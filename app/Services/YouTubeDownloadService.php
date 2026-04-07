<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Str;

class YouTubeDownloadService
{
    /**
     * Get the playlist title from a YouTube URL.
     */
    public function getPlaylistTitle(string $url): string
    {
        $process = new Process([
            'yt-dlp',
            '--print', 'playlist_title',
            '--playlist-items', '1',
            $url
        ]);

        $process->setTimeout(60);
        $process->run();

        if (!$process->isSuccessful()) {
            return 'playlist';
        }

        $title = trim($process->getOutput());

        return !empty($title) && $title !== 'NA' ? $title : 'playlist';
    }

    public function getPlaylistInfo(string $url): array
    {
        $process = new Process([
            'yt-dlp',
            '--dump-json',
            '--flat-playlist',
            $url
        ]);

        $process->setTimeout(120);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $output = $process->getOutput();
        $lines = explode("\n", trim($output));

        return array_map(fn($line) => json_decode($line, true), $lines);
    }

    /**
     * Get metadata for a single video quickly.
     * Uses pipe-separated --print fields to avoid JSON parsing issues with stderr.
     */
    public function getSingleTrackInfo(string $url): array
    {
        // Use pipe-separated fields — avoids JSON template + stderr mixing issue
        $process = new Process([
            'yt-dlp',
            '--no-playlist',
            '--flat-playlist',
            '--print', '%(title)s|||%(webpage_url)s|||%(duration)s|||%(uploader)s|||%(thumbnail)s|||%(view_count)s',
            $url
        ]);

        $process->setTimeout(30);
        $process->run();

        // Parse stdout only (ignores stderr warnings like HTTP 429)
        $stdout = trim($process->getOutput());

        if (!$process->isSuccessful() || empty($stdout)) {
            // Fallback: try without --flat-playlist
            $process2 = new Process([
                'yt-dlp',
                '--no-playlist',
                '--skip-download',
                '--print', '%(title)s|||%(webpage_url)s|||%(duration)s|||%(uploader)s|||%(thumbnail)s|||%(view_count)s',
                $url
            ]);
            $process2->setTimeout(60);
            $process2->run();

            if (!$process2->isSuccessful()) {
                throw new ProcessFailedException($process2);
            }
            $stdout = trim($process2->getOutput());
        }

        // Take first line (in case there's extra output)
        $line = strtok($stdout, "\n");
        $parts = explode('|||', $line);

        if (count($parts) < 2 || empty($parts[0])) {
            throw new \RuntimeException('No se pudo obtener información del video. Verificá que sea una URL válida de YouTube.');
        }

        $duration   = isset($parts[2]) && is_numeric($parts[2]) ? (int)$parts[2] : null;
        $viewCount  = isset($parts[5]) && is_numeric($parts[5]) ? (int)$parts[5] : null;
        $videoUrl   = !empty($parts[1]) && str_starts_with($parts[1], 'http') ? $parts[1] : $url;
        $thumbnail  = isset($parts[4]) && str_starts_with($parts[4], 'http') ? $parts[4] : null;

        return [
            'title'      => $parts[0],
            'url'        => $videoUrl,
            'duration'   => $duration,
            'uploader'   => $parts[3] ?? '',
            'thumbnail'  => $thumbnail,
            'view_count' => $viewCount,
        ];
    }


    /**
     * Search YouTube by keyword. Returns up to $limit results.
     */
    public function searchVideos(string $query, int $limit = 10): array
    {
        $process = new Process([
            'yt-dlp',
            '--flat-playlist',
            '--print', '%(title)s|||%(id)s|||%(url)s|||%(duration)s|||%(channel)s|||%(thumbnail)s|||%(view_count)s',
            "ytsearch{$limit}:{$query}"
        ]);

        $process->setTimeout(60);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $results = [];
        foreach (explode("\n", trim($process->getOutput())) as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $parts = explode('|||', $line);
            if (count($parts) < 2 || empty($parts[0]) || $parts[0] === 'NA') continue;

            // Build full URL from id
            $id  = $parts[1] ?? '';
            $url = $parts[2] ?? '';
            if (!str_starts_with($url, 'http')) {
                $url = "https://www.youtube.com/watch?v={$id}";
            }

            $duration  = isset($parts[3]) && is_numeric($parts[3]) ? (int)(float)$parts[3] : null;
            $viewCount = isset($parts[6]) && is_numeric($parts[6]) ? (int)$parts[6] : null;
            $thumbnail = isset($parts[5]) && str_starts_with($parts[5], 'http') ? $parts[5] : "https://i.ytimg.com/vi/{$id}/mqdefault.jpg";

            $results[] = [
                'title'      => $parts[0],
                'url'        => $url,
                'duration'   => $duration,
                'channel'    => $parts[4] ?? '',
                'thumbnail'  => $thumbnail,
                'view_count' => $viewCount,
            ];
        }

        return $results;
    }

    /**
     * Download a track and convert to the specified audio format + bitrate.
     *
     * @param string $audioFormat  Accepted: mp3, flac, ogg
     * @param string $audioBitrate Accepted: best, 320k, 192k, 128k
     */
    public function downloadTrack(
        string $url,
        string $outputTemplate,
        string $subfolder = '',
        ?callable $onProgress = null,
        string $audioFormat = 'mp3',
        string $audioBitrate = 'best'
    ): string {
        $allowedFormats = ['mp3', 'flac', 'ogg'];
        if (!in_array($audioFormat, $allowedFormats)) {
            $audioFormat = 'mp3';
        }

        // Translate bitrate selector to yt-dlp --audio-quality value
        $qualityArg = match ($audioBitrate) {
            '320k'  => '320K',
            '192k'  => '192K',
            '128k'  => '128K',
            default => '0',   // 0 = best VBR
        };

        $downloadsDir = storage_path('app/downloads');
        if (!empty($subfolder)) {
            $downloadsDir .= '/' . $subfolder;
        }
        if (!file_exists($downloadsDir)) {
            mkdir($downloadsDir, 0755, true);
        }

        $template = $downloadsDir . '/' . $outputTemplate . '.%(ext)s';

        $process = new Process([
            'yt-dlp',
            '-x',
            '--audio-format', $audioFormat,
            '--audio-quality', $qualityArg,
            '-o', $template,
            '--no-playlist',
            $url
        ]);

        $process->setTimeout(3600);
        $process->run(function ($type, $buffer) use ($onProgress) {
            if ($onProgress) {
                $onProgress($buffer);
            }
        });

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        // Find the output file
        $expectedFile = $downloadsDir . '/' . $outputTemplate . '.' . $audioFormat;
        $relativePrefix = !empty($subfolder) ? $subfolder . '/' : '';

        if (file_exists($expectedFile)) {
            return $relativePrefix . basename($expectedFile);
        }

        // Fallback: return the latest audio file in the directory
        foreach ([$audioFormat, 'mp3', 'flac', 'ogg', 'm4a', 'opus'] as $ext) {
            $files = glob($downloadsDir . '/*.' . $ext);
            if (!empty($files)) {
                usort($files, fn($a, $b) => filemtime($b) - filemtime($a));
                return $relativePrefix . basename($files[0]);
            }
        }

        return '';
    }
}
