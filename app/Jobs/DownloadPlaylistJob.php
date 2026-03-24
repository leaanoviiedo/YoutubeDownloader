<?php

namespace App\Jobs;

use App\Services\YouTubeDownloadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DownloadPlaylistJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected string $playlistUrl)
    {
    }

    public function handle(YouTubeDownloadService $service): void
    {
        try {
            $tracks = $service->getPlaylistInfo($this->playlistUrl);

            foreach ($tracks as $track) {
                if (isset($track['url'])) {
                    DownloadTrackJob::dispatch($track['url'], $track['title'] ?? 'unknown');
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to process playlist: " . $e->getMessage());
        }
    }
}
