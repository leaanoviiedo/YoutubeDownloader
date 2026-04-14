<?php

namespace App\Jobs;

use App\Services\YouTubeDownloadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class DownloadTrackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    public function __construct(
        protected string $url,
        protected string $title,
        protected string $playlistFolder = '',
        protected string $audioFormat    = 'mp3',
        protected string $audioBitrate   = 'best',
        protected string $sessionId      = ''
    ) {
    }

    public function handle(YouTubeDownloadService $service): void
    {
        $id = md5($this->url);
        $hashKey = $this->sessionId ? "download_status_{$this->sessionId}" : 'download_status';

        $data = [];
        $existing = Redis::hget($hashKey, $id);
        if ($existing) {
            $data = json_decode($existing, true) ?? [];
            if (($data['status'] ?? '') === 'stopped') {
                return;
            }
        }

        $safeTitle = Str::slug($this->title, '_');
        if (empty($safeTitle)) {
            $safeTitle = 'track_' . $id;
        }

        $data = array_merge($data, [
            'id'             => $id,
            'url'            => $this->url,
            'title'          => $this->title,
            'status'         => 'downloading',
            'progress'       => 0,
            'playlist_folder'=> $this->playlistFolder,
            'audio_format'   => $this->audioFormat,
            'audio_bitrate'  => $this->audioBitrate,
        ]);
        Redis::hset($hashKey, $id, json_encode($data));

        try {
            $filename = $service->downloadTrack(
                $this->url,
                $safeTitle,
                $this->playlistFolder,
                function ($buffer) use ($id, $hashKey) {
                    if (preg_match('/\[download\]\s+(\d+\.?\d*)%/', $buffer, $matches)) {
                        $progress = floatval($matches[1]);
                        $currentData = json_decode(Redis::hget($hashKey, $id) ?? '{}', true) ?: [];
                        $currentData['status'] = 'downloading';
                        $currentData['progress'] = $progress;
                        Redis::hset($hashKey, $id, json_encode($currentData));
                        Redis::expire($hashKey, 7200); // 2 hours expiry
                    }
                },
                $this->audioFormat,
                $this->audioBitrate
            );

            $finalData = json_decode(Redis::hget($hashKey, $id) ?? '{}', true) ?: [];
            $finalData['status'] = 'completed';
            $finalData['progress'] = 100;
            $finalData['filename'] = $filename;
            Redis::hset($hashKey, $id, json_encode($finalData));
            Redis::expire($hashKey, 7200);

        } catch (\Exception $e) {
            Log::error("Error al descargar {$this->title}: " . $e->getMessage());
            $errorData = json_decode(Redis::hget($hashKey, $id) ?? '{}', true) ?: [];
            $errorData['status'] = 'failed';
            $errorData['error'] = $e->getMessage();
            Redis::hset($hashKey, $id, json_encode($errorData));
            Redis::expire($hashKey, 7200);
        }
    }
}
