<?php

use App\Livewire\Dashboard;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;

Route::get('/', Dashboard::class);

// Download individual track (supports subfolder paths like "playlist_name/track.mp3")
Route::get('/download/{path}', function (string $path) {
    $fullPath = storage_path('app/downloads/' . $path);
    
    if (!file_exists($fullPath)) {
        abort(404, 'Archivo no encontrado');
    }

    return Response::download($fullPath, basename($path), [
        'Content-Type' => 'audio/mpeg',
    ]);
})->where('path', '.*')->name('track.download');

// Stream audio for playback (supports subfolder paths)
Route::get('/play/{path}', function (string $path) {
    $fullPath = storage_path('app/downloads/' . $path);
    
    if (!file_exists($fullPath)) {
        abort(404, 'Archivo no encontrado');
    }

    return Response::file($fullPath, [
        'Content-Type' => 'audio/mpeg',
    ]);
})->where('path', '.*')->name('track.play');

// Download all as ZIP with playlist name
Route::get('/download-all', function () {
    $downloadsPath = storage_path('app/downloads');
    $playlistFolder = \Illuminate\Support\Facades\Redis::get('current_playlist_folder') ?? '';
    $playlistName = \Illuminate\Support\Facades\Redis::get('current_playlist_name') ?? 'playlist';
    $zipName = \Illuminate\Support\Str::slug($playlistName, '_') . '.zip';

    // Search in the playlist subfolder if available, otherwise root
    $searchDir = !empty($playlistFolder) ? $downloadsPath . '/' . $playlistFolder : $downloadsPath;
    $files = glob($searchDir . '/*.mp3');

    if (empty($files)) {
        abort(404, 'No hay archivos para descargar');
    }

    $zipPath = $downloadsPath . '/' . $zipName;

    $zip = new \ZipArchive();
    $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
    
    foreach ($files as $file) {
        $zip->addFile($file, basename($file));
    }
    
    $zip->close();

    return Response::download($zipPath, $zipName)->deleteFileAfterSend();
})->name('download.all');
