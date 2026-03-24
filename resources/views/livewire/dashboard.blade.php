<div class="max-w-5xl mx-auto py-12 px-4">
    <!-- Encabezado -->
    <div class="mb-12 text-center animate-fade-in">
        <h1 class="text-5xl font-extrabold tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-emerald-400 mb-4">
            YT Playlist Downloader
        </h1>
        <p class="text-slate-400 text-lg">Descargá tus playlists favoritas en MP3 de alta calidad</p>
    </div>

    <!-- Entrada de URL -->
    <div class="glass-card p-8 mb-8 transform hover:scale-[1.01] transition-transform duration-300">
        <form wire:submit="download" class="flex flex-col md:flex-row gap-4">
            <input 
                type="text" 
                wire:model="url" 
                placeholder="Pegá la URL de la playlist de YouTube aquí..." 
                class="glass-input flex-1 text-lg"
                id="playlist-url-input"
            >
            <button type="submit" id="download-btn" class="glass-button text-lg flex items-center justify-center gap-2" wire:loading.attr="disabled" wire:target="download">
                <span wire:loading.remove wire:target="download">Descargar Playlist</span>
                <span wire:loading wire:target="download" class="flex items-center gap-2">
                    <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Procesando...
                </span>
            </button>
        </form>
        @error('url') <span class="text-red-400 text-sm mt-2 block">{{ $message }}</span> @enderror
    </div>

    <!-- Estado de Carga -->
    @if($loading && count($downloads) === 0)
        <div class="glass-card p-8 mb-8 text-center">
            <div class="flex items-center justify-center gap-3 text-blue-300">
                <svg class="animate-spin h-6 w-6" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-lg font-medium">Obteniendo canciones de la playlist...</span>
            </div>
        </div>
    @endif

    <!-- Barra de Estadísticas -->
    @if(count($downloads) > 0)
        @php
            $total = count($downloads);
            $completed = count(array_filter($downloads, fn($d) => $d['status'] === 'completed'));
            $downloading = count(array_filter($downloads, fn($d) => $d['status'] === 'downloading'));
            $queued = count(array_filter($downloads, fn($d) => $d['status'] === 'queued'));
            $failed = count(array_filter($downloads, fn($d) => $d['status'] === 'failed'));
        @endphp
        <div class="glass-card p-4 mb-6 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-6 text-sm font-medium">
                <span class="text-slate-300">
                    <span class="text-white font-bold text-lg">{{ $total }}</span> canciones
                </span>
                @if($completed > 0)
                    <span class="text-emerald-400 flex items-center gap-1">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        {{ $completed }} completadas
                    </span>
                @endif
                @if($downloading > 0)
                    <span class="text-blue-400 flex items-center gap-1">
                        <svg class="h-4 w-4 animate-pulse" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"></circle></svg>
                        {{ $downloading }} descargando
                    </span>
                @endif
                @if($queued > 0)
                    <span class="text-slate-400">
                        {{ $queued }} en cola
                    </span>
                @endif
                @if($failed > 0)
                    <span class="text-red-400">
                        {{ $failed }} fallidas
                    </span>
                @endif
            </div>
            <div class="flex items-center gap-3">
                @if($completed > 1)
                    <a href="{{ route('download.all') }}" class="text-sm bg-emerald-600/70 hover:bg-emerald-500 text-white px-4 py-1.5 rounded-lg transition-all flex items-center gap-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        Descargar ZIP
                    </a>
                @endif
                <button wire:click="clearAll" class="text-sm text-slate-500 hover:text-red-400 transition-colors">
                    Limpiar Todo
                </button>
            </div>
        </div>
    @endif

    <!-- Lista de Canciones -->
    <div class="space-y-3" wire:poll.2s="fetchDownloads">
        @forelse($downloads as $id => $item)
            <div class="glass-card p-4 animate-slide-up" style="animation-delay: {{ $loop->index * 50 }}ms">
                <div class="flex items-center gap-4">
                    <!-- Ícono de Estado -->
                    <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center
                        @if($item['status'] === 'completed') bg-emerald-500/20 text-emerald-400
                        @elseif($item['status'] === 'downloading') bg-blue-500/20 text-blue-400
                        @elseif($item['status'] === 'failed') bg-red-500/20 text-red-400
                        @else bg-white/5 text-slate-500
                        @endif
                    ">
                        @if($item['status'] === 'completed')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        @elseif($item['status'] === 'downloading')
                            <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        @elseif($item['status'] === 'failed')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        @else
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        @endif
                    </div>

                    <!-- Info del Track y Progreso -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between mb-1">
                            <h3 class="text-sm font-semibold text-white truncate pr-4">{{ $item['title'] }}</h3>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <span class="text-xs font-medium uppercase tracking-wider
                                    @if($item['status'] === 'completed') text-emerald-400
                                    @elseif($item['status'] === 'downloading') text-blue-400
                                    @elseif($item['status'] === 'failed') text-red-400
                                    @else text-slate-500
                                    @endif
                                ">
                                    @if($item['status'] === 'completed') Completada
                                    @elseif($item['status'] === 'downloading') Descargando
                                    @elseif($item['status'] === 'failed') Fallida
                                    @else En Cola
                                    @endif
                                </span>
                            </div>
                        </div>

                        @if($item['status'] === 'downloading' || $item['status'] === 'completed')
                            <div class="overflow-hidden h-1.5 rounded-full bg-white/5">
                                <div 
                                    style="width:{{ $item['progress'] ?? 0 }}%" 
                                    class="h-full rounded-full transition-all duration-500
                                        @if($item['status'] === 'completed') bg-gradient-to-r from-emerald-500 to-emerald-400
                                        @else bg-gradient-to-r from-blue-500 to-cyan-400 shadow-[0_0_8px_rgba(59,130,246,0.5)]
                                        @endif
                                    "
                                ></div>
                            </div>
                        @endif

                        @if($item['status'] === 'failed' && isset($item['error']))
                            <p class="text-xs text-red-400/70 mt-1 truncate">{{ $item['error'] }}</p>
                        @endif
                    </div>

                    <!-- Botones de Acción -->
                    @if($item['status'] === 'completed' && isset($item['filename']))
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <!-- Reproducir -->
                            <button 
                                wire:click="playTrack('{{ $id }}')"
                                class="p-2 rounded-lg transition-all hover:bg-white/10
                                    {{ $playingTrack === $id ? 'text-blue-400 bg-blue-500/10' : 'text-slate-400 hover:text-white' }}
                                "
                                title="Reproducir"
                            >
                                @if($playingTrack === $id)
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"></path></svg>
                                @else
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"></path></svg>
                                @endif
                            </button>

                            <!-- Descargar -->
                            <a 
                                href="{{ route('track.download', ['filename' => $item['filename']]) }}"
                                class="p-2 rounded-lg text-slate-400 hover:text-emerald-400 hover:bg-white/10 transition-all"
                                title="Descargar MP3"
                            >
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                            </a>
                        </div>
                    @endif
                </div>

                <!-- Reproductor de Audio -->
                @if($playingTrack === $id && $item['status'] === 'completed' && isset($item['filename']))
                    <div class="mt-3 pt-3 border-t border-white/5">
                        <audio 
                            controls 
                            autoplay 
                            class="w-full h-10 rounded-lg"
                            style="filter: hue-rotate(200deg) saturate(1.5);"
                        >
                            <source src="{{ route('track.play', ['filename' => $item['filename']]) }}" type="audio/mpeg">
                            Tu navegador no soporta la reproducción de audio.
                        </audio>
                    </div>
                @endif
            </div>
        @empty
            <div class="text-center py-16 glass-card border-dashed">
                <svg class="h-16 w-16 mx-auto text-slate-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                </svg>
                <p class="text-slate-500 text-lg">No hay descargas activas</p>
                <p class="text-slate-600 text-sm mt-1">Pegá un link de playlist de YouTube para comenzar</p>
                <button 
                    wire:click="loadExistingFiles"
                    class="mt-4 text-sm text-blue-400 hover:text-blue-300 underline transition-colors"
                >
                    Cargar archivos descargados previamente
                </button>
            </div>
        @endforelse
    </div>

    <!-- Notificaciones Toast -->
    <div 
        x-data="{ show: false, message: '' }" 
        x-on:notify.window="show = true; message = $event.detail; setTimeout(() => show = false, 3000)"
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-4"
        class="fixed bottom-8 right-8 z-50 glass-card p-4 flex items-center gap-3 border-blue-500/50"
        style="display: none;"
    >
        <div class="bg-blue-500 rounded-full p-1">
            <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <span class="text-white font-medium" x-text="message"></span>
    </div>
</div>

<style>
@keyframes fade-in {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes slide-up {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in { animation: fade-in 0.8s ease-out forwards; }
.animate-slide-up { animation: slide-up 0.3s ease-out forwards; }
</style>
