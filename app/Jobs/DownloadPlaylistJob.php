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
use Illuminate\Support\Str;

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
            // Get playlist title and create folder name
            $playlistTitle = $service->getPlaylistTitle($this->playlistUrl);
            $playlistFolder = Str::slug($playlistTitle, '_');

            if (empty($playlistFolder)) {
                $playlistFolder = 'playlist_' . date('Y_m_d_His');
            }

            // Store playlist name in Redis for the UI
            Redis::set('current_playlist_name', $playlistTitle);
            Redis::set('current_playlist_folder', $playlistFolder);

            // Create the playlist directory
            $playlistDir = storage_path('app/downloads/' . $playlistFolder);
            if (!file_exists($playlistDir)) {
                mkdir($playlistDir, 0755, true);
            }

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
                    'playlist_folder' => $playlistFolder,
                ]));

                $jobs[] = new DownloadTrackJob($url, $title, $playlistFolder);
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
