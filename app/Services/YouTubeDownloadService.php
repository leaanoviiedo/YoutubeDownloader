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

    public function downloadTrack(string $url, string $outputTemplate, string $subfolder = '', ?callable $onProgress = null): string
    {
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
            '--audio-format', 'mp3',
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
        $expectedFile = $downloadsDir . '/' . $outputTemplate . '.mp3';
        $relativePrefix = !empty($subfolder) ? $subfolder . '/' : '';

        if (file_exists($expectedFile)) {
            return $relativePrefix . basename($expectedFile);
        }

        // Fallback: return the latest mp3 in the directory
        $files = glob($downloadsDir . '/*.mp3');
        if (!empty($files)) {
            usort($files, fn($a, $b) => filemtime($b) - filemtime($a));
            return $relativePrefix . basename($files[0]);
        }

        return '';
    }
}
