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
     * Uses --flat-playlist (instant) which avoids processing all formats.
     */
    public function getSingleTrackInfo(string $url): array
    {
        $process = new Process([
            'yt-dlp',
            '--no-playlist',
            '--flat-playlist',
            '--print', '{"title":%(title)j,"webpage_url":%(webpage_url)j,"duration":%(duration)j,"uploader":%(uploader)j,"thumbnail":%(thumbnail)j,"view_count":%(view_count)j}',
            $url
        ]);

        $process->setTimeout(30);
        $process->run();

        // If flat-playlist fails, try without it (slightly slower but more compatible)
        if (!$process->isSuccessful() || empty(trim($process->getOutput()))) {
            $process2 = new Process([
                'yt-dlp',
                '--no-playlist',
                '--skip-download',
                '--print', '{"title":%(title)j,"webpage_url":%(webpage_url)j,"duration":%(duration)j,"uploader":%(uploader)j,"thumbnail":%(thumbnail)j,"view_count":%(view_count)j}',
                $url
            ]);
            $process2->setTimeout(60);
            $process2->run();

            if (!$process2->isSuccessful()) {
                throw new ProcessFailedException($process2);
            }
            $output = trim($process2->getOutput());
        } else {
            $output = trim($process->getOutput());
        }

        $firstLine = strtok($output, "\n");
        $data = json_decode($firstLine, true);

        if (!$data || empty($data['title'])) {
            throw new \RuntimeException('No se pudo obtener información del video. Verificá que sea una URL válida de YouTube.');
        }

        // Build URL from id if needed
        $videoUrl = $data['webpage_url'] ?? '';
        if (empty($videoUrl) || !str_starts_with($videoUrl, 'http')) {
            $videoUrl = $url;
        }

        return [
            'title'      => $data['title'],
            'url'        => $videoUrl,
            'duration'   => is_numeric($data['duration'] ?? null) ? (int)$data['duration'] : null,
            'uploader'   => $data['uploader'] ?? '',
            'thumbnail'  => $data['thumbnail'] ?? null,
            'view_count' => is_numeric($data['view_count'] ?? null) ? (int)$data['view_count'] : null,
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
            '--print', '{"title":%(title)j,"id":%(id)j,"url":%(url)j,"duration":%(duration)j,"channel":%(channel)j,"thumbnail":%(thumbnail)j,"view_count":%(view_count)j}',
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
            $data = json_decode($line, true);
            if (!$data || empty($data['title'])) continue;

            // Build full URL from id if url is just the id
            $url = $data['url'] ?? '';
            if ($url && !str_starts_with($url, 'http')) {
                $url = 'https://www.youtube.com/watch?v=' . ($data['id'] ?? $url);
            }

            $results[] = [
                'title'      => $data['title'],
                'url'        => $url,
                'duration'   => is_numeric($data['duration']) ? (int)$data['duration'] : null,
                'channel'    => $data['channel'] ?? '',
                'thumbnail'  => $data['thumbnail'] ?? null,
                'view_count' => is_numeric($data['view_count']) ? (int)$data['view_count'] : null,
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
