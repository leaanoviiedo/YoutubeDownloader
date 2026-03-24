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
        protected string $title
    ) {
    }

    public function handle(YouTubeDownloadService $service): void
    {
        $id = md5($this->url);

        // Check if this download was stopped
        $existing = Redis::hget('download_status', $id);
        if ($existing) {
            $data = json_decode($existing, true);
            if (($data['status'] ?? '') === 'stopped') {
                return;
            }
        }

        // Sanitize the title for use as filename
        $safeTitle = Str::slug($this->title, '_');
        if (empty($safeTitle)) {
            $safeTitle = 'track_' . $id;
        }

        Redis::hset('download_status', $id, json_encode([
            'title' => $this->title,
            'status' => 'downloading',
            'progress' => 0,
        ]));

        try {
            $filename = $service->downloadTrack($this->url, $safeTitle, function ($buffer) use ($id) {
                if (preg_match('/\[download\]\s+(\d+\.?\d*)%/', $buffer, $matches)) {
                    $progress = floatval($matches[1]);
                    Redis::hset('download_status', $id, json_encode([
                        'title' => $this->title,
                        'status' => 'downloading',
                        'progress' => $progress,
                    ]));
                }
            });

            Redis::hset('download_status', $id, json_encode([
                'title' => $this->title,
                'status' => 'completed',
                'progress' => 100,
                'filename' => $filename,
            ]));

        } catch (\Exception $e) {
            Log::error("Error al descargar {$this->title}: " . $e->getMessage());
            Redis::hset('download_status', $id, json_encode([
                'title' => $this->title,
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]));
        }
    }
}
