<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class CleanOldDownloads extends Command
{
    protected $signature = 'downloads:clean {--days=1 : Days to keep downloads}';
    protected $description = 'Elimina descargas con más de X días de antigüedad';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dir = storage_path('app/downloads');

        if (!is_dir($dir)) {
            $this->info('No hay directorio de descargas.');
            return 0;
        }

        $cutoff = now()->subDays($days)->timestamp;
        $deleted = 0;

        // Clean root directory
        $deleted += $this->cleanDirectory($dir, $cutoff);

        // Clean playlist subdirectories
        $subdirs = glob($dir . '/*', GLOB_ONLYDIR);
        foreach ($subdirs as $subdir) {
            $deleted += $this->cleanDirectory($subdir, $cutoff);

            // Remove empty directories
            if (count(glob($subdir . '/*')) === 0) {
                @rmdir($subdir);
            }
        }

        // Clean stale Redis entries
        $statuses = Redis::hgetall('download_status');
        foreach ($statuses as $id => $json) {
            $data = json_decode($json, true);
            if ($data['status'] === 'completed' && isset($data['filename'])) {
                $path = $dir . '/' . $data['filename'];
                if (!file_exists($path)) {
                    Redis::hdel('download_status', $id);
                }
            }
        }

        $this->info("Eliminados {$deleted} archivos con más de {$days} día(s).");
        return 0;
    }

    private function cleanDirectory(string $dir, int $cutoff): int
    {
        $deleted = 0;
        $files = glob($dir . '/*.mp3');

        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                @unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }
}
