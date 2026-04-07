<div class="max-w-5xl mx-auto py-10 px-4">
    <div class="mb-8 text-center animate-fade-in">
        <div class="inline-flex items-center gap-3 mb-2">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-emerald-500 flex items-center justify-center shadow-lg shadow-blue-500/30">
                <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V8.69a8.27 8.27 0 004.84 1.56V6.82a4.85 4.85 0 01-1.07-.13z"/></svg>
            </div>
            <h1 class="text-4xl font-extrabold tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-blue-400 via-cyan-300 to-emerald-400">YT Downloader</h1>
        </div>
        <p class="text-slate-400 text-sm">Descargá canciones, playlists o buscá en el catálogo</p>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-1 mb-6 p-1 rounded-xl bg-white/5 border border-white/10">
        <button wire:click="$set('activeTab','download')" class="flex-1 py-2 px-4 rounded-lg text-sm font-medium transition-all {{ $activeTab === 'download' ? 'bg-blue-600 text-white shadow' : 'text-slate-400 hover:text-white' }}">📥 Descargar URL</button>
        <button wire:click="$set('activeTab','search')" class="flex-1 py-2 px-4 rounded-lg text-sm font-medium transition-all {{ $activeTab === 'search' ? 'bg-blue-600 text-white shadow' : 'text-slate-400 hover:text-white' }}">🔍 Buscar Título</button>
    </div>

    {{-- SEARCH --}}
    @if($activeTab === 'search')
    <div class="glass-card p-6 mb-6">
        <div class="flex gap-3 mb-5">
            <input type="text" wire:model="searchQuery" wire:keydown.enter="performSearch" placeholder="Buscá canción, artista..." class="glass-input flex-1 text-base" @if($searching) disabled @endif>
            <button wire:click="performSearch" wire:loading.attr="disabled" wire:target="performSearch" class="glass-button px-5 py-2 flex items-center gap-2">
                <span wire:loading.remove wire:target="performSearch">🔍 Buscar</span>
                <span wire:loading wire:target="performSearch">Buscando...</span>
            </button>
        </div>
        @error('searchQuery')<p class="text-red-400 text-sm mb-3">{{ $message }}</p>@enderror
        @if($errorMsg) <div class="text-red-400 text-sm p-4 bg-red-500/10 rounded-lg mb-4">{{ $errorMsg }}</div> @endif
        
        @if(!empty($searchResults))
            <div class="space-y-2 max-h-[500px] overflow-y-auto pr-1 custom-scrollbar">
                @foreach($searchResults as $res)
                <div class="flex items-center gap-3 p-3 rounded-xl bg-white/5 hover:bg-white/10 group">
                    @if(!empty($res['thumbnail'])) <img src="{{ $res['thumbnail'] }}" class="w-16 h-12 object-cover rounded flex-shrink-0"> @endif
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-white truncate">{{ $res['title'] }}</p>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $res['channel'] ?? '' }} @if($res['duration'])· {{ gmdate($res['duration']>=3600?'H:i:s':'i:s',$res['duration']) }}@endif</p>
                    </div>
                    <button wire:click="downloadSearchResult('{{ addslashes($res['url']) }}','{{ addslashes($res['title']) }}')" class="opacity-0 group-hover:opacity-100 p-2 rounded bg-emerald-500/20 text-emerald-400" title="Descargar">⬇</button>
                </div>
                @endforeach
            </div>
        @endif
    </div>
    @endif

    {{-- DOWNLOAD BY URL --}}
    @if($activeTab === 'download')
        {{-- Input URL form --}}
        @if(!$previewing)
        <div class="glass-card p-6 mb-6">
            <input type="text" wire:model="url" wire:keydown.enter="enablePreview" placeholder="Pegá la URL del video o playlist..." class="glass-input w-full text-base mb-4">
            
            <div class="flex flex-wrap items-center gap-x-6 gap-y-2 mb-4">
                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-400 uppercase tracking-wider font-medium">Formato</span>
                    @foreach(['mp3'=>'MP3','flac'=>'FLAC','ogg'=>'OGG'] as $v=>$l)
                        <button type="button" wire:click="$set('audioFormat','{{$v}}')" class="format-pill {{ $audioFormat===$v ? 'format-pill-active' : '' }}">{{$l}}</button>
                    @endforeach
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-400 uppercase tracking-wider font-medium">Calidad</span>
                    @foreach(['0'=>'Auto','320k'=>'320k','192k'=>'192k','128k'=>'128k'] as $v=>$l)
                        <button type="button" wire:click="$set('audioBitrate','{{$v}}')" class="format-pill {{ $audioBitrate===$v ? 'format-pill-active' : '' }}">{{$l}}</button>
                    @endforeach
                </div>
            </div>

            <button wire:click="enablePreview" wire:loading.attr="disabled" class="w-full py-3 rounded-xl font-medium btn-playlist">
                <span wire:loading.remove wire:target="enablePreview">🔍 Obtener Información</span>
                <span wire:loading wire:target="enablePreview">Obteniendo info del servidor...</span>
            </button>
            @error('url')<p class="text-red-400 text-sm mt-3">{{ $message }}</p>@enderror
            @if($errorMsg) <div class="text-red-400 text-sm p-4 bg-red-500/10 rounded-lg mt-4">{{ $errorMsg }}</div> @endif
        </div>
        @endif

        {{-- Preview loaded tracks --}}
        @if($previewing)
        <div class="glass-card p-5 mb-6 border border-blue-500/20">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div>
                    <p class="text-sm font-bold text-white">📋 {{ $playlistTitle ?: 'Audio' }}</p>
                    <p class="text-xs text-slate-400 mt-0.5"><span class="text-emerald-400 font-semibold">{{ count($selectedTracks) }}</span> de {{ count($previewTracks) }} listos</p>
                </div>
                <div class="flex gap-2">
                    @if(count($previewTracks) > 1)
                        <button wire:click="selectAllTracks" class="text-xs px-3 py-1.5 rounded bg-white/5 hover:bg-white/10 text-slate-300">✅ Todas</button>
                        <button wire:click="deselectAllTracks" class="text-xs px-3 py-1.5 rounded bg-white/5 hover:bg-white/10 text-slate-300">⬜ Ninguna</button>
                    @endif
                    <button wire:click="cancelPreview" class="text-xs px-3 py-1.5 rounded text-slate-500 hover:text-red-400">Cancelar</button>
                    <button wire:click="processSelected" class="text-sm px-4 py-1.5 rounded bg-blue-600 hover:bg-blue-500 text-white font-medium" @if(empty($selectedTracks)) disabled @endif>
                        ⬇ Descargar seleccionadas
                    </button>
                </div>
            </div>

            <div class="space-y-1 max-h-96 overflow-y-auto custom-scrollbar pr-1">
                @foreach($previewTracks as $i => $track)
                    @if(!empty($track['title']) && $track['title']!=='[Deleted video]' && $track['title']!=='[Private video]')
                        @php $ck = in_array($i, $selectedTracks); @endphp
                        <div wire:click="toggleTrack({{ $i }})" class="flex items-center gap-3 p-2 rounded cursor-pointer {{ $ck ? 'bg-blue-500/10' : 'bg-white/5' }}">
                            <div class="w-5 h-5 rounded border-2 flex items-center justify-center {{ $ck ? 'bg-blue-500 border-blue-500' : 'border-slate-600' }}">
                                @if($ck)<svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>@endif
                            </div>
                            @if(!empty($track['thumbnail'])) <img src="{{ $track['thumbnail'] }}" class="w-12 h-8 object-cover rounded"> @endif
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-white truncate">{{ $track['title'] }}</p>
                                <p class="text-xs text-slate-500">{{ $track['uploader'] ?? $track['channel'] ?? '' }}</p>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
            @if($errorMsg) <div class="text-red-400 text-sm p-4 bg-red-500/10 rounded-lg mt-4">{{ $errorMsg }}</div> @endif
        </div>
        @endif
    @endif

    {{-- QUEUE & DOWNLOADS (Always visible) --}}
    <livewire:download-queue />

    <div x-data="{show:false,msg:''}" x-on:notify.window="show=true;msg=$event.detail;setTimeout(()=>show=false,3000)" x-show="show" x-transition class="fixed bottom-8 right-8 z-50 glass-card p-4 flex gap-3" style="display:none">
        <span class="text-white text-sm" x-text="msg"></span>
    </div>
</div>

<style>
@keyframes fade-in{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
.animate-fade-in{animation:fade-in .35s ease-out forwards}
.custom-scrollbar::-webkit-scrollbar{width:4px}
.custom-scrollbar::-webkit-scrollbar-thumb{background:rgba(255,255,255,0.1);border-radius:2px}
.btn-playlist{background:linear-gradient(135deg,rgba(59,130,246,.12),rgba(99,102,241,.06));border:1px solid rgba(59,130,246,.3);color:#93c5fd}
.btn-playlist:hover:not(:disabled){background:linear-gradient(135deg,rgba(59,130,246,.25),rgba(99,102,241,.15));transform:translateY(-1px);color:white}
.btn-playlist:disabled{opacity:.5;cursor:not-allowed}
.format-pill{display:inline-flex;padding:.2rem .65rem;border-radius:999px;font-size:.7rem;border:1px solid rgba(255,255,255,.1);color:#94a3b8;cursor:pointer}
.format-pill:hover{border-color:rgba(255,255,255,.2);color:#e2e8f0}
.format-pill-active{border-color:rgba(59,130,246,.5)!important;background:rgba(59,130,246,.15)!important;color:#93c5fd!important}
</style>
