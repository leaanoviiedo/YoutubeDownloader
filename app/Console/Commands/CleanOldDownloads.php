<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class CleanOldDownloads extends Command
{
    protected $signature = 'downloads:clean {--days= : Days to keep downloads} {--hours= : Hours to keep downloads}';
    protected $description = 'Elimina descargas de audio y carpetas vacías según antigüedad';

    public function handle(): int
    {
        $dir = storage_path('app/downloads');

        if (!is_dir($dir)) {
            $this->info('No hay directorio de descargas.');
            return 0;
        }

        $cutoff = now();
        if ($this->option('hours')) {
            $cutoff = $cutoff->subHours((int) $this->option('hours'));
        } elseif ($this->option('days')) {
            $cutoff = $cutoff->subDays((int) $this->option('days'));
        } else {
            // Predeterminado: 2 horas
            $cutoff = $cutoff->subHours(2);
        }
        $cutoffTimestamp = $cutoff->timestamp;
        $deleted = 0;

        // Clean root directory
        $deleted += $this->cleanDirectory($dir, $cutoffTimestamp);

        // Clean playlist subdirectories
        $subdirs = glob($dir . '/*', GLOB_ONLYDIR);
        foreach ($subdirs as $subdir) {
            $deleted += $this->cleanDirectory($subdir, $cutoffTimestamp);

            // Remove empty directories
            if (count(glob($subdir . '/*')) === 0) {
                @rmdir($subdir);
            }
        }

        // Las entradas de Redis ahora tienen TTL (expiran solas en 2 horas)
        // por lo que no es necesario limpiarlas manualmente de Redis aquí.

        $this->info("Eliminados {$deleted} archivos más antiguos que " . $cutoff->format('Y-m-d H:i:s'));
        return 0;
    }

    private function cleanDirectory(string $dir, int $cutoffTimestamp): int
    {
        $deleted = 0;
        $files = [];
        foreach (['mp3', 'flac', 'ogg', 'wav', 'aac', 'm4a'] as $ext) {
            $files = array_merge($files, glob($dir . '/*.' . $ext) ?: []);
        }

        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTimestamp) {
                @unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }
}
