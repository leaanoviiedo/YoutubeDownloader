<?php

namespace App\Livewire;

use App\Services\AudioEditingService;
use Illuminate\Support\Facades\Redis;
use Livewire\Component;
use Livewire\WithFileUploads;

class AudioEditor extends Component
{
    use WithFileUploads;

    public array $downloadedFiles = [];
    public string $tool = 'trim';
    
    // Upload local file
    public $localAudio;


    // Trim
    public string $trimFile = '';
    public string $trimStart = '00:00:00';
    public string $trimEnd = '';
    public ?string $trimResult = null;
    public ?string $trimError = null;
    public bool $trimming = false;

    // Convert
    public string $convertFile = '';
    public string $convertFormat = 'mp3';
    public string $convertBitrate = '0';
    public ?string $convertResult = null;
    public ?string $convertError = null;
    public bool $converting = false;

    // Normalize
    public string $normalizeFile = '';
    public ?string $normalizeResult = null;
    public ?string $normalizeError = null;
    public bool $normalizing = false;

    // Player
    public ?string $playingFile = null;

    public function mount(): void
    {
        $this->loadFiles();
    }

    public function updatedLocalAudio(): void
    {
        $this->validate([
            'localAudio' => 'required|file|max:51200|mimes:mp3,wav,ogg,flac,aac,m4a' // max 50MB
        ], [
            'localAudio.max' => 'El archivo supera el límite de 50MB.',
            'localAudio.mimes' => 'El formato debe ser de audio (mp3, wav, etc).'
        ]);

        try {
            $name = $this->localAudio->getClientOriginalName();
            $safeName = \Illuminate\Support\Str::slug(pathinfo($name, PATHINFO_FILENAME)) . '.' . pathinfo($name, PATHINFO_EXTENSION);
            
            // Move to local uploads folder
            $this->localAudio->storeAs('downloads/Locales', $safeName);
            
            $this->localAudio = null;
            $this->loadFiles();
            $this->dispatch('notify', '✅ Audio local subido correctemente: ' . $safeName);
        } catch (\Exception $e) {
            $this->dispatch('notify', '❌ Error al subir: ' . $e->getMessage());
        }
    }

    public function loadFiles(): void
    {
        $dir = storage_path('app/downloads');
        $files = [];

        foreach (['mp3', 'flac', 'ogg', 'wav', 'aac', 'm4a'] as $ext) {
            foreach (glob($dir . '/*.' . $ext) ?: [] as $f) {
                $files[] = basename($f);
            }
            foreach (glob($dir . '/*/*.' . $ext) ?: [] as $f) {
                $files[] = substr($f, strlen($dir) + 1);
            }
        }

        $statuses = Redis::hgetall('download_status');
        foreach ($statuses as $json) {
            $data = json_decode($json, true);
            if (($data['status'] ?? '') === 'completed' && isset($data['filename'])) {
                $fn   = $data['filename'];
                $full = $dir . '/' . $fn;
                if (!in_array($fn, $files) && file_exists($full)) {
                    $files[] = $fn;
                }
            }
        }

        $this->downloadedFiles = array_values(array_unique($files));
        sort($this->downloadedFiles);
    }

    public function runTrim(): void
    {
        $this->validate(['trimFile' => 'required', 'trimStart' => 'required', 'trimEnd' => 'required']);
        $this->trimming = true;
        $this->trimResult = null;
        $this->trimError = null;

        try {
            $svc     = app(AudioEditingService::class);
            $base    = pathinfo($this->trimFile, PATHINFO_FILENAME);
            $ext     = strtolower(pathinfo($this->trimFile, PATHINFO_EXTENSION));
            $outName = $base . '_trim_' . now()->format('His') . '.' . $ext;

            $svc->trim($this->trimFile, $this->trimStart, $this->trimEnd, $outName);
            $this->trimResult = $outName;
            $this->loadFiles();
            $this->dispatch('notify', '✂️ Audio recortado correctamente');
        } catch (\Exception $e) {
            $this->trimError = $e->getMessage();
        }

        $this->trimming = false;
    }

    public function runConvert(): void
    {
        $this->validate(['convertFile' => 'required']);
        $this->converting = true;
        $this->convertResult = null;
        $this->convertError = null;

        try {
            $svc     = app(AudioEditingService::class);
            $base    = pathinfo($this->convertFile, PATHINFO_FILENAME);
            $outName = $base . '_.' . $this->convertFormat;

            $svc->convert($this->convertFile, $this->convertFormat, $outName, $this->convertBitrate);
            $this->convertResult = $outName;
            $this->loadFiles();
            $this->dispatch('notify', '🔄 Convertido a ' . strtoupper($this->convertFormat));
        } catch (\Exception $e) {
            $this->convertError = $e->getMessage();
        }

        $this->converting = false;
    }

    public function runNormalize(): void
    {
        $this->validate(['normalizeFile' => 'required']);
        $this->normalizing = true;
        $this->normalizeResult = null;
        $this->normalizeError = null;

        try {
            $svc     = app(AudioEditingService::class);
            $base    = pathinfo($this->normalizeFile, PATHINFO_FILENAME);
            $ext     = strtolower(pathinfo($this->normalizeFile, PATHINFO_EXTENSION));
            $outName = $base . '_norm_' . now()->format('His') . '.' . $ext;

            $svc->normalize($this->normalizeFile, $outName);
            $this->normalizeResult = $outName;
            $this->loadFiles();
            $this->dispatch('notify', '🔊 Audio normalizado correctamente');
        } catch (\Exception $e) {
            $this->normalizeError = $e->getMessage();
        }

        $this->normalizing = false;
    }

    public function playFile(string $filename): void
    {
        $this->playingFile = $this->playingFile === $filename ? null : $filename;
    }

    public function render()
    {
        return view('livewire.audio-editor')->layout('components.layouts.app');
    }
}
