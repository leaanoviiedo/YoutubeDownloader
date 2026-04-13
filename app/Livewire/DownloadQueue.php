<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class DownloadQueue extends Component
{
    public array $downloads = [];

    public function fetchDownloads(): void
    {
        try {
            $sid = session()->getId();
            $hashKey = "download_status_{$sid}";
            $statuses = Redis::hgetall($hashKey);
            $this->downloads = [];
            foreach ((array) $statuses as $id => $json) {
                $d = json_decode($json, true);
                if (is_array($d)) {
                    $d['id'] = $id;
                    $this->downloads[] = $d;
                }
            }
            usort($this->downloads, fn($a, $b) => ($b['added_at'] ?? 0) <=> ($a['added_at'] ?? 0));
        } catch (\Exception $e) {
            Log::error('DownloadQueue::fetchDownloads - ' . $e->getMessage());
        }
    }

    public function stopDownloads(): void
    {
        try {
            $sid = session()->getId();
            $hashKey = "download_status_{$sid}";
            foreach ((array) Redis::hgetall($hashKey) as $id => $json) {
                $data = json_decode($json, true);
                if (isset($data['status']) && in_array($data['status'], ['queued', 'downloading'])) {
                    $data['status'] = 'stopped';
                    Redis::hset($hashKey, $id, json_encode($data));
                }
            }
            // Eliminado Redis::del('queues:default') para no afectar a otros
            $this->dispatch('notify', 'Descargas detenidas');
            $this->fetchDownloads();
        } catch (\Exception $e) {
            Log::error('DownloadQueue::stopDownloads - ' . $e->getMessage());
        }
    }

    public function clearAll(): void
    {
        try {
            $sid = session()->getId();
            Redis::del("download_status_{$sid}");
            Redis::del("current_playlist_folder_{$sid}");
            Redis::del("current_playlist_name_{$sid}");
            $this->downloads = [];
            $this->dispatch('notify', 'Cola vaciada');
        } catch (\Exception $e) {
            Log::error('DownloadQueue::clearAll - ' . $e->getMessage());
        }
    }

    public function render()
    {
        $this->fetchDownloads();
        return view('livewire.download-queue');
    }
}
