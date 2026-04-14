<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Str;

class YouTubeDownloadService
{
    private string $cookiesFile;
    private bool   $hasCookies;

    public function __construct()
    {
        $this->cookiesFile = storage_path('app/youtube_cookies.txt');
        $this->hasCookies  = file_exists($this->cookiesFile);
    }

    /**
     * Build a yt-dlp command array, injecting --cookies if the file exists.
     */
    private function ytdlp(array $args): array
    {
        $base = ['yt-dlp'];
        if ($this->hasCookies) {
            $base[] = '--cookies';
            $base[] = $this->cookiesFile;
        }
        return array_merge($base, $args);
    }

    /**
     * Get the playlist title from a YouTube URL.
     */
    public function getPlaylistTitle(string $url): string
    {
        $process = new Process($this->ytdlp([
            '--print', 'playlist_title',
            '--playlist-items', '1',
            $url
        ]));

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
        $process = new Process($this->ytdlp([
            '--dump-json',
            '--flat-playlist',
            $url
        ]));

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
        $process = new Process($this->ytdlp([
            '--no-playlist',
            '--flat-playlist',
            '--print', '%(title)s|||%(webpage_url)s|||%(duration)s|||%(uploader)s|||%(thumbnail)s|||%(view_count)s',
            $url
        ]));

        $process->setTimeout(30);
        $process->run();

        // Parse stdout only (ignores stderr warnings like HTTP 429)
        $stdout = trim($process->getOutput());

        if (!$process->isSuccessful() || empty($stdout)) {
            // First fallback: avoid flat-playlist
            $process2 = new Process($this->ytdlp([
                '--no-playlist',
                '--skip-download',
                '--print', '%(title)s|||%(webpage_url)s|||%(duration)s|||%(uploader)s|||%(thumbnail)s|||%(view_count)s',
                $url
            ]));
            $process2->setTimeout(30);
            $process2->run();

            if ($process2->isSuccessful() && !empty(trim($process2->getOutput()))) {
                $stdout = trim($process2->getOutput());
            } else {
                // Secondary fallback: YouTube oEmbed API (avoids bot detection for preview metadata)
                try {
                    $response = \Illuminate\Support\Facades\Http::timeout(10)->get('https://www.youtube.com/oembed', [
                        'url' => $url,
                        'format' => 'json'
                    ]);
                    if ($response->successful()) {
                        $data = $response->json();
                        return [
                            'title'      => $data['title'] ?? 'Video',
                            'url'        => $url,
                            'duration'   => null,
                            'uploader'   => $data['author_name'] ?? '',
                            'thumbnail'  => $data['thumbnail_url'] ?? "https://i.ytimg.com/vi/" . (preg_match('/(?:v=|youtu\.be\/)([^&]+)/', $url, $m) ? $m[1] : '') . "/mqdefault.jpg",
                            'view_count' => null,
                        ];
                    }
                } catch (\Exception $e) {
                    // Ignore, drop down to exception below
                }
                throw new ProcessFailedException($process2);
            }
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
        $process = new Process($this->ytdlp([
            '--flat-playlist',
            '--print', '%(title)s|||%(id)s|||%(url)s|||%(duration)s|||%(channel)s|||%(thumbnail)s|||%(view_count)s',
            "ytsearch{$limit}:{$query}"
        ]));

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

        $process = new Process($this->ytdlp([
            '-x',
            '--audio-format', $audioFormat,
            '--audio-quality', $qualityArg,
            '-o', $template,
            '--no-playlist',
            $url
        ]));

        $process->setTimeout(3600);
        $process->run(function ($type, $buffer) use ($onProgress) {
            if ($onProgress) {
                $onProgress($buffer);
            }
        });

        $expectedFile = $downloadsDir . '/' . $outputTemplate . '.' . $audioFormat;
        $relativePrefix = !empty($subfolder) ? $subfolder . '/' : '';

        if ($process->isSuccessful() && file_exists($expectedFile)) {
            return $relativePrefix . basename($expectedFile);
        }

        // ─────────────────────────────────────────────────────────────────
        // FALLBACK: Dedicated Converter API (Loader.to / Savenow)
        // ─────────────────────────────────────────────────────────────────
        // Map audio format to API supported format
        $apiFormat = match ($audioFormat) {
            'mp3' => 'mp3',
            'flac' => 'flac',
            'ogg' => 'ogg',
            'wav' => 'wav',
            default => 'mp3',
        };

        try {
            // 1. Init conversion
            $initResponse = \Illuminate\Support\Facades\Http::timeout(10)->get('https://loader.to/ajax/download.php', [
                'format' => $apiFormat,
                'url' => $url
            ]);

            if ($initResponse->successful() && !empty($initResponse->json('id'))) {
                $jobId = $initResponse->json('id');
                $downloadUrl = null;

                // 2. Poll for completion (hasta 15 minutos en caso de mucha cola)
                for ($i = 0; $i < 180; $i++) {
                    sleep(5);
                    $progRes = \Illuminate\Support\Facades\Http::timeout(10)->get('https://p.savenow.to/api/progress', ['id' => $jobId]);
                    if ($progRes->successful()) {
                        $progData = $progRes->json();
                        
                        // Error handling from API
                        if (isset($progData['success']) && $progData['success'] == 0 && isset($progData['text']) && (str_contains(strtolower($progData['text']), 'error') || str_contains(strtolower($progData['text']), 'fail'))) {
                            throw new \RuntimeException("API Externa (Loader.to) rechazó este video: " . $progData['text']);
                        }

                        if ($onProgress && isset($progData['progress'])) {
                            $pct = round($progData['progress'] / 10, 1);
                            $onProgress("[download] {$pct}% of unknown");
                        }

                        if (!empty($progData['download_url'])) {
                            $downloadUrl = $progData['download_url'];
                            break;
                        }
                    }
                }

                if ($downloadUrl) {
                    $finalFile = $expectedFile;
                    // Simply download the ready file
                    \Illuminate\Support\Facades\Http::timeout(1800)
                        ->withOptions(['stream' => true])
                        ->sink($finalFile)
                        ->get($downloadUrl);

                    if (file_exists($finalFile) && filesize($finalFile) > 0) {
                        return $relativePrefix . basename($finalFile);
                    }
                }
            } else {
                 throw new \RuntimeException("API de conversión (Loader) rechazada en inicio. " . $initResponse->body());
            }
        } catch (\Exception $e) {
            throw new \RuntimeException("API de conversión: " . $e->getMessage());
        }

        throw new \RuntimeException("Tiempo de espera agotado al convertir en la API externa. Subí cookies en Configuración.");
    }

    private function extractVideoId(string $url): ?string
    {
        if (preg_match('/(?:v=|youtu\.be\/)([^&]+)/', $url, $m)) {
            return $m[1];
        }
        return null;
    }
}
