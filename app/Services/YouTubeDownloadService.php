<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class YouTubeDownloadService
{
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

    public function downloadTrack(string $url, string $outputTemplate, ?callable $onProgress = null): string
    {
        $downloadsDir = storage_path('app/downloads');
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
        if (file_exists($expectedFile)) {
            return basename($expectedFile);
        }

        // Fallback: return the latest mp3 in the directory
        $files = glob($downloadsDir . '/*.mp3');
        if (!empty($files)) {
            usort($files, fn($a, $b) => filemtime($b) - filemtime($a));
            return basename($files[0]);
        }

        return '';
    }
}
