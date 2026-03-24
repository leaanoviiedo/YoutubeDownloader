<?php

namespace App\Livewire;

use App\Jobs\DownloadPlaylistJob;
use Illuminate\Support\Facades\Redis;
use Livewire\Component;

class Dashboard extends Component
{
    public string $url = '';
    public array $downloads = [];
    public bool $loading = false;
    public ?string $playingTrack = null;

    public function mount(): void
    {
        $this->fetchDownloads();
    }

    public function download(): void
    {
        $this->validate([
            'url' => 'required|url',
        ]);

        $this->loading = true;

        DownloadPlaylistJob::dispatch($this->url);
        
        $this->url = '';
        $this->dispatch('notify', '¡Playlist en cola! Obteniendo lista de canciones...');
    }

    public function clearAll(): void
    {
        Redis::del('download_status');
        $this->downloads = [];
        $this->playingTrack = null;
    }

    public function playTrack(string $id): void
    {
        $this->playingTrack = $this->playingTrack === $id ? null : $id;
    }

    public function loadExistingFiles(): void
    {
        $dir = storage_path('app/downloads');
        if (!is_dir($dir)) {
            return;
        }

        $files = glob($dir . '/*.mp3');
        foreach ($files as $file) {
            $filename = basename($file);
            $id = md5($filename);
            
            // Only add if not already tracked in Redis
            if (!Redis::hexists('download_status', $id)) {
                $title = str_replace(['.mp3', '_', '-'], ['', ' ', ' '], $filename);
                $title = ucwords(trim($title));
                
                Redis::hset('download_status', $id, json_encode([
                    'title' => $title,
                    'status' => 'completed',
                    'progress' => 100,
                    'filename' => $filename,
                ]));
            }
        }
    }

    public function fetchDownloads(): void
    {
        $statuses = Redis::hgetall('download_status');
        $this->downloads = array_map(fn($item) => json_decode($item, true), $statuses);
        
        if (count($this->downloads) > 0) {
            $this->loading = false;
        }
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
