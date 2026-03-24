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

        $files = glob($dir . '/*.mp3');
        $cutoff = now()->subDays($days)->timestamp;
        $deleted = 0;

        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                @unlink($file);
                $deleted++;
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
}
