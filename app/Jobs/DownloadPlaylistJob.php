<?php

namespace App\Jobs;

use App\Services\YouTubeDownloadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class DownloadPlaylistJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(protected string $playlistUrl)
    {
    }

    public function handle(YouTubeDownloadService $service): void
    {
        try {
            $tracks = $service->getPlaylistInfo($this->playlistUrl);

            // Register ALL tracks in Redis immediately with 'queued' status
            $jobs = [];
            foreach ($tracks as $track) {
                $url = $track['webpage_url'] ?? $track['url'] ?? null;
                $title = $track['title'] ?? 'Unknown track';

                if (!$url) {
                    continue;
                }

                $id = md5($url);
                Redis::hset('download_status', $id, json_encode([
                    'title' => $title,
                    'status' => 'queued',
                    'progress' => 0,
                ]));

                $jobs[] = new DownloadTrackJob($url, $title);
            }

            // Dispatch all jobs at once for parallel processing
            foreach ($jobs as $job) {
                Bus::dispatch($job);
            }

        } catch (\Exception $e) {
            Log::error("Failed to process playlist: " . $e->getMessage());
        }
    }
}
