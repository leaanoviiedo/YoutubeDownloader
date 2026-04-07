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

    // Audio format selector
    public string $audioFormat = 'mp3';

    public function mount(): void
    {
        // Recover playlist name from Redis
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
        $safeTitle = Str::slug($track['title'], '_') ?: 'track_' . $id;

        Redis::hset('download_status', $id, json_encode([
            'title'          => $track['title'],
            'status'         => 'queued',
            'progress'       => 0,
            'playlist_folder'=> '',
            'audio_format'   => $this->audioFormat,
        ]));

        DownloadTrackJob::dispatch($track['url'], $track['title'], '', $this->audioFormat);

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
        $this->errorMessage = null;

        try {
            $service = app(YouTubeDownloadService::class);

            // Get playlist title first
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

            $this->playlistUrl = $this->url;
            $this->url = '';
            $this->previewing = true;
            $this->dispatch('notify', count($this->previewTracks) . ' canciones encontradas');
        } catch (\Exception $e) {
            $this->errorMessage = $this->humanizeError($e->getMessage());
        }

        $this->loading = false;
    }

    public function startDownload(): void
    {
        if (empty($this->previewTracks)) return;

        $playlistFolder = Str::slug($this->playlistName, '_');
        if (empty($playlistFolder)) {
            $playlistFolder = 'playlist_' . date('Y_m_d_His');
        }

        foreach ($this->previewTracks as $track) {
            $id = md5($track['url']);
            Redis::hset('download_status', $id, json_encode([
                'title'          => $track['title'],
                'status'         => 'queued',
                'progress'       => 0,
                'playlist_folder'=> $playlistFolder,
                'audio_format'   => $this->audioFormat,
            ]));
        }

        DownloadPlaylistJob::dispatch($this->playlistUrl, $this->audioFormat);

        $this->previewTracks = [];
        $this->previewing = false;
        $this->playlistUrl = '';
        $this->dispatch('notify', '¡Descargas iniciadas!');
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

    public function downloadZip(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $dir = storage_path('app/downloads');
        $playlistFolder = Redis::get('current_playlist_folder') ?? '';
        $playlistName = Redis::get('current_playlist_name') ?? 'playlist';
        $zipName = Str::slug($playlistName, '_') . '.zip';

        $searchDir = !empty($playlistFolder) ? $dir . '/' . $playlistFolder : $dir;
        $zipPath = $dir . '/' . $zipName;

        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        foreach ($this->downloads as $item) {
            if ($item['status'] === 'completed' && isset($item['filename'])) {
                $filePath = $dir . '/' . $item['filename'];
                if (file_exists($filePath)) {
                    $zip->addFile($filePath, basename($item['filename']));
                }
            }
        }

        $zip->close();

        return response()->download($zipPath, $zipName)->deleteFileAfterSend();
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
            $folderName = basename($subdir);
            $this->scanDirectoryForFiles($subdir, $folderName);
        }

        $this->fetchDownloads();
    }

    private function scanDirectoryForFiles(string $dir, string $subfolder): void
    {
        $extensions = ['mp3', 'flac', 'ogg'];
        foreach ($extensions as $ext) {
            $files = glob($dir . '/*.' . $ext);
            foreach ($files as $file) {
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
        if (str_contains($errorMsg, 'not found') || str_contains($errorMsg, 'No such file')) {
            return 'El servicio de descarga (yt-dlp) no está disponible. Contactá al administrador del sistema.';
        } elseif (str_contains($errorMsg, 'is not a valid URL') || str_contains($errorMsg, 'Unsupported URL')) {
            return 'La URL ingresada no es válida o no es compatible con YouTube.';
        } elseif (str_contains($errorMsg, 'HTTP Error') || str_contains($errorMsg, 'network') || str_contains($errorMsg, 'URLError')) {
            return 'Error de conexión. Verificá tu conexión a internet e intentá de nuevo.';
        } elseif (str_contains($errorMsg, 'Private') || str_contains($errorMsg, 'unavailable')) {
            return 'El video/playlist es privado o no está disponible.';
        } else {
            return 'Ocurrió un error. Detalle: ' . Str::limit($errorMsg, 150);
        }
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
