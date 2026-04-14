<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Limpiar descargas viejas (y carpetas vacías) cada hora, reteniendo máx 2 horas
Schedule::command('downloads:clean --hours=2')->hourly();

// Actualizar yt-dlp automáticamente todos los días
Schedule::exec('yt-dlp -U')->daily();
