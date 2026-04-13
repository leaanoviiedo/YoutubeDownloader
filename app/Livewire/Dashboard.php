<?php

namespace App\Livewire;

use App\Jobs\DownloadTrackJob;
use App\Services\YouTubeDownloadService;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Livewire\Component;

class Dashboard extends Component
{
    public string $url = '';
    public string $searchQuery = '';
    public string $activeTab = 'download'; // download | search
    public string $audioFormat = 'mp3';
    public string $audioBitrate = '0'; // 0 means default/auto

    // Info de la playlist o cancion única
    public ?string $playlistTitle = null;
    public array $previewTracks = [];
    public bool $previewing = false;

    // Search results
    public array $searchResults = [];
    public bool $searching = false;

    // Track selection
    public array $selectedTracks = [];

    // Errores
    public ?string $errorMsg = null;

    protected $listeners = [
        'downloadComplete' => 'fetchDownloads'
    ];

    public function mount(): void
    {
        $this->fetchDownloads();
    }

    public function updatedActiveTab(): void
    {
        $this->errorMsg = null;
        if ($this->activeTab === 'playlist') {
            $this->searchResults = [];
            $this->searchQuery = '';
        } else {
            $this->previewTracks = [];
            $this->url = '';
            $this->previewing = false;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Single Track Preview (strips list param, shows info panel like playlist)
    // ─────────────────────────────────────────────────────────────────────────

    public function fetchSingleTrack(): void
    {
        $this->validate(['url' => 'required|url'], [
            'url.required' => 'Ingresá una URL de YouTube',
            'url.url'      => 'El formato de la URL no es válido',
        ]);

        $this->errorMsg = null;
        $this->previewing = true;
        $this->previewTracks = [];
        $this->selectedTracks = [];
        $this->playlistTitle = null;

        // No eliminamos el parámetro list para que sea tratada como playlist
        $cleanUrl = $this->url;

        try {
            $service = app(YouTubeDownloadService::class);
            $tracks = $service->getPlaylistInfo($cleanUrl);
            if (empty($tracks)) {
                throw new \Exception("No se encontró información de la canción.");
            }
            // Tomamos solo la primera de la lista
            $info = $tracks[0];
            
            if (empty($info['url']) && !empty($info['webpage_url'])) {
                $info['url'] = $info['webpage_url'];
            } elseif (empty($info['url']) && !empty($info['id'])) {
                $info['url'] = 'https://www.youtube.com/watch?v=' . $info['id'];
            } else {
                $info['url'] = $cleanUrl;
            }

            $this->playlistTitle = $info['title'] ?? 'Video';
            $this->previewTracks = [$info];
            $this->selectedTracks = [0];
        } catch (\Exception $e) {
            $this->errorMsg = $this->humanizeError($e->getMessage());
            $this->previewing = false;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Search Mode
    // ─────────────────────────────────────────────────────────────────────────

    public function performSearch(): void
    {
        $this->validate(['searchQuery' => 'required|min:2'], [
            'searchQuery.required' => 'Ingresá al menos una palabra para buscar',
            'searchQuery.min' => 'Ingresá al menos 2 caracteres'
        ]);

        $this->searching = true;
        $this->searchResults = [];
        $this->errorMsg = null;

        try {
            $service = app(YouTubeDownloadService::class);
            $this->searchResults = $service->searchVideos($this->searchQuery, 15);
        } catch (\Exception $e) {
            $this->errorMsg = $this->humanizeError($e->getMessage());
        }

        $this->searching = false;
    }

    public function downloadSearchResult(string $url, string $title): void
    {
        try {
            $service = app(YouTubeDownloadService::class);
            $info = $service->getSingleTrackInfo($url);

            $trackData = [
                'url'        => $url,
                'title'      => $info['title'] ?? $title,
                'duration'   => $info['duration'] ?? null,
                'thumbnail'  => $info['thumbnail'] ?? null,
                'uploader'   => $info['uploader'] ?? '',
                'view_count' => $info['view_count'] ?? null,
            ];

            $this->queueTrack($trackData, 'Busquedas');
            $this->dispatch('notify', '🎵 Descargando: ' . ($trackData['title']));
        } catch (\Exception $e) {
            $this->errorMsg = "Error al descargar: " . $this->humanizeError($e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // URL Mode (Playlist or Single)
    // ─────────────────────────────────────────────────────────────────────────

    public function enablePreview(): void
    {
        $this->validate(['url' => 'required|url'], [
            'url.required' => 'Ingresá una URL de YouTube',
            'url.url' => 'El formato de la URL no es válido'
        ]);

        $this->previewing = true;
        $this->errorMsg = null;
        $this->previewTracks = [];
        $this->selectedTracks = [];
        $this->playlistTitle = null;

        try {
            $service = app(YouTubeDownloadService::class);

            if (str_contains($this->url, 'list=')) {
                $this->playlistTitle = $service->getPlaylistTitle($this->url);
                $this->previewTracks = $service->getPlaylistInfo($this->url);
            } else {
                $this->playlistTitle = 'Video Único';
                $tracks = $service->getPlaylistInfo($this->url);
                if (!empty($tracks)) {
                    $info = $tracks[0];
                    if (empty($info['url']) && !empty($info['webpage_url'])) {
                        $info['url'] = $info['webpage_url'];
                    } elseif (empty($info['url']) && !empty($info['id'])) {
                        $info['url'] = 'https://www.youtube.com/watch?v=' . $info['id'];
                    } else {
                        $info['url'] = $this->url;
                    }
                    $this->previewTracks = [$info];
                } else {
                    $this->previewTracks = [];
                }
            }

            // Seleccionar todas por defecto
            foreach ($this->previewTracks as $i => $track) {
                if (!empty($track['title']) && $track['title'] !== '[Deleted video]') {
                    $this->selectedTracks[] = $i;
                }
            }

        } catch (\Exception $e) {
            $this->errorMsg = $this->humanizeError($e->getMessage());
            $this->previewing = false;
        }
    }

    public function selectAllTracks(): void
    {
        $this->selectedTracks = [];
        foreach ($this->previewTracks as $i => $track) {
            if (!empty($track['title']) && $track['title'] !== '[Deleted video]' && $track['title'] !== '[Private video]') {
                $this->selectedTracks[] = $i;
            }
        }
    }

    public function deselectAllTracks(): void
    {
        $this->selectedTracks = [];
    }

    public function toggleTrack(int $index): void
    {
        if (in_array($index, $this->selectedTracks)) {
            $this->selectedTracks = array_diff($this->selectedTracks, [$index]);
            $this->selectedTracks = array_values($this->selectedTracks);
        } else {
            $this->selectedTracks[] = $index;
            $this->selectedTracks = array_values($this->selectedTracks);
        }
    }

    public function processSelected(): void
    {
        if (empty($this->selectedTracks)) {
            $this->errorMsg = "Por favor, seleccioná al menos una canción para descargar.";
            return;
        }

        $playlistFolder = Str::slug($this->playlistTitle ?? 'Descargas');
        Redis::set('current_playlist_folder', $playlistFolder);
        Redis::set('current_playlist_name', $this->playlistTitle ?? 'Descargas');

        $count = 0;
        foreach ($this->selectedTracks as $index) {
            if (isset($this->previewTracks[$index])) {
                $track = $this->previewTracks[$index];
                $track['id'] = (string) Str::uuid();
                
                // Fallback for flat-playlist missing standard url key
                if (empty($track['url']) && !empty($track['webpage_url'])) {
                    $track['url'] = $track['webpage_url'];
                } elseif (empty($track['url']) && !empty($track['id']) && strlen($track['id']) === 11) {
                    $track['url'] = 'https://www.youtube.com/watch?v=' . $track['id'];
                } elseif (empty($track['url'])) {
                    $track['url'] = $this->url;
                }

                if (empty($track['title']) || $track['title'] === '[Deleted video]' || $track['title'] === '[Private video]') {
                    continue;
                }

                $this->queueTrack($track, $playlistFolder);
                $count++;
            }
        }

        $this->dispatch('notify', "📥 $count descargas agregadas a la cola");
        $this->previewing = false;
        $this->previewTracks = [];
        $this->selectedTracks = [];
        $this->url = '';
        $this->fetchDownloads();
    }

    private function queueTrack(array $trackData, string $folder): void
    {
        $trackUrl   = $trackData['url'] ?? $trackData['webpage_url'] ?? '';
        $trackTitle = $trackData['title'] ?? 'Sin título';

        // Use md5(url) as ID — SAME as what DownloadTrackJob uses internally
        // This ensures both refer to the same Redis key (no duplicate entries)
        $id = md5($trackUrl);

        Redis::hset('download_status', $id, json_encode([
            'id'        => $id,
            'title'     => $trackTitle,
            'status'    => 'queued',
            'progress'  => 0,
            'added_at'  => now()->timestamp,
            'format'    => strtoupper($this->audioFormat),
            'thumbnail' => $trackData['thumbnail'] ?? null,
            'duration'  => $trackData['duration'] ?? null,
            'channel'   => $trackData['uploader'] ?? $trackData['channel'] ?? null,
        ]));

        // Dispatch with url + title strings (matches Job constructor signature)
        DownloadTrackJob::dispatch(
            $trackUrl,
            $trackTitle,
            $folder,
            $this->audioFormat,
            $this->audioBitrate
        );
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
    }

    public function fetchDownloads(): void
    {
        // Polling para mantener UI actualizada si hay un componente que lo requiere
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function humanizeError(string $errorMsg): string
    {
        if (str_contains($errorMsg, 'yt-dlp: not found') || str_contains($errorMsg, 'No such file or directory')) {
            return 'El servicio de descarga (yt-dlp) no está disponible. Contactá al administrador.';
        } elseif (str_contains($errorMsg, 'is not a valid URL') || str_contains($errorMsg, 'Unsupported URL')) {
            return 'La URL ingresada no es válida o no es compatible con YouTube.';
        } elseif (str_contains($errorMsg, 'Private video') || str_contains($errorMsg, 'This video is unavailable')) {
            return 'El video/playlist es privado o no está disponible.';
        } elseif (str_contains($errorMsg, 'Sign in') || str_contains($errorMsg, 'bot') || str_contains($errorMsg, 'cookies') || str_contains($errorMsg, 'confirm you')) {
            return '⚠️ YouTube está bloqueando el servidor por detección de bots. Solución: exportá tus cookies de YouTube con la extensión "Get cookies.txt LOCALLY" y subí el archivo como storage/app/youtube_cookies.txt en el servidor.';
        } elseif (str_contains($errorMsg, 'age-restricted')) {
            return 'Este video tiene restricción de edad. Necesitás configurar cookies de una cuenta de YouTube verificada. Subile un archivo cookies.txt';
        } elseif (str_contains($errorMsg, 'Timed out') || str_contains($errorMsg, 'timed out')) {
            return 'La búsqueda tardó demasiado. Intentá de nuevo o usá "Buscar Playlist" que es más rápido.';
        } else {
            $clean = preg_replace('/\[download\].*\n?/', '', $errorMsg);
            return 'Error: ' . \Illuminate\Support\Str::limit(trim($clean), 200);
        }
    }

    public function render()
    {
        return view('livewire.dashboard')->layout('components.layouts.app');
    }
}
