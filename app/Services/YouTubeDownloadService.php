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
     * Get metadata for a single video (ignores playlist context).
     */
    public function getSingleTrackInfo(string $url): array
    {
        $process = new Process([
            'yt-dlp',
            '--dump-json',
            '--no-playlist',
            $url
        ]);

        $process->setTimeout(60);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $output = trim($process->getOutput());
        // Take only the first JSON object (in case of multiple lines)
        $firstLine = strtok($output, "\n");
        $data = json_decode($firstLine, true);

        if (!$data) {
            throw new \RuntimeException('No se pudo obtener información del video.');
        }

        return [
            'title'     => $data['title'] ?? 'Sin título',
            'url'       => $data['webpage_url'] ?? $url,
            'duration'  => $data['duration'] ?? null,
            'uploader'  => $data['uploader'] ?? $data['channel'] ?? '',
            'thumbnail' => $data['thumbnail'] ?? null,
            'view_count'=> $data['view_count'] ?? null,
        ];
    }

    /**
     * @param string $audioFormat  Accepted: mp3, flac, ogg
     */
    public function downloadTrack(string $url, string $outputTemplate, string $subfolder = '', ?callable $onProgress = null, string $audioFormat = 'mp3'): string
    {
        $allowedFormats = ['mp3', 'flac', 'ogg'];
        if (!in_array($audioFormat, $allowedFormats)) {
            $audioFormat = 'mp3';
        }

        $downloadsDir = storage_path('app/downloads');

        if (!empty($subfolder)) {
            $downloadsDir .= '/' . $subfolder;
        }

        if (!file_exists($downloadsDir)) {
            mkdir($downloadsDir, 0755, true);
        }

        // Use a specific output template that includes the video ID for uniqueness
        $template = $downloadsDir . '/' . $outputTemplate . '.%(ext)s';

        $process = new Process([
            'yt-dlp',
            '-x',
            '--audio-format', $audioFormat,
            '--audio-quality', '0',
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
        $extensions = [$audioFormat, 'mp3', 'flac', 'ogg', 'm4a', 'opus'];
        foreach ($extensions as $ext) {
            $files = glob($downloadsDir . '/*.' . $ext);
            if (!empty($files)) {
                usort($files, fn($a, $b) => filemtime($b) - filemtime($a));
                return $relativePrefix . basename($files[0]);
            }
        }

        return '';
    }
}
