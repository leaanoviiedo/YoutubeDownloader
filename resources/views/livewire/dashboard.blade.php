<div class="max-w-5xl mx-auto py-12 px-4">
    <!-- Encabezado -->
    <div class="mb-12 text-center animate-fade-in">
        <h1 class="text-5xl font-extrabold tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-emerald-400 mb-4">
            YT Playlist Downloader
        </h1>
        <p class="text-slate-400 text-lg">Descargá tus playlists favoritas en MP3 de alta calidad</p>
    </div>

    <!-- Entrada de URL -->
    @if(!$previewing)
        <div class="glass-card p-8 mb-8 transform hover:scale-[1.01] transition-transform duration-300">
            <form wire:submit="fetchPlaylist" class="flex flex-col md:flex-row gap-4">
                <input 
                    type="text" 
                    wire:model="url" 
                    placeholder="Pegá la URL de la playlist de YouTube aquí..." 
                    class="glass-input flex-1 text-lg"
                    id="playlist-url-input"
                    @if($loading) disabled @endif
                >
                <button type="submit" id="download-btn" class="glass-button text-lg flex items-center justify-center gap-2" wire:loading.attr="disabled" wire:target="fetchPlaylist" @if($loading) disabled @endif>
                    <span wire:loading.remove wire:target="fetchPlaylist">
                        <svg class="h-5 w-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        Buscar Playlist
                    </span>
                    <span wire:loading wire:target="fetchPlaylist" class="flex items-center gap-2">
                        <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Buscando...
                    </span>
                </button>
            </form>
            @error('url') <span class="text-red-400 text-sm mt-2 block">{{ $message }}</span> @enderror
        </div>
    @endif

    <!-- Estado de Carga Mejorado -->
    @if($loading)
        <div class="glass-card p-8 mb-8 animate-fade-in">
            <div class="flex flex-col items-center gap-4">
                <div class="relative">
                    <div class="w-16 h-16 rounded-full border-4 border-blue-500/20 flex items-center justify-center">
                        <svg class="animate-spin h-8 w-8 text-blue-400" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    <div class="absolute inset-0 rounded-full border-4 border-blue-400/30 animate-ping"></div>
                </div>
                <div class="text-center">
                    <p class="text-lg font-semibold text-white mb-1">Buscando playlist en YouTube...</p>
                    <p class="text-sm text-slate-400">Esto puede tomar unos segundos dependiendo del tamaño de la playlist</p>
                </div>
                <div class="w-full max-w-xs overflow-hidden h-1.5 rounded-full bg-white/5">
                    <div class="h-full rounded-full bg-gradient-to-r from-blue-500 to-cyan-400 animate-loading-bar"></div>
                </div>
            </div>
        </div>
    @endif

    <!-- Estado de Error -->
    @if($errorMessage && !$loading)
        <div class="glass-card p-6 mb-8 border border-red-500/30 animate-fade-in">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-12 h-12 rounded-full bg-red-500/15 flex items-center justify-center">
                    <svg class="h-6 w-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-base font-semibold text-red-400 mb-1">Error al buscar la playlist</h3>
                    <p class="text-sm text-slate-300">{{ $errorMessage }}</p>
                </div>
                <button 
                    wire:click="$set('errorMessage', null)" 
                    class="flex-shrink-0 text-slate-500 hover:text-white transition-colors p-1"
                    title="Cerrar"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="mt-4 flex items-center gap-3">
                <button 
                    wire:click="$set('errorMessage', null)" 
                    class="text-sm bg-red-500/20 hover:bg-red-500/30 text-red-300 hover:text-red-200 px-4 py-2 rounded-lg transition-all flex items-center gap-2"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Reintentar
                </button>
                <span class="text-xs text-slate-500">Cerrá este mensaje para ingresar otra URL</span>
            </div>
        </div>
    @endif

    <!-- Vista previa de Playlist -->
    @if($previewing && count($previewTracks) > 0)
        <div class="glass-card p-6 mb-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl font-bold text-white">Playlist encontrada</h2>
                    <p class="text-slate-400 text-sm mt-1">
                        <span class="text-emerald-400 font-semibold">{{ count($previewTracks) }}</span> canciones disponibles para descargar
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button 
                        wire:click="cancelPreview" 
                        class="text-sm text-slate-500 hover:text-red-400 transition-colors px-4 py-2"
                    >
                        Cancelar
                    </button>
                    <button 
                        wire:click="startDownload" 
                        class="glass-button flex items-center gap-2"
                        wire:loading.attr="disabled" 
                        wire:target="startDownload"
                    >
                        <span wire:loading.remove wire:target="startDownload">
                            <svg class="h-5 w-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Descargar Todo
                        </span>
                        <span wire:loading wire:target="startDownload" class="flex items-center gap-2">
                            <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Iniciando...
                        </span>
                    </button>
                </div>
            </div>

            <!-- Lista de tracks preview -->
            <div class="space-y-2 max-h-96 overflow-y-auto pr-2 custom-scrollbar">
                @foreach($previewTracks as $index => $track)
                    <div class="flex items-center gap-3 p-3 rounded-lg bg-white/5 hover:bg-white/10 transition-colors animate-slide-up" style="animation-delay: {{ $index * 30 }}ms">
                        <span class="text-slate-500 text-sm font-mono w-8 text-right">{{ $index + 1 }}</span>
                        <div class="w-8 h-8 rounded-full bg-blue-500/10 flex items-center justify-center flex-shrink-0">
                            <svg class="h-4 w-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-white font-medium truncate">{{ $track['title'] }}</p>
                            @if($track['uploader'])
                                <p class="text-xs text-slate-500 truncate">{{ $track['uploader'] }}</p>
                            @endif
                        </div>
                        @if($track['duration'])
                            <span class="text-xs text-slate-500 font-mono flex-shrink-0">
                                {{ gmdate($track['duration'] >= 3600 ? 'H:i:s' : 'i:s', $track['duration']) }}
                            </span>
                        @endif
                    </div>
                @endforeach
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
            $stopped = count(array_filter($downloads, fn($d) => $d['status'] === 'stopped'));
            $active = $downloading + $queued;
        @endphp

        @if(!empty($playlistName))
            <div class="glass-card p-4 mb-4 flex items-center gap-3">
                <svg class="h-5 w-5 text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                <span class="text-white font-semibold">{{ $playlistName }}</span>
            </div>
        @endif

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
                @if($stopped > 0)
                    <span class="text-amber-400">
                        {{ $stopped }} detenidas
                    </span>
                @endif
                @if($failed > 0)
                    <span class="text-red-400">
                        {{ $failed }} fallidas
                    </span>
                @endif
            </div>
            <div class="flex items-center gap-3">
                @if($active > 0)
                    <button wire:click="stopDownloads" class="text-sm bg-red-600/70 hover:bg-red-500 text-white px-4 py-1.5 rounded-lg transition-all flex items-center gap-2">
                        <span wire:loading.remove wire:target="stopDownloads">
                            <svg class="h-4 w-4 inline" fill="currentColor" viewBox="0 0 24 24"><rect x="6" y="6" width="12" height="12" rx="1"></rect></svg>
                            Detener
                        </span>
                        <span wire:loading wire:target="stopDownloads" class="flex items-center gap-2">
                            <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Deteniendo...
                        </span>
                    </button>
                @endif
                @if($completed > 1)
                    <a href="{{ route('download.all') }}" x-data="{ loading: false }" x-on:click="loading = true" class="text-sm bg-emerald-600/70 hover:bg-emerald-500 text-white px-4 py-1.5 rounded-lg transition-all flex items-center gap-2">
                        <svg x-show="!loading" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        <svg x-show="loading" class="h-4 w-4 animate-spin" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span x-text="loading ? 'Generando...' : 'Descargar ZIP'"></span>
                    </a>
                @endif
                <button wire:click="clearAll" class="text-sm text-slate-500 hover:text-red-400 transition-colors flex items-center gap-1">
                    <span wire:loading.remove wire:target="clearAll">Limpiar Todo</span>
                    <span wire:loading wire:target="clearAll" class="flex items-center gap-1">
                        <svg class="h-3 w-3 animate-spin" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Limpiando...
                    </span>
                </button>
            </div>
        </div>
    @endif

    <!-- Lista de Descargas -->
    <div class="space-y-3" wire:poll.2s="fetchDownloads">
        @forelse($downloads as $id => $item)
            <div class="glass-card p-4 animate-slide-up" style="animation-delay: {{ $loop->index * 50 }}ms">
                <div class="flex items-center gap-4">
                    <!-- Ícono de Estado -->
                    <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center
                        @if($item['status'] === 'completed') bg-emerald-500/20 text-emerald-400
                        @elseif($item['status'] === 'downloading') bg-blue-500/20 text-blue-400
                        @elseif($item['status'] === 'failed') bg-red-500/20 text-red-400
                        @elseif($item['status'] === 'stopped') bg-amber-500/20 text-amber-400
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
                        @elseif($item['status'] === 'stopped')
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><rect x="6" y="6" width="12" height="12" rx="1"></rect></svg>
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
                            <span class="text-xs font-medium uppercase tracking-wider flex-shrink-0
                                @if($item['status'] === 'completed') text-emerald-400
                                @elseif($item['status'] === 'downloading') text-blue-400
                                @elseif($item['status'] === 'failed') text-red-400
                                @elseif($item['status'] === 'stopped') text-amber-400
                                @else text-slate-500
                                @endif
                            ">
                                @if($item['status'] === 'completed') Completada
                                @elseif($item['status'] === 'downloading') Descargando
                                @elseif($item['status'] === 'failed') Fallida
                                @elseif($item['status'] === 'stopped') Detenida
                                @else En Cola
                                @endif
                            </span>
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
            @if(!$previewing && !$loading)
                <div class="text-center py-16 glass-card border-dashed">
                    <svg class="h-16 w-16 mx-auto text-slate-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                    </svg>
                    <p class="text-slate-500 text-lg">No hay descargas activas</p>
                    <p class="text-slate-600 text-sm mt-1">Pegá un link de playlist de YouTube para comenzar</p>
                    <button 
                        wire:click="loadExistingFiles"
                        class="mt-4 text-sm text-blue-400 hover:text-blue-300 underline transition-colors flex items-center gap-2 mx-auto"
                    >
                        <span wire:loading.remove wire:target="loadExistingFiles">Cargar archivos descargados previamente</span>
                        <span wire:loading wire:target="loadExistingFiles" class="flex items-center gap-2">
                            <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Buscando archivos...
                        </span>
                    </button>
                </div>
            @endif
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
@keyframes loading-bar {
    0% { width: 0%; margin-left: 0%; }
    50% { width: 60%; margin-left: 20%; }
    100% { width: 0%; margin-left: 100%; }
}
.animate-fade-in { animation: fade-in 0.8s ease-out forwards; }
.animate-slide-up { animation: slide-up 0.3s ease-out forwards; }
.animate-loading-bar { animation: loading-bar 1.8s ease-in-out infinite; }
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 3px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.2); }
</style>
