<?php

use App\Livewire\Dashboard;
use App\Livewire\AudioEditor;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;

Route::get('/', Dashboard::class)->name('home');
Route::get('/editor', AudioEditor::class)->name('audio.editor');

// Upload cookies.txt via the UI
Route::post('/upload-cookies', function (Request $request) {
    $request->validate(['cookies' => 'required|file|max:5120']);
    $request->file('cookies')->move(storage_path('app'), 'youtube_cookies.txt');
    return redirect('/')->with('toast', '✅ Cookies guardadas. Las descargas ya deberían funcionar.');
})->name('upload.cookies');

// Download individual track
Route::get('/download/{path}', function (string $path) {
    $fullPath = storage_path('app/downloads/' . $path);
    if (!file_exists($fullPath)) abort(404, 'Archivo no encontrado');
    $ext  = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
    $mime = match ($ext) {
        'flac' => 'audio/flac',
        'ogg'  => 'audio/ogg',
        'wav'  => 'audio/wav',
        'aac', 'm4a' => 'audio/aac',
        default => 'audio/mpeg',
    };
    return Response::download($fullPath, basename($path), ['Content-Type' => $mime]);
})->where('path', '.*')->name('track.download');

// Stream audio for playback
Route::get('/play/{path}', function (string $path) {
    $fullPath = storage_path('app/downloads/' . $path);
    if (!file_exists($fullPath)) abort(404, 'Archivo no encontrado');
    $ext  = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
    $mime = match ($ext) {
        'flac' => 'audio/flac',
        'ogg'  => 'audio/ogg',
        'wav'  => 'audio/wav',
        'aac', 'm4a' => 'audio/aac',
        default => 'audio/mpeg',
    };
    return Response::file($fullPath, ['Content-Type' => $mime]);
})->where('path', '.*')->name('track.play');

// Download all as ZIP
Route::get('/download-all', function () {
    $sid = session()->getId();
    $downloadsPath  = storage_path('app/downloads');
    $playlistFolder = \Illuminate\Support\Facades\Redis::get("current_playlist_folder_{$sid}") ?? '';
    $playlistName   = \Illuminate\Support\Facades\Redis::get("current_playlist_name_{$sid}") ?? 'playlist';
    $zipName        = \Illuminate\Support\Str::slug($playlistName, '_') . '.zip';
    $searchDir      = !empty($playlistFolder) ? $downloadsPath . '/' . $playlistFolder : $downloadsPath;
    $files = [];
    foreach (['mp3', 'flac', 'ogg', 'wav', 'aac', 'm4a'] as $ext) {
        $files = array_merge($files, glob($searchDir . '/*.' . $ext) ?: []);
    }
    if (empty($files)) abort(404, 'No hay archivos para descargar');
    $zipPath = storage_path('app/' . $zipName);
    $zip = new \ZipArchive();
    $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
    foreach ($files as $file) {
        $zip->addFile($file, basename($file));
    }
    $zip->close();
    return Response::download($zipPath, $zipName)->deleteFileAfterSend();
})->name('download.all');

// Configuracion page (simple blade view, no Livewire needed)
Route::get('/configuracion', function () {
    return view('configuracion');
})->name('config');
