<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\Storage;

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

        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $output = $process->getOutput();
        $lines = explode("\n", trim($output));
        
        return array_map(fn($line) => json_decode($line, true), $lines);
    }

    public function downloadTrack(string $url, string $filename, ?callable $onProgress = null): string
    {
        if (!file_exists(storage_path('app/downloads'))) {
            mkdir(storage_path('app/downloads'), 0755, true);
        }
        $path = storage_path('app/downloads/' . $filename . '.mp3');
        
        $process = new Process([
            'yt-dlp',
            '-x',
            '--audio-format', 'mp3',
            '--audio-quality', '0',
            '-o', storage_path('app/downloads/%(title)s.%(ext)s'),
            $url
        ]);

        $process->setTimeout(3600);
        $process->run(function ($type, $buffer) use ($onProgress) {
            if ($onProgress) {
                // Parse yt-dlp output for progress if needed
                $onProgress($buffer);
            }
        });

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $path;
    }
}
