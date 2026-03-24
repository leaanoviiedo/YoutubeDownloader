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

class DownloadTrackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    public function __construct(
        protected string $url,
        protected string $title
    ) {
    }

    public function handle(YouTubeDownloadService $service): void
    {
        $id = md5($this->url);
        Redis::hset('download_status', $id, json_encode([
            'title' => $this->title,
            'status' => 'downloading',
            'progress' => 0
        ]));

        try {
            $service->downloadTrack($this->url, $this->title, function ($buffer) use ($id) {
                // Simplified progress parsing
                if (preg_match('/\[download\]\s+(\d+\.\d+)%/', $buffer, $matches)) {
                    $progress = $matches[1];
                    Redis::hset('download_status', $id, json_encode([
                        'title' => $this->title,
                        'status' => 'downloading',
                        'progress' => $progress
                    ]));
                }
            });

            Redis::hset('download_status', $id, json_encode([
                'title' => $this->title,
                'status' => 'completed',
                'progress' => 100
            ]));

        } catch (\Exception $e) {
            Log::error("Failed to download track {$this->title}: " . $e->getMessage());
            Redis::hset('download_status', $id, json_encode([
                'title' => $this->title,
                'status' => 'failed',
                'error' => $e->getMessage()
            ]));
        }
    }
}
