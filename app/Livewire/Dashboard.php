<?php

namespace App\Livewire;

use App\Jobs\DownloadPlaylistJob;
use Illuminate\Support\Facades\Redis;
use Livewire\Component;
use Livewire\Attributes\On;

class Dashboard extends Component
{
    public string $url = '';
    public array $downloads = [];

    public function download(): void
    {
        $this->validate([
            'url' => 'required|url',
        ]);

        DownloadPlaylistJob::dispatch($this->url);
        
        $this->url = '';
        $this->dispatch('notify', 'Playlist download started!');
    }

    #[On('echo:download_status,ProgressUpdated')]
    public function updateProgress(): void
    {
        $this->fetchDownloads();
    }

    public function fetchDownloads(): void
    {
        $statuses = Redis::hgetall('download_status');
        $this->downloads = array_map(fn($item) => json_decode($item, true), $statuses);
    }

    public function render()
    {
        $this->fetchDownloads();
        return view('livewire.dashboard');
    }
}
