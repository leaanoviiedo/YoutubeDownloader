<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class AudioEditingService
{
    private string $downloadsDir;

    public function __construct()
    {
        $this->downloadsDir = storage_path('app/downloads');
    }

    public function resolvePath(string $filename): string
    {
        return $this->downloadsDir . '/' . ltrim($filename, '/');
    }

    /** Trim audio between start and end (HH:MM:SS format) */
    public function trim(string $inputFile, string $start, string $end, string $outputFile): void
    {
        $in  = $this->resolvePath($inputFile);
        $out = $this->resolvePath($outputFile);

        if (!file_exists($in)) throw new \RuntimeException("Archivo no encontrado: $inputFile");

        $this->ensureDir(dirname($out));

        $p = new Process(['ffmpeg', '-y', '-i', $in, '-ss', $start, '-to', $end, '-c', 'copy', $out]);
        $p->setTimeout(300);
        $p->run();

        if (!$p->isSuccessful() || !file_exists($out)) {
            throw new \RuntimeException('Error al recortar: ' . $p->getErrorOutput());
        }
    }

    /** Convert audio to target format (mp3|flac|ogg|wav|aac) */
    public function convert(string $inputFile, string $targetFormat, string $outputFile, string $bitrate = '0'): void
    {
        $in  = $this->resolvePath($inputFile);
        $out = $this->resolvePath($outputFile);

        if (!file_exists($in)) throw new \RuntimeException("Archivo no encontrado: $inputFile");

        $this->ensureDir(dirname($out));

        $codec = match ($targetFormat) {
            'flac' => ['flac'],
            'ogg'  => ['libvorbis'],
            'wav'  => ['pcm_s16le'],
            'aac', 'm4a' => ['aac'],
            default      => ['libmp3lame'],
        };

        $args = ['ffmpeg', '-y', '-i', $in, '-codec:a', ...$codec];

        if (!in_array($targetFormat, ['flac', 'wav']) && $bitrate !== '0') {
            $args = [...$args, '-b:a', $bitrate];
        }

        $args[] = $out;

        $p = new Process($args);
        $p->setTimeout(600);
        $p->run();

        if (!$p->isSuccessful() || !file_exists($out)) {
            throw new \RuntimeException('Error al convertir: ' . $p->getErrorOutput());
        }
    }

    /** Normalize audio volume using EBU R128 (two-pass loudnorm) */
    public function normalize(string $inputFile, string $outputFile): void
    {
        $in  = $this->resolvePath($inputFile);
        $out = $this->resolvePath($outputFile);

        if (!file_exists($in)) throw new \RuntimeException("Archivo no encontrado: $inputFile");

        $this->ensureDir(dirname($out));

        // Pass 1: analyze
        $a = new Process(['ffmpeg', '-y', '-i', $in, '-af', 'loudnorm=I=-16:TP=-1.5:LRA=11:print_format=json', '-f', 'null', '-']);
        $a->setTimeout(300);
        $a->run();

        preg_match('/\{[^}]+\}/s', $a->getErrorOutput(), $m);
        $stats = isset($m[0]) ? json_decode($m[0], true) : null;

        $filter = $stats
            ? sprintf('loudnorm=I=-16:TP=-1.5:LRA=11:measured_I=%s:measured_TP=%s:measured_LRA=%s:measured_thresh=%s:offset=%s:linear=true',
                $stats['input_i'] ?? '-23', $stats['input_tp'] ?? '-2',
                $stats['input_lra'] ?? '7', $stats['input_thresh'] ?? '-33',
                $stats['target_offset'] ?? '0')
            : 'loudnorm=I=-16:TP=-1.5:LRA=11';

        $ext   = strtolower(pathinfo($in, PATHINFO_EXTENSION));
        $codec = match ($ext) {
            'flac' => ['-codec:a', 'flac'],
            'ogg'  => ['-codec:a', 'libvorbis', '-q:a', '6'],
            default=> ['-codec:a', 'libmp3lame', '-q:a', '0'],
        };

        $p = new Process(['ffmpeg', '-y', '-i', $in, '-af', $filter, ...$codec, $out]);
        $p->setTimeout(600);
        $p->run();

        if (!$p->isSuccessful() || !file_exists($out)) {
            throw new \RuntimeException('Error al normalizar: ' . $p->getErrorOutput());
        }
    }

    /** Get duration and basic info via ffprobe */
    public function getInfo(string $filename): array
    {
        $path = $this->resolvePath($filename);
        if (!file_exists($path)) return [];

        $p = new Process(['ffprobe', '-v', 'quiet', '-print_format', 'json', '-show_format', $path]);
        $p->setTimeout(10);
        $p->run();

        if (!$p->isSuccessful()) return [];

        $data   = json_decode($p->getOutput(), true);
        $format = $data['format'] ?? [];

        return [
            'duration'   => isset($format['duration']) ? round((float)$format['duration'], 2) : null,
            'size_bytes' => isset($format['size']) ? (int)$format['size'] : null,
            'bitrate'    => isset($format['bit_rate']) ? (int)$format['bit_rate'] : null,
        ];
    }

    private function ensureDir(string $dir): void
    {
        if (!is_dir($dir)) mkdir($dir, 0755, true);
    }
}
