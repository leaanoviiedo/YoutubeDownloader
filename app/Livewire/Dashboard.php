<?php

namespace App\Livewire;

use App\Jobs\DownloadPlaylistJob;
use App\Services\YouTubeDownloadService;
use Illuminate\Support\Facades\Redis;
use Livewire\Component;

class Dashboard extends Component
{
    public string $url = '';
    public array $downloads = [];
    public array $previewTracks = [];
    public bool $loading = false;
    public bool $previewing = false;
    public ?string $playingTrack = null;
    public string $playlistUrl = '';

    public function mount(): void
    {
        $this->fetchDownloads();
    }

    public function fetchPlaylist(): void
    {
        $this->validate([
            'url' => 'required|url',
        ]);

        $this->loading = true;
        $this->previewing = false;
        $this->previewTracks = [];

        try {
            $service = app(YouTubeDownloadService::class);
            $tracks = $service->getPlaylistInfo($this->url);

            $this->previewTracks = [];
            foreach ($tracks as $track) {
                $url = $track['webpage_url'] ?? $track['url'] ?? null;
                if (!$url) continue;

                $this->previewTracks[] = [
                    'title' => $track['title'] ?? 'Sin título',
                    'url' => $url,
                    'duration' => $track['duration'] ?? null,
                    'uploader' => $track['uploader'] ?? $track['channel'] ?? '',
                ];
            }

            $this->playlistUrl = $this->url;
            $this->url = '';
            $this->previewing = true;
            $this->dispatch('notify', count($this->previewTracks) . ' canciones encontradas');
        } catch (\Exception $e) {
            $this->dispatch('notify', 'Error: ' . $e->getMessage());
        }

        $this->loading = false;
    }

    public function startDownload(): void
    {
        if (empty($this->previewTracks)) return;

        foreach ($this->previewTracks as $track) {
            $id = md5($track['url']);
            Redis::hset('download_status', $id, json_encode([
                'title' => $track['title'],
                'status' => 'queued',
                'progress' => 0,
            ]));
        }

        DownloadPlaylistJob::dispatch($this->playlistUrl);

        $this->previewTracks = [];
        $this->previewing = false;
        $this->playlistUrl = '';
        $this->dispatch('notify', '¡Descargas iniciadas!');
        $this->fetchDownloads();
    }

    public function stopDownloads(): void
    {
        // Mark queued/downloading tracks as 'stopped' and purge the queue
        $statuses = Redis::hgetall('download_status');
        foreach ($statuses as $id => $json) {
            $data = json_decode($json, true);
            if (in_array($data['status'], ['queued', 'downloading'])) {
                $data['status'] = 'stopped';
                Redis::hset('download_status', $id, json_encode($data));
            }
        }

        // Clear the Redis queue to prevent pending jobs from running
        Redis::del('queues:default');

        $this->dispatch('notify', 'Descargas detenidas');
        $this->fetchDownloads();
    }

    public function cancelPreview(): void
    {
        $this->previewTracks = [];
        $this->previewing = false;
        $this->playlistUrl = '';
    }

    public function clearAll(): void
    {
        Redis::del('download_status');
        $this->downloads = [];
        $this->playingTrack = null;

        $dir = storage_path('app/downloads');
        if (is_dir($dir)) {
            $patterns = ['*.mp3', '*.part', '*.temp', '*.webm', '*.m4a', '*.opus', '*.zip', '*.tmp', '*.ytdl'];
            foreach ($patterns as $pattern) {
                foreach (glob($dir . '/' . $pattern) as $file) {
                    @unlink($file);
                }
            }
        }
    }

    public function downloadZip(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $dir = storage_path('app/downloads');
        $zipPath = storage_path('app/downloads/playlist.zip');
        
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        // Only include files that are in the current visible downloads list
        foreach ($this->downloads as $item) {
            if ($item['status'] === 'completed' && isset($item['filename'])) {
                $filePath = $dir . '/' . $item['filename'];
                if (file_exists($filePath)) {
                    $zip->addFile($filePath, $item['filename']);
                }
            }
        }

        $zip->close();

        return response()->download($zipPath, 'playlist.zip')->deleteFileAfterSend();
    }

    public function playTrack(string $id): void
    {
        $this->playingTrack = $this->playingTrack === $id ? null : $id;
    }

    public function loadExistingFiles(): void
    {
        $dir = storage_path('app/downloads');
        if (!is_dir($dir)) return;

        $files = glob($dir . '/*.mp3');
        foreach ($files as $file) {
            $filename = basename($file);
            $id = md5($filename);
            if (!Redis::hexists('download_status', $id)) {
                $title = str_replace(['.mp3', '_', '-'], ['', ' ', ' '], $filename);
                Redis::hset('download_status', $id, json_encode([
                    'title' => ucwords(trim($title)),
                    'status' => 'completed',
                    'progress' => 100,
                    'filename' => $filename,
                ]));
            }
        }
        $this->fetchDownloads();
    }

    public function fetchDownloads(): void
    {
        $statuses = Redis::hgetall('download_status');
        $this->downloads = array_map(fn($item) => json_decode($item, true), $statuses);
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
