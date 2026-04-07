<div class="max-w-5xl mx-auto py-12 px-4">
    <!-- Encabezado -->
    <div class="mb-10 text-center animate-fade-in">
        <div class="inline-flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-emerald-500 flex items-center justify-center shadow-lg shadow-blue-500/30">
                <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V8.69a8.27 8.27 0 004.84 1.56V6.82a4.85 4.85 0 01-1.07-.13z"/></svg>
            </div>
            <h1 class="text-4xl font-extrabold tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-blue-400 via-cyan-300 to-emerald-400">
                YT Downloader
            </h1>
        </div>
        <p class="text-slate-400 text-base">Descargá canciones o playlists completas en alta calidad</p>
    </div>

    <!-- Entrada de URL -->
    @if(!$previewing && !$singleMode)
        <div class="glass-card p-6 mb-6 animate-fade-in">
            <div class="flex flex-col gap-4">
                <input
                    type="text"
                    wire:model="url"
                    placeholder="Pegá la URL de YouTube aquí (video o playlist)..."
                    class="glass-input flex-1 text-base w-full"
                    id="yt-url-input"
                    @if($loading) disabled @endif
                >

                <!-- Selector de formato -->
                <div class="flex flex-wrap items-center gap-3">
                    <span class="text-xs text-slate-400 font-medium uppercase tracking-wider">Formato:</span>
                    <div class="flex items-center gap-2">
                        <label class="format-pill @if($audioFormat === 'mp3') format-pill-active @endif cursor-pointer">
                            <input type="radio" wire:model="audioFormat" value="mp3" class="sr-only">
                            🎵 MP3
                        </label>
                        <label class="format-pill @if($audioFormat === 'flac') format-pill-active @endif cursor-pointer">
                            <input type="radio" wire:model="audioFormat" value="flac" class="sr-only">
                            💎 FLAC
                        </label>
                        <label class="format-pill @if($audioFormat === 'ogg') format-pill-active @endif cursor-pointer">
                            <input type="radio" wire:model="audioFormat" value="ogg" class="sr-only">
                            🔊 OGG
                        </label>
                    </div>
                    <span class="text-xs text-slate-500 ml-auto hidden sm:block">
                        @if($audioFormat === 'mp3') Compatible con todos los dispositivos
                        @elseif($audioFormat === 'flac') Sin pérdida de calidad (archivos grandes)
                        @else Buena calidad, menor tamaño
                        @endif
                    </span>
                </div>

                <!-- Botones de acción -->
                <div class="flex flex-col sm:flex-row gap-3">
                    <!-- Botón: Canción individual -->
                    <button
                        wire:click="fetchSingleTrack"
                        id="btn-single"
                        class="flex-1 btn-single flex items-center justify-center gap-2 py-3 px-5 rounded-xl font-medium text-sm transition-all"
                        wire:loading.attr="disabled"
                        wire:target="fetchSingleTrack,fetchPlaylist"
                        @if($loading) disabled @endif
                    >
                        <span wire:loading.remove wire:target="fetchSingleTrack">
                            <svg class="h-5 w-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path></svg>
                            Descargar Canción
                        </span>
                        <span wire:loading wire:target="fetchSingleTrack" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Buscando...
                        </span>
                    </button>

                    <!-- Botón: Playlist completa -->
                    <button
                        wire:click="fetchPlaylist"
                        id="btn-playlist"
                        class="flex-1 btn-playlist flex items-center justify-center gap-2 py-3 px-5 rounded-xl font-medium text-sm transition-all"
                        wire:loading.attr="disabled"
                        wire:target="fetchSingleTrack,fetchPlaylist"
                        @if($loading) disabled @endif
                    >
                        <span wire:loading.remove wire:target="fetchPlaylist">
                            <svg class="h-5 w-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h10M4 18h10"></path></svg>
                            Buscar Playlist
                        </span>
                        <span wire:loading wire:target="fetchPlaylist" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Buscando...
                        </span>
                    </button>
                </div>
            </div>
            @error('url') <span class="text-red-400 text-sm mt-2 block">{{ $message }}</span> @enderror
        </div>
    @endif

    <!-- Estado de Carga -->
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
                    <p class="text-lg font-semibold text-white mb-1">Buscando en YouTube...</p>
                    <p class="text-sm text-slate-400">Esto puede tomar unos segundos</p>
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
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-500/15 flex items-center justify-center">
                    <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-sm font-semibold text-red-400 mb-1">Error</h3>
                    <p class="text-sm text-slate-300">{{ $errorMessage }}</p>
                </div>
                <button wire:click="$set('errorMessage', null)" class="flex-shrink-0 text-slate-500 hover:text-white transition-colors p-1" title="Cerrar">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        </div>
    @endif

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- Preview: Canción Individual                                        -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    @if($singleMode && !empty($singleTrackInfo))
        <div class="glass-card p-6 mb-8 animate-fade-in border border-emerald-500/20">
            <div class="flex items-center gap-2 mb-5">
                <div class="w-7 h-7 rounded-lg bg-emerald-500/20 flex items-center justify-center">
                    <svg class="h-4 w-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path></svg>
                </div>
                <h2 class="text-base font-bold text-white">Canción encontrada</h2>
            </div>

            <div class="flex gap-5 items-start">
                <!-- Thumbnail -->
                @if(!empty($singleTrackInfo['thumbnail']))
                    <img
                        src="{{ $singleTrackInfo['thumbnail'] }}"
                        alt="Thumbnail"
                        class="w-28 h-20 object-cover rounded-xl flex-shrink-0 shadow-lg border border-white/10"
                        onerror="this.style.display='none'"
                    >
                @endif

                <!-- Info -->
                <div class="flex-1 min-w-0">
                    <p class="text-white font-semibold text-lg leading-snug mb-1">{{ $singleTrackInfo['title'] }}</p>
                    @if($singleTrackInfo['uploader'])
                        <p class="text-slate-400 text-sm mb-2 flex items-center gap-1">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            {{ $singleTrackInfo['uploader'] }}
                        </p>
                    @endif
                    <div class="flex flex-wrap items-center gap-3 text-xs">
                        @if($singleTrackInfo['duration'])
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-white/5 text-slate-400">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                {{ gmdate($singleTrackInfo['duration'] >= 3600 ? 'H:i:s' : 'i:s', $singleTrackInfo['duration']) }}
                            </span>
                        @endif
                        @if($singleTrackInfo['view_count'])
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-white/5 text-slate-400">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                {{ number_format($singleTrackInfo['view_count']) }}
                            </span>
                        @endif
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-{{ $audioFormat === 'mp3' ? 'blue' : ($audioFormat === 'flac' ? 'purple' : 'emerald') }}-500/15 text-{{ $audioFormat === 'mp3' ? 'blue' : ($audioFormat === 'flac' ? 'purple' : 'emerald') }}-400 font-semibold uppercase">
                            {{ strtoupper($audioFormat) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-5 flex items-center gap-3 justify-end pt-4 border-t border-white/5">
                <button wire:click="cancelSingle" class="text-sm text-slate-500 hover:text-red-400 transition-colors px-4 py-2">
                    Cancelar
                </button>
                <button
                    wire:click="startSingleDownload"
                    class="glass-button flex items-center gap-2"
                    wire:loading.attr="disabled"
                    wire:target="startSingleDownload"
                >
                    <span wire:loading.remove wire:target="startSingleDownload">
                        <svg class="h-4 w-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        Descargar en {{ strtoupper($audioFormat) }}
                    </span>
                    <span wire:loading wire:target="startSingleDownload" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Iniciando...
                    </span>
                </button>
            </div>
        </div>
    @endif

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- Preview: Playlist                                                  -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    @if($previewing && count($previewTracks) > 0)
        <div class="glass-card p-6 mb-8 animate-fade-in border border-blue-500/20">
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-3">
                    <div class="w-7 h-7 rounded-lg bg-blue-500/20 flex items-center justify-center">
                        <svg class="h-4 w-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h10M4 18h10"></path></svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-white">Playlist encontrada</h2>
                        @if($playlistName)
                            <p class="text-slate-400 text-xs mt-0.5">{{ $playlistName }}</p>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-sm text-slate-400"><span class="text-emerald-400 font-semibold">{{ count($previewTracks) }}</span> canciones</span>
                    <button wire:click="cancelPreview" class="text-sm text-slate-500 hover:text-red-400 transition-colors px-3 py-1.5">Cancelar</button>
                    <button
                        wire:click="startDownload"
                        class="glass-button flex items-center gap-2 text-sm py-2 px-4"
                        wire:loading.attr="disabled"
                        wire:target="startDownload"
                    >
                        <span wire:loading.remove wire:target="startDownload">
                            <svg class="h-4 w-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Descargar Todo ({{ strtoupper($audioFormat) }})
                        </span>
                        <span wire:loading wire:target="startDownload" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Iniciando...
                        </span>
                    </button>
                </div>
            </div>

            <!-- Lista de tracks preview -->
            <div class="space-y-1.5 max-h-96 overflow-y-auto pr-2 custom-scrollbar">
                @foreach($previewTracks as $index => $track)
                    <div class="flex items-center gap-3 p-2.5 rounded-lg bg-white/5 hover:bg-white/10 transition-colors animate-slide-up" style="animation-delay: {{ $index * 30 }}ms">
                        <span class="text-slate-500 text-xs font-mono w-7 text-right">{{ $index + 1 }}</span>
                        <div class="w-7 h-7 rounded-full bg-blue-500/10 flex items-center justify-center flex-shrink-0">
                            <svg class="h-3.5 w-3.5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- Barra de Estadísticas                                              -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
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
            <div class="glass-card p-3.5 mb-3 flex items-center gap-3">
                <svg class="h-4 w-4 text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                <span class="text-white font-semibold text-sm">{{ $playlistName }}</span>
            </div>
        @endif

        <div class="glass-card p-4 mb-5 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-4 text-sm font-medium">
                <span class="text-slate-300">
                    <span class="text-white font-bold text-base">{{ $total }}</span> canciones
                </span>
                @if($completed > 0)
                    <span class="text-emerald-400 flex items-center gap-1">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        {{ $completed }} completadas
                    </span>
                @endif
                @if($downloading > 0)
                    <span class="text-blue-400 flex items-center gap-1">
                        <svg class="h-3.5 w-3.5 animate-pulse" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"></circle></svg>
                        {{ $downloading }} descargando
                    </span>
                @endif
                @if($queued > 0)
                    <span class="text-slate-400">{{ $queued }} en cola</span>
                @endif
                @if($stopped > 0)
                    <span class="text-amber-400">{{ $stopped }} detenidas</span>
                @endif
                @if($failed > 0)
                    <span class="text-red-400">{{ $failed }} fallidas</span>
                @endif
            </div>
            <div class="flex items-center gap-2">
                @if($active > 0)
                    <button wire:click="stopDownloads" class="text-xs bg-red-600/70 hover:bg-red-500 text-white px-3 py-1.5 rounded-lg transition-all flex items-center gap-1.5">
                        <span wire:loading.remove wire:target="stopDownloads">
                            <svg class="h-3.5 w-3.5 inline" fill="currentColor" viewBox="0 0 24 24"><rect x="6" y="6" width="12" height="12" rx="1"></rect></svg>
                            Detener
                        </span>
                        <span wire:loading wire:target="stopDownloads" class="flex items-center gap-1.5">
                            <svg class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Deteniendo...
                        </span>
                    </button>
                @endif
                @if($completed > 1)
                    <a href="{{ route('download.all') }}" x-data="{ loading: false }" x-on:click="loading = true" class="text-xs bg-emerald-600/70 hover:bg-emerald-500 text-white px-3 py-1.5 rounded-lg transition-all flex items-center gap-1.5">
                        <svg x-show="!loading" class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        <svg x-show="loading" class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span x-text="loading ? 'Generando...' : 'Descargar ZIP'"></span>
                    </a>
                @endif
                <button wire:click="clearAll" class="text-xs text-slate-500 hover:text-red-400 transition-colors flex items-center gap-1">
                    <span wire:loading.remove wire:target="clearAll">Limpiar Todo</span>
                    <span wire:loading wire:target="clearAll" class="flex items-center gap-1">
                        <svg class="h-3 w-3 animate-spin" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Limpiando...
                    </span>
                </button>
            </div>
        </div>
    @endif

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- Lista de Descargas                                                 -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
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
                            <div class="flex items-center gap-2 flex-shrink-0">
                                @if(isset($item['audio_format']))
                                    <span class="text-xs px-1.5 py-0.5 rounded font-mono
                                        @if(($item['audio_format'] ?? 'mp3') === 'flac') bg-purple-500/20 text-purple-400
                                        @elseif(($item['audio_format'] ?? 'mp3') === 'ogg') bg-emerald-500/20 text-emerald-400
                                        @else bg-blue-500/20 text-blue-400
                                        @endif
                                    ">{{ strtoupper($item['audio_format'] ?? 'MP3') }}</span>
                                @endif
                                <span class="text-xs font-medium uppercase tracking-wider
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
                        <div class="flex items-center gap-1.5 flex-shrink-0">
                            <button
                                wire:click="playTrack('{{ $id }}')"
                                class="p-2 rounded-lg transition-all hover:bg-white/10
                                    {{ $playingTrack === $id ? 'text-blue-400 bg-blue-500/10' : 'text-slate-400 hover:text-white' }}
                                "
                                title="Reproducir"
                            >
                                @if($playingTrack === $id)
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"></path></svg>
                                @else
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"></path></svg>
                                @endif
                            </button>
                            <a
                                href="{{ route('track.download', ['filename' => $item['filename']]) }}"
                                class="p-2 rounded-lg text-slate-400 hover:text-emerald-400 hover:bg-white/10 transition-all"
                                title="Descargar {{ strtoupper($item['audio_format'] ?? 'MP3') }}"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                            <source src="{{ route('track.play', ['filename' => $item['filename']]) }}" type="audio/{{ ($item['audio_format'] ?? 'mp3') === 'ogg' ? 'ogg' : ($item['audio_format'] ?? 'mpeg') === 'flac' ? 'flac' : 'mpeg' }}">
                            Tu navegador no soporta la reproducción de audio.
                        </audio>
                    </div>
                @endif
            </div>
        @empty
            @if(!$previewing && !$loading && !$singleMode)
                <div class="text-center py-16 glass-card border-dashed">
                    <svg class="h-14 w-14 mx-auto text-slate-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                    </svg>
                    <p class="text-slate-500 text-base">No hay descargas activas</p>
                    <p class="text-slate-600 text-sm mt-1">Pegá un link de YouTube para comenzar</p>
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

    <!-- Toast -->
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
    from { opacity: 0; transform: translateY(-16px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes slide-up {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes loading-bar {
    0%   { width: 0%;   margin-left: 0%; }
    50%  { width: 60%;  margin-left: 20%; }
    100% { width: 0%;   margin-left: 100%; }
}
.animate-fade-in  { animation: fade-in 0.5s ease-out forwards; }
.animate-slide-up { animation: slide-up 0.3s ease-out forwards; }
.animate-loading-bar { animation: loading-bar 1.8s ease-in-out infinite; }

.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 3px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.2); }

/* Botones de modo */
.btn-single {
    background: linear-gradient(135deg, rgba(16,185,129,0.15), rgba(52,211,153,0.08));
    border: 1px solid rgba(16,185,129,0.3);
    color: #6ee7b7;
}
.btn-single:hover:not(:disabled) {
    background: linear-gradient(135deg, rgba(16,185,129,0.28), rgba(52,211,153,0.18));
    border-color: rgba(16,185,129,0.5);
    color: #a7f3d0;
    box-shadow: 0 0 20px rgba(16,185,129,0.15);
    transform: translateY(-1px);
}
.btn-single:disabled { opacity: 0.5; cursor: not-allowed; }

.btn-playlist {
    background: linear-gradient(135deg, rgba(59,130,246,0.15), rgba(99,102,241,0.08));
    border: 1px solid rgba(59,130,246,0.3);
    color: #93c5fd;
}
.btn-playlist:hover:not(:disabled) {
    background: linear-gradient(135deg, rgba(59,130,246,0.28), rgba(99,102,241,0.18));
    border-color: rgba(59,130,246,0.5);
    color: #bfdbfe;
    box-shadow: 0 0 20px rgba(59,130,246,0.15);
    transform: translateY(-1px);
}
.btn-playlist:disabled { opacity: 0.5; cursor: not-allowed; }

/* Format pills */
.format-pill {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
    border: 1px solid rgba(255,255,255,0.1);
    background: rgba(255,255,255,0.04);
    color: #94a3b8;
    transition: all 0.2s;
}
.format-pill:hover { border-color: rgba(255,255,255,0.2); color: #e2e8f0; }
.format-pill-active {
    border-color: rgba(59,130,246,0.5) !important;
    background: rgba(59,130,246,0.15) !important;
    color: #93c5fd !important;
}
</style>
