<?php

use App\Livewire\Dashboard;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;

Route::get('/', Dashboard::class);

// Download individual track
Route::get('/download/{filename}', function (string $filename) {
    $path = storage_path('app/downloads/' . $filename);
    
    if (!file_exists($path)) {
        abort(404, 'Archivo no encontrado');
    }

    return Response::download($path, $filename, [
        'Content-Type' => 'audio/mpeg',
    ]);
})->name('track.download');

// Stream audio for playback
Route::get('/play/{filename}', function (string $filename) {
    $path = storage_path('app/downloads/' . $filename);
    
    if (!file_exists($path)) {
        abort(404, 'Archivo no encontrado');
    }

    return Response::file($path, [
        'Content-Type' => 'audio/mpeg',
    ]);
})->name('track.play');

// Download all as ZIP
Route::get('/download-all', function () {
    $downloadsPath = storage_path('app/downloads');
    $files = glob($downloadsPath . '/*.mp3');

    if (empty($files)) {
        abort(404, 'No hay archivos para descargar');
    }

    $zipPath = storage_path('app/downloads/playlist.zip');

    $zip = new \ZipArchive();
    $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
    
    foreach ($files as $file) {
        $zip->addFile($file, basename($file));
    }
    
    $zip->close();

    return Response::download($zipPath, 'playlist.zip')->deleteFileAfterSend();
})->name('download.all');
