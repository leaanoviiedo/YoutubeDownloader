<?php

namespace App\Livewire;

use App\Jobs\DownloadPlaylistJob;
use App\Jobs\DownloadTrackJob;
use App\Services\YouTubeDownloadService;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
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
    public string $playlistName = '';
    public ?string $errorMessage = null;

    // Single-track mode
    public bool $singleMode = false;
    public array $singleTrackInfo = [];

    // Audio options
    public string $audioFormat  = 'mp3';
    public string $audioBitrate = 'best'; // best | 320k | 192k | 128k

    // Playlist track selection
    public array $selectedTracks = [];  // array of int indices

    // Search feature
    public string $activeTab     = 'download'; // 'download' | 'search'
    public string $searchQuery   = '';
    public array  $searchResults = [];
    public bool   $searching     = false;
    public ?string $searchError  = null;

    public function mount(): void
    {
        $this->playlistName = Redis::get('current_playlist_name') ?? '';
        $this->fetchDownloads();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Single-track flow
    // ─────────────────────────────────────────────────────────────────────────

    public function fetchSingleTrack(): void
    {
        $this->validate(['url' => 'required|url']);

        $this->loading = true;
        $this->previewing = false;
        $this->singleMode = false;
        $this->singleTrackInfo = [];
        $this->previewTracks = [];
        $this->selectedTracks = [];
        $this->errorMessage = null;

        try {
            $service = app(YouTubeDownloadService::class);
            $info = $service->getSingleTrackInfo($this->url);

            $this->singleTrackInfo = $info;
            $this->singleMode = true;
            $this->playlistUrl = $info['url'];
            $this->dispatch('notify', 'Canción encontrada: ' . Str::limit($info['title'], 40));
        } catch (\Exception $e) {
            $this->errorMessage = $this->humanizeError($e->getMessage());
        }

        $this->loading = false;
    }

    public function startSingleDownload(): void
    {
        if (empty($this->singleTrackInfo)) return;

        $track = $this->singleTrackInfo;
        $id = md5($track['url']);

        Redis::hset('download_status', $id, json_encode([
            'title'          => $track['title'],
            'status'         => 'queued',
            'progress'       => 0,
            'playlist_folder'=> '',
            'audio_format'   => $this->audioFormat,
            'audio_bitrate'  => $this->audioBitrate,
        ]));

        DownloadTrackJob::dispatch($track['url'], $track['title'], '', $this->audioFormat, $this->audioBitrate);

        $this->singleTrackInfo = [];
        $this->singleMode = false;
        $this->url = '';
        $this->playlistUrl = '';
        $this->dispatch('notify', '¡Descarga iniciada!');
        $this->fetchDownloads();
    }

    public function cancelSingle(): void
    {
        $this->singleTrackInfo = [];
        $this->singleMode = false;
        $this->url = '';
        $this->playlistUrl = '';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Playlist flow
    // ─────────────────────────────────────────────────────────────────────────

    public function fetchPlaylist(): void
    {
        $this->validate(['url' => 'required|url']);

        $this->loading = true;
        $this->previewing = false;
        $this->singleMode = false;
        $this->singleTrackInfo = [];
        $this->previewTracks = [];
        $this->selectedTracks = [];
        $this->errorMessage = null;

        try {
            $service = app(YouTubeDownloadService::class);

            $this->playlistName = $service->getPlaylistTitle($this->url);
            $tracks = $service->getPlaylistInfo($this->url);

            $this->previewTracks = [];
            foreach ($tracks as $track) {
                $url = $track['webpage_url'] ?? $track['url'] ?? null;
                if (!$url) continue;

                $this->previewTracks[] = [
                    'title'    => $track['title'] ?? 'Sin título',
                    'url'      => $url,
                    'duration' => $track['duration'] ?? null,
                    'uploader' => $track['uploader'] ?? $track['channel'] ?? '',
                ];
            }

            if (empty($this->previewTracks)) {
                $this->errorMessage = 'No se encontraron canciones en la playlist. Verificá que la URL sea correcta y que la playlist sea pública.';
                $this->loading = false;
                return;
            }

            // Select all tracks by default
            $this->selectedTracks = array_keys($this->previewTracks);

            $this->playlistUrl = $this->url;
            $this->url = '';
            $this->previewing = true;
            $this->dispatch('notify', count($this->previewTracks) . ' canciones encontradas');
        } catch (\Exception $e) {
            $this->errorMessage = $this->humanizeError($e->getMessage());
        }

        $this->loading = false;
    }

    // Track selection helpers
    public function toggleTrack(int $index): void
    {
        if (in_array($index, $this->selectedTracks)) {
            $this->selectedTracks = array_values(array_filter(
                $this->selectedTracks,
                fn($i) => $i !== $index
            ));
        } else {
            $this->selectedTracks[] = $index;
        }
    }

    public function selectAll(): void
    {
        $this->selectedTracks = array_keys($this->previewTracks);
    }

    public function deselectAll(): void
    {
        $this->selectedTracks = [];
    }

    public function startDownload(): void
    {
        if (empty($this->previewTracks) || empty($this->selectedTracks)) return;

        $playlistFolder = Str::slug($this->playlistName, '_');
        if (empty($playlistFolder)) {
            $playlistFolder = 'playlist_' . date('Y_m_d_His');
        }

        $tracksToDownload = array_intersect_key(
            $this->previewTracks,
            array_flip($this->selectedTracks)
        );

        foreach ($tracksToDownload as $track) {
            $id = md5($track['url']);
            Redis::hset('download_status', $id, json_encode([
                'title'          => $track['title'],
                'status'         => 'queued',
                'progress'       => 0,
                'playlist_folder'=> $playlistFolder,
                'audio_format'   => $this->audioFormat,
                'audio_bitrate'  => $this->audioBitrate,
            ]));

            DownloadTrackJob::dispatch($track['url'], $track['title'], $playlistFolder, $this->audioFormat, $this->audioBitrate);
        }

        $count = count($tracksToDownload);
        $this->previewTracks = [];
        $this->selectedTracks = [];
        $this->previewing = false;
        $this->playlistUrl = '';
        $this->dispatch('notify', "¡{$count} descargas iniciadas!");
        $this->fetchDownloads();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // YouTube Search
    // ─────────────────────────────────────────────────────────────────────────

    public function searchYouTube(): void
    {
        $this->validate(['searchQuery' => 'required|min:2']);

        $this->searching = true;
        $this->searchResults = [];
        $this->searchError = null;

        try {
            $service = app(YouTubeDownloadService::class);
            $this->searchResults = $service->searchVideos($this->searchQuery);

            if (empty($this->searchResults)) {
                $this->searchError = 'No se encontraron resultados para "' . $this->searchQuery . '"';
            }
        } catch (\Exception $e) {
            $this->searchError = 'Error al buscar: ' . Str::limit($e->getMessage(), 100);
        }

        $this->searching = false;
    }

    public function downloadSearchResult(string $url, string $title): void
    {
        $id = md5($url);
        Redis::hset('download_status', $id, json_encode([
            'title'          => $title,
            'status'         => 'queued',
            'progress'       => 0,
            'playlist_folder'=> '',
            'audio_format'   => $this->audioFormat,
            'audio_bitrate'  => $this->audioBitrate,
        ]));

        DownloadTrackJob::dispatch($url, $title, '', $this->audioFormat, $this->audioBitrate);
        $this->dispatch('notify', 'Descarga agregada: ' . Str::limit($title, 35));
        $this->fetchDownloads();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Common controls
    // ─────────────────────────────────────────────────────────────────────────

    public function stopDownloads(): void
    {
        $statuses = Redis::hgetall('download_status');
        foreach ($statuses as $id => $json) {
            $data = json_decode($json, true);
            if (in_array($data['status'], ['queued', 'downloading'])) {
                $data['status'] = 'stopped';
                Redis::hset('download_status', $id, json_encode($data));
            }
        }

        Redis::del('queues:default');
        $this->dispatch('notify', 'Descargas detenidas');
        $this->fetchDownloads();
    }

    public function cancelPreview(): void
    {
        $this->previewTracks = [];
        $this->selectedTracks = [];
        $this->previewing = false;
        $this->playlistUrl = '';
        $this->playlistName = '';
    }

    public function clearAll(): void
    {
        Redis::del('download_status');
        Redis::del('current_playlist_name');
        Redis::del('current_playlist_folder');
        $this->downloads = [];
        $this->playingTrack = null;
        $this->playlistName = '';

        $dir = storage_path('app/downloads');
        if (is_dir($dir)) {
            $patterns = ['*.mp3', '*.flac', '*.ogg', '*.part', '*.temp', '*.webm', '*.m4a', '*.opus', '*.zip', '*.tmp', '*.ytdl'];
            foreach ($patterns as $pattern) {
                foreach (glob($dir . '/' . $pattern) as $file) {
                    @unlink($file);
                }
            }
            $subdirs = glob($dir . '/*', GLOB_ONLYDIR);
            foreach ($subdirs as $subdir) {
                foreach ($patterns as $pattern) {
                    foreach (glob($subdir . '/' . $pattern) as $file) {
                        @unlink($file);
                    }
                }
                @rmdir($subdir);
            }
        }
    }

    public function playTrack(string $id): void
    {
        $this->playingTrack = $this->playingTrack === $id ? null : $id;
    }

    public function loadExistingFiles(): void
    {
        $dir = storage_path('app/downloads');
        if (!is_dir($dir)) return;

        $this->scanDirectoryForFiles($dir, '');

        $subdirs = glob($dir . '/*', GLOB_ONLYDIR);
        foreach ($subdirs as $subdir) {
            $this->scanDirectoryForFiles($subdir, basename($subdir));
        }

        $this->fetchDownloads();
    }

    private function scanDirectoryForFiles(string $dir, string $subfolder): void
    {
        foreach (['mp3', 'flac', 'ogg'] as $ext) {
            foreach (glob($dir . '/*.' . $ext) as $file) {
                $filename = basename($file);
                $relativePath = !empty($subfolder) ? $subfolder . '/' . $filename : $filename;
                $id = md5($relativePath);
                if (!Redis::hexists('download_status', $id)) {
                    $title = str_replace(['.' . $ext, '_', '-'], ['', ' ', ' '], $filename);
                    Redis::hset('download_status', $id, json_encode([
                        'title'          => ucwords(trim($title)),
                        'status'         => 'completed',
                        'progress'       => 100,
                        'filename'       => $relativePath,
                        'playlist_folder'=> $subfolder,
                        'audio_format'   => $ext,
                    ]));
                }
            }
        }
    }

    public function fetchDownloads(): void
    {
        $statuses = Redis::hgetall('download_status');
        $this->downloads = array_map(fn($item) => json_decode($item, true), $statuses);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function humanizeError(string $errorMsg): string
    {
        if (str_contains($errorMsg, 'yt-dlp: not found') || str_contains($errorMsg, 'No such file or directory')) {
            return 'El servicio de descarga (yt-dlp) no está disponible. Contactá al administrador del sistema.';
        } elseif (str_contains($errorMsg, 'is not a valid URL') || str_contains($errorMsg, 'Unsupported URL')) {
            return 'La URL ingresada no es válida o no es compatible con YouTube.';
        } elseif (str_contains($errorMsg, 'Private video') || str_contains($errorMsg, 'This video is unavailable')) {
            return 'El video/playlist es privado o no está disponible.';
        } elseif (str_contains($errorMsg, 'Sign in') || str_contains($errorMsg, 'age-restricted')) {
            return 'Este video requiere inicio de sesión o tiene restricción de edad.';
        } elseif (str_contains($errorMsg, 'Timed out') || str_contains($errorMsg, 'timed out')) {
            return 'La búsqueda tardó demasiado. Intentá de nuevo.';
        } else {
            // Show a real excerpt of the error, trimmed to be readable
            $clean = preg_replace('/\[download\].*\n?/', '', $errorMsg);
            return 'Error: ' . \Illuminate\Support\Str::limit(trim($clean), 200);
        }
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
