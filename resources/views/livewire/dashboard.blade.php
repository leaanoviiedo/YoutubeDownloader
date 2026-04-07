<div class="max-w-5xl mx-auto py-10 px-4">

    {{-- ═══ HEADER ═══ --}}
    <div class="mb-8 text-center animate-fade-in">
        <div class="inline-flex items-center gap-3 mb-2">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-emerald-500 flex items-center justify-center shadow-lg shadow-blue-500/30">
                <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V8.69a8.27 8.27 0 004.84 1.56V6.82a4.85 4.85 0 01-1.07-.13z"/></svg>
            </div>
            <h1 class="text-4xl font-extrabold tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-blue-400 via-cyan-300 to-emerald-400">YT Downloader</h1>
        </div>
        <p class="text-slate-400 text-sm">Descargá canciones, playlists o buscá por palabras clave</p>
    </div>

    {{-- ═══ TABS ═══ --}}
    <div class="flex gap-1 mb-6 p-1 rounded-xl bg-white/5 border border-white/10">
        <button wire:click="$set('activeTab','download')" class="flex-1 py-2 px-4 rounded-lg text-sm font-medium transition-all {{ $activeTab === 'download' ? 'bg-blue-600 text-white shadow' : 'text-slate-400 hover:text-white' }}">
            📥 Descargar
        </button>
        <button wire:click="$set('activeTab','search')" class="flex-1 py-2 px-4 rounded-lg text-sm font-medium transition-all {{ $activeTab === 'search' ? 'bg-blue-600 text-white shadow' : 'text-slate-400 hover:text-white' }}">
            🔍 Buscar en YouTube
        </button>
    </div>

    {{-- ═══ TAB: SEARCH ═══ --}}
    @if($activeTab === 'search')
        <div class="glass-card p-6 mb-6 animate-fade-in">
            <div class="flex gap-3 mb-5">
                <input type="text" wire:model="searchQuery" wire:keydown.enter="searchYouTube"
                    placeholder="Buscá artista, canción o álbum..."
                    class="glass-input flex-1 text-base"
                    id="search-input"
                    @if($searching) disabled @endif>
                <button wire:click="searchYouTube" wire:loading.attr="disabled" wire:target="searchYouTube"
                    class="glass-button flex items-center gap-2 px-5 py-2">
                    <span wire:loading.remove wire:target="searchYouTube">
                        <svg class="h-4 w-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        Buscar
                    </span>
                    <span wire:loading wire:target="searchYouTube" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Buscando...
                    </span>
                </button>
            </div>
            @error('searchQuery') <p class="text-red-400 text-sm mb-3">{{ $message }}</p> @enderror

            @if($searchError)
                <p class="text-amber-400 text-sm text-center py-4">{{ $searchError }}</p>
            @endif

            @if(!empty($searchResults))
                <div class="space-y-2 max-h-[500px] overflow-y-auto custom-scrollbar pr-1">
                    @foreach($searchResults as $result)
                        <div class="flex items-center gap-3 p-3 rounded-xl bg-white/5 hover:bg-white/10 transition-colors group">
                            @if(!empty($result['thumbnail']))
                                <img src="{{ $result['thumbnail'] }}" class="w-20 h-14 object-cover rounded-lg flex-shrink-0" onerror="this.style.display='none'">
                            @endif
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-white truncate">{{ $result['title'] }}</p>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    {{ $result['channel'] ?? '' }}
                                    @if($result['duration']) · {{ gmdate($result['duration'] >= 3600 ? 'H:i:s' : 'i:s', $result['duration']) }} @endif
                                    @if($result['view_count']) · {{ number_format($result['view_count']) }} vistas @endif
                                </p>
                            </div>
                            <button wire:click="downloadSearchResult('{{ addslashes($result['url']) }}', '{{ addslashes($result['title']) }}')"
                                class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity p-2 rounded-lg bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-400"
                                title="Descargar">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            </button>
                        </div>
                    @endforeach
                </div>
            @elseif(!$searching && empty($searchError))
                <div class="text-center py-10 text-slate-600">
                    <svg class="h-12 w-12 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <p class="text-sm">Escribí y presioná Buscar</p>
                </div>
            @endif
        </div>
    @endif

    {{-- ═══ TAB: DOWNLOAD ═══ --}}
    @if($activeTab === 'download')

        {{-- URL Input --}}
        @if(!$previewing && !$singleMode)
            <div class="glass-card p-6 mb-6 animate-fade-in">
                <div class="flex flex-col gap-4">
                    <input type="text" wire:model="url"
                        placeholder="Pegá la URL de YouTube (video o playlist)..."
                        class="glass-input w-full text-base" id="yt-url-input"
                        @if($loading) disabled @endif
                        wire:keydown.enter="fetchSingleTrack">

                    {{-- Formato + Bitrate --}}
                    <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-slate-400 uppercase tracking-wider font-medium">Formato</span>
                            <button type="button" wire:click="$set('audioFormat','mp3')" class="format-pill {{ $audioFormat==='mp3' ? 'format-pill-active' : '' }}">🎵 MP3</button>
                            <button type="button" wire:click="$set('audioFormat','flac')" class="format-pill {{ $audioFormat==='flac' ? 'format-pill-active' : '' }}">💎 FLAC</button>
                            <button type="button" wire:click="$set('audioFormat','ogg')" class="format-pill {{ $audioFormat==='ogg' ? 'format-pill-active' : '' }}">🔊 OGG</button>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-slate-400 uppercase tracking-wider font-medium">Calidad</span>
                            <button type="button" wire:click="$set('audioBitrate','best')" class="format-pill {{ $audioBitrate==='best' ? 'format-pill-active' : '' }}">Auto</button>
                            <button type="button" wire:click="$set('audioBitrate','320k')" class="format-pill {{ $audioBitrate==='320k' ? 'format-pill-active' : '' }}">320k</button>
                            <button type="button" wire:click="$set('audioBitrate','192k')" class="format-pill {{ $audioBitrate==='192k' ? 'format-pill-active' : '' }}">192k</button>
                            <button type="button" wire:click="$set('audioBitrate','128k')" class="format-pill {{ $audioBitrate==='128k' ? 'format-pill-active' : '' }}">128k</button>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex flex-col sm:flex-row gap-3">
                        <button wire:click="fetchSingleTrack" id="btn-single"
                            class="flex-1 btn-single flex items-center justify-center gap-2 py-3 px-4 rounded-xl font-medium text-sm transition-all"
                            wire:loading.attr="disabled" wire:target="fetchSingleTrack,fetchPlaylist"
                            @if($loading) disabled @endif>
                            <span wire:loading.remove wire:target="fetchSingleTrack">
                                <svg class="h-4 w-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path></svg>
                                🎵 Solo esta canción
                            </span>
                            <span wire:loading wire:target="fetchSingleTrack" class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Buscando...
                            </span>
                        </button>
                        <button wire:click="fetchPlaylist" id="btn-playlist"
                            class="flex-1 btn-playlist flex items-center justify-center gap-2 py-3 px-4 rounded-xl font-medium text-sm transition-all"
                            wire:loading.attr="disabled" wire:target="fetchSingleTrack,fetchPlaylist"
                            @if($loading) disabled @endif>
                            <span wire:loading.remove wire:target="fetchPlaylist">
                                <svg class="h-4 w-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h10M4 18h10"></path></svg>
                                📋 Toda la Playlist
                            </span>
                            <span wire:loading wire:target="fetchPlaylist" class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Cargando...
                            </span>
                        </button>
                    </div>
                </div>
                @error('url') <span class="text-red-400 text-sm mt-2 block">{{ $message }}</span> @enderror
            </div>
        @endif

        {{-- Loading --}}
        @if($loading)
            <div class="glass-card p-8 mb-6 animate-fade-in">
                <div class="flex flex-col items-center gap-4">
                    <div class="relative">
                        <div class="w-14 h-14 rounded-full border-4 border-blue-500/20 flex items-center justify-center">
                            <svg class="animate-spin h-7 w-7 text-blue-400" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        </div>
                        <div class="absolute inset-0 rounded-full border-4 border-blue-400/30 animate-ping"></div>
                    </div>
                    <p class="text-base font-semibold text-white">Buscando en YouTube...</p>
                    <div class="w-48 h-1.5 rounded-full bg-white/5 overflow-hidden">
                        <div class="h-full rounded-full bg-gradient-to-r from-blue-500 to-cyan-400 animate-loading-bar"></div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Error --}}
        @if($errorMessage && !$loading)
            <div class="glass-card p-5 mb-6 border border-red-500/30 animate-fade-in">
                <div class="flex items-start gap-3">
                    <svg class="h-5 w-5 text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>
                    <div class="flex-1">
                        <p class="text-sm text-red-400 font-semibold mb-0.5">Error</p>
                        <p class="text-sm text-slate-300">{{ $errorMessage }}</p>
                    </div>
                    <button wire:click="$set('errorMessage',null)" class="text-slate-500 hover:text-white transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
            </div>
        @endif

        {{-- ─── SINGLE TRACK PREVIEW ─── --}}
        @if($singleMode && !empty($singleTrackInfo))
            <div class="glass-card p-5 mb-6 animate-fade-in border border-emerald-500/20">
                <div class="flex items-center gap-2 mb-4">
                    <span class="text-emerald-400 text-sm font-bold uppercase tracking-wider">🎵 Canción encontrada</span>
                </div>
                <div class="flex gap-4 items-start">
                    @if(!empty($singleTrackInfo['thumbnail']))
                        <img src="{{ $singleTrackInfo['thumbnail'] }}" class="w-24 h-16 object-cover rounded-lg flex-shrink-0 border border-white/10" onerror="this.style.display='none'">
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="text-white font-semibold text-base leading-snug">{{ $singleTrackInfo['title'] }}</p>
                        <p class="text-slate-400 text-sm mt-0.5">{{ $singleTrackInfo['uploader'] }}</p>
                        <div class="flex flex-wrap gap-2 mt-2 text-xs">
                            @if($singleTrackInfo['duration'])
                                <span class="px-2 py-0.5 rounded-full bg-white/5 text-slate-400">⏱ {{ gmdate($singleTrackInfo['duration'] >= 3600 ? 'H:i:s' : 'i:s', $singleTrackInfo['duration']) }}</span>
                            @endif
                            @if($singleTrackInfo['view_count'])
                                <span class="px-2 py-0.5 rounded-full bg-white/5 text-slate-400">👁 {{ number_format($singleTrackInfo['view_count']) }}</span>
                            @endif
                            <span class="px-2 py-0.5 rounded-full bg-blue-500/15 text-blue-400 font-mono uppercase">{{ $audioFormat }} {{ $audioBitrate !== 'best' ? $audioBitrate : '' }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-4 pt-4 border-t border-white/5">
                    <button wire:click="cancelSingle" class="text-sm text-slate-500 hover:text-red-400 transition-colors px-3 py-1.5">Cancelar</button>
                    <button wire:click="startSingleDownload" class="glass-button text-sm flex items-center gap-2"
                        wire:loading.attr="disabled" wire:target="startSingleDownload">
                        <span wire:loading.remove wire:target="startSingleDownload">⬇ Descargar</span>
                        <span wire:loading wire:target="startSingleDownload" class="flex items-center gap-1">
                            <svg class="animate-spin h-3.5 w-3.5" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            Iniciando...
                        </span>
                    </button>
                </div>
            </div>
        @endif

        {{-- ─── PLAYLIST PREVIEW WITH CHECKBOXES ─── --}}
        @if($previewing && count($previewTracks) > 0)
            <div class="glass-card p-5 mb-6 animate-fade-in border border-blue-500/20">
                {{-- Header --}}
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div>
                        <p class="text-sm font-bold text-white">📋 {{ $playlistName ?: 'Playlist' }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">
                            <span class="text-emerald-400 font-semibold">{{ count($selectedTracks) }}</span> de {{ count($previewTracks) }} seleccionadas
                        </p>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <button wire:click="selectAll" class="text-xs px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-slate-300 transition-colors">✅ Todas</button>
                        <button wire:click="deselectAll" class="text-xs px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-slate-300 transition-colors">⬜ Ninguna</button>
                        <button wire:click="cancelPreview" class="text-xs text-slate-500 hover:text-red-400 transition-colors px-2 py-1.5">Cancelar</button>
                        <button wire:click="startDownload"
                            class="glass-button text-sm py-1.5 px-4 flex items-center gap-1.5 disabled:opacity-50"
                            wire:loading.attr="disabled" wire:target="startDownload"
                            @if(empty($selectedTracks)) disabled @endif>
                            <span wire:loading.remove wire:target="startDownload">
                                ⬇ Descargar {{ count($selectedTracks) > 0 ? count($selectedTracks) : '' }} ({{ strtoupper($audioFormat) }})
                            </span>
                            <span wire:loading wire:target="startDownload" class="flex items-center gap-1.5">
                                <svg class="animate-spin h-3.5 w-3.5" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                Iniciando...
                            </span>
                        </button>
                    </div>
                </div>

                {{-- Track list with checkboxes --}}
                <div class="space-y-1 max-h-96 overflow-y-auto custom-scrollbar pr-1">
                    @foreach($previewTracks as $index => $track)
                        @php $checked = in_array($index, $selectedTracks); @endphp
                        <div wire:click="toggleTrack({{ $index }})"
                            class="flex items-center gap-3 p-2.5 rounded-lg cursor-pointer transition-colors
                                {{ $checked ? 'bg-blue-500/10 hover:bg-blue-500/15' : 'bg-white/3 hover:bg-white/8' }}
                                animate-slide-up"
                            style="animation-delay: {{ $index * 20 }}ms">
                            {{-- Checkbox --}}
                            <div class="flex-shrink-0 w-5 h-5 rounded border-2 flex items-center justify-center transition-all
                                {{ $checked ? 'bg-blue-500 border-blue-500' : 'border-slate-600' }}">
                                @if($checked)
                                    <svg class="h-3 w-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                @endif
                            </div>
                            <span class="text-slate-500 text-xs font-mono w-6 text-right">{{ $index + 1 }}</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm {{ $checked ? 'text-white' : 'text-slate-400' }} font-medium truncate transition-colors">{{ $track['title'] }}</p>
                                @if($track['uploader'])
                                    <p class="text-xs text-slate-600 truncate">{{ $track['uploader'] }}</p>
                                @endif
                            </div>
                            @if($track['duration'])
                                <span class="text-xs text-slate-500 font-mono flex-shrink-0">{{ gmdate($track['duration'] >= 3600 ? 'H:i:s' : 'i:s', $track['duration']) }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ─── STATS BAR ─── --}}
        @if(count($downloads) > 0)
            @php
                $total       = count($downloads);
                $completed   = count(array_filter($downloads, fn($d) => $d['status'] === 'completed'));
                $downloading = count(array_filter($downloads, fn($d) => $d['status'] === 'downloading'));
                $queued      = count(array_filter($downloads, fn($d) => $d['status'] === 'queued'));
                $failed      = count(array_filter($downloads, fn($d) => $d['status'] === 'failed'));
                $stopped     = count(array_filter($downloads, fn($d) => $d['status'] === 'stopped'));
                $active      = $downloading + $queued;
            @endphp

            @if(!empty($playlistName))
                <div class="glass-card p-3 mb-3 flex items-center gap-2">
                    <svg class="h-4 w-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    <span class="text-white font-semibold text-sm">{{ $playlistName }}</span>
                </div>
            @endif

            <div class="glass-card p-3.5 mb-4 flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-3 text-sm">
                    <span class="text-white font-bold">{{ $total }}</span><span class="text-slate-400">canciones</span>
                    @if($completed)   <span class="text-emerald-400">✓ {{ $completed }}</span> @endif
                    @if($downloading) <span class="text-blue-400 animate-pulse">● {{ $downloading }}</span> @endif
                    @if($queued)      <span class="text-slate-400">⏳ {{ $queued }}</span> @endif
                    @if($stopped)     <span class="text-amber-400">■ {{ $stopped }}</span> @endif
                    @if($failed)      <span class="text-red-400">✗ {{ $failed }}</span> @endif
                </div>
                <div class="flex items-center gap-2">
                    @if($active)
                        <button wire:click="stopDownloads" class="text-xs bg-red-600/60 hover:bg-red-500 text-white px-3 py-1.5 rounded-lg transition-all">
                            <span wire:loading.remove wire:target="stopDownloads">■ Detener</span>
                            <span wire:loading wire:target="stopDownloads">…</span>
                        </button>
                    @endif
                    @if($completed > 1)
                        <a href="{{ route('download.all') }}" x-data="{l:false}" x-on:click="l=true"
                            class="text-xs bg-emerald-600/60 hover:bg-emerald-500 text-white px-3 py-1.5 rounded-lg transition-all">
                            <span x-text="l ? 'Generando...' : '↓ ZIP'"></span>
                        </a>
                    @endif
                    <button wire:click="clearAll" class="text-xs text-slate-500 hover:text-red-400 transition-colors">
                        <span wire:loading.remove wire:target="clearAll">Limpiar</span>
                        <span wire:loading wire:target="clearAll">…</span>
                    </button>
                </div>
            </div>
        @endif

        {{-- ─── DOWNLOADS LIST ─── --}}
        <div class="space-y-2" wire:poll.2s="fetchDownloads">
            @forelse($downloads as $id => $item)
                <div class="glass-card p-4 animate-slide-up" style="animation-delay: {{ $loop->index * 40 }}ms">
                    <div class="flex items-center gap-3">
                        {{-- Status icon --}}
                        <div class="flex-shrink-0 w-9 h-9 rounded-full flex items-center justify-center
                            @if($item['status']==='completed') bg-emerald-500/20 text-emerald-400
                            @elseif($item['status']==='downloading') bg-blue-500/20 text-blue-400
                            @elseif($item['status']==='failed') bg-red-500/20 text-red-400
                            @elseif($item['status']==='stopped') bg-amber-500/20 text-amber-400
                            @else bg-white/5 text-slate-500 @endif">
                            @if($item['status']==='completed')
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            @elseif($item['status']==='downloading')
                                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            @elseif($item['status']==='failed')
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            @elseif($item['status']==='stopped')
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><rect x="6" y="6" width="12" height="12" rx="1"></rect></svg>
                            @else
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            @endif
                        </div>

                        {{-- Info --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-1 gap-2">
                                <h3 class="text-sm font-semibold text-white truncate">{{ $item['title'] }}</h3>
                                <div class="flex items-center gap-1.5 flex-shrink-0">
                                    @if(isset($item['audio_format']))
                                        <span class="text-xs px-1.5 py-0.5 rounded font-mono
                                            @if(($item['audio_format']??'mp3')==='flac') bg-purple-500/20 text-purple-400
                                            @elseif(($item['audio_format']??'mp3')==='ogg') bg-emerald-500/20 text-emerald-400
                                            @else bg-blue-500/20 text-blue-400 @endif">
                                            {{ strtoupper($item['audio_format']??'MP3') }}
                                        </span>
                                    @endif
                                    <span class="text-xs font-medium uppercase
                                        @if($item['status']==='completed') text-emerald-400
                                        @elseif($item['status']==='downloading') text-blue-400
                                        @elseif($item['status']==='failed') text-red-400
                                        @elseif($item['status']==='stopped') text-amber-400
                                        @else text-slate-500 @endif">
                                        @if($item['status']==='completed') Listo
                                        @elseif($item['status']==='downloading') Descargando
                                        @elseif($item['status']==='failed') Error
                                        @elseif($item['status']==='stopped') Detenido
                                        @else Cola @endif
                                    </span>
                                </div>
                            </div>
                            @if(in_array($item['status'], ['downloading','completed']))
                                <div class="h-1 rounded-full bg-white/5 overflow-hidden">
                                    <div style="width:{{ $item['progress']??0 }}%" class="h-full rounded-full transition-all duration-500
                                        @if($item['status']==='completed') bg-gradient-to-r from-emerald-500 to-emerald-400
                                        @else bg-gradient-to-r from-blue-500 to-cyan-400 @endif"></div>
                                </div>
                            @endif
                            @if($item['status']==='failed' && isset($item['error']))
                                <p class="text-xs text-red-400/60 mt-1 truncate">{{ $item['error'] }}</p>
                            @endif
                        </div>

                        {{-- Actions --}}
                        @if($item['status']==='completed' && isset($item['filename']))
                            <div class="flex items-center gap-1 flex-shrink-0">
                                <button wire:click="playTrack('{{ $id }}')" title="Reproducir"
                                    class="p-2 rounded-lg transition-all hover:bg-white/10 {{ $playingTrack===$id ? 'text-blue-400 bg-blue-500/10' : 'text-slate-400 hover:text-white' }}">
                                    @if($playingTrack===$id)
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"></path></svg>
                                    @else
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"></path></svg>
                                    @endif
                                </button>
                                <a href="{{ route('track.download', ['filename'=>$item['filename']]) }}"
                                    class="p-2 rounded-lg text-slate-400 hover:text-emerald-400 hover:bg-white/10 transition-all" title="Descargar">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                </a>
                            </div>
                        @endif
                    </div>

                    {{-- Audio Player --}}
                    @if($playingTrack===$id && $item['status']==='completed' && isset($item['filename']))
                        <div class="mt-3 pt-3 border-t border-white/5">
                            <audio controls autoplay class="w-full h-10 rounded-lg" style="filter:hue-rotate(200deg) saturate(1.5)">
                                <source src="{{ route('track.play', ['filename'=>$item['filename']]) }}"
                                    type="audio/{{ match($item['audio_format']??'mp3') { 'ogg'=>'ogg','flac'=>'flac',default=>'mpeg' } }}">
                            </audio>
                        </div>
                    @endif
                </div>

            @empty
                @if(!$previewing && !$loading && !$singleMode)
                    <div class="text-center py-14 glass-card border-dashed">
                        <svg class="h-14 w-14 mx-auto text-slate-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path></svg>
                        <p class="text-slate-500">No hay descargas activas</p>
                        <p class="text-slate-600 text-sm mt-1">Pegá un link de YouTube o buscá una canción</p>
                        <button wire:click="loadExistingFiles" class="mt-4 text-sm text-blue-400 hover:text-blue-300 underline transition-colors">
                            <span wire:loading.remove wire:target="loadExistingFiles">Cargar archivos previos</span>
                            <span wire:loading wire:target="loadExistingFiles">Buscando…</span>
                        </button>
                    </div>
                @endif
            @endforelse
        </div>

    @endif {{-- end activeTab === download --}}

    {{-- ═══ TOAST ═══ --}}
    <div x-data="{ show: false, message: '' }"
        x-on:notify.window="show = true; message = $event.detail; setTimeout(() => show = false, 3000)"
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-4"
        class="fixed bottom-8 right-8 z-50 glass-card p-4 flex items-center gap-3 border-blue-500/40"
        style="display:none">
        <div class="w-6 h-6 rounded-full bg-blue-500 flex items-center justify-center flex-shrink-0">
            <svg class="h-3.5 w-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <span class="text-white text-sm font-medium" x-text="message"></span>
    </div>
</div>

<style>
@keyframes fade-in    { from { opacity:0; transform:translateY(-12px); } to { opacity:1; transform:translateY(0); } }
@keyframes slide-up   { from { opacity:0; transform:translateY(6px);  } to { opacity:1; transform:translateY(0); } }
@keyframes loading-bar{ 0%{width:0%;margin-left:0%} 50%{width:60%;margin-left:20%} 100%{width:0%;margin-left:100%} }
.animate-fade-in    { animation: fade-in 0.4s ease-out forwards; }
.animate-slide-up   { animation: slide-up 0.25s ease-out forwards; }
.animate-loading-bar{ animation: loading-bar 1.8s ease-in-out infinite; }
.custom-scrollbar::-webkit-scrollbar { width:4px; }
.custom-scrollbar::-webkit-scrollbar-track { background:transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background:rgba(255,255,255,0.1); border-radius:2px; }
.btn-single  { background:linear-gradient(135deg,rgba(16,185,129,.12),rgba(52,211,153,.06)); border:1px solid rgba(16,185,129,.3); color:#6ee7b7; }
.btn-single:hover:not(:disabled) { background:linear-gradient(135deg,rgba(16,185,129,.25),rgba(52,211,153,.15)); border-color:rgba(16,185,129,.5); color:#a7f3d0; box-shadow:0 0 16px rgba(16,185,129,.12); transform:translateY(-1px); }
.btn-single:disabled { opacity:.5; cursor:not-allowed; }
.btn-playlist{ background:linear-gradient(135deg,rgba(59,130,246,.12),rgba(99,102,241,.06)); border:1px solid rgba(59,130,246,.3); color:#93c5fd; }
.btn-playlist:hover:not(:disabled) { background:linear-gradient(135deg,rgba(59,130,246,.25),rgba(99,102,241,.15)); border-color:rgba(59,130,246,.5); color:#bfdbfe; box-shadow:0 0 16px rgba(59,130,246,.12); transform:translateY(-1px); }
.btn-playlist:disabled { opacity:.5; cursor:not-allowed; }
.format-pill { display:inline-flex;align-items:center;padding:.2rem .65rem;border-radius:9999px;font-size:.7rem;font-weight:500;border:1px solid rgba(255,255,255,.1);background:rgba(255,255,255,.04);color:#94a3b8;transition:all .15s;cursor:pointer; }
.format-pill:hover { border-color:rgba(255,255,255,.2);color:#e2e8f0; }
.format-pill-active { border-color:rgba(59,130,246,.5)!important;background:rgba(59,130,246,.15)!important;color:#93c5fd!important; }
</style>
