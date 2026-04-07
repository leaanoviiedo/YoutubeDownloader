<div class="mt-6">
    <div class="glass-card p-5">
        {{-- Header --}}
        @php
            $downloading = count(array_filter($downloads, fn($d) => ($d['status'] ?? '') === 'downloading'));
            $queued      = count(array_filter($downloads, fn($d) => ($d['status'] ?? '') === 'queued'));
            $done        = count(array_filter($downloads, fn($d) => ($d['status'] ?? '') === 'completed'));
        @endphp
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div>
                <p class="text-sm font-bold text-white flex items-center gap-2">
                    📥 Cola de descargas
                    @if($downloading || $queued)
                        <span class="inline-block w-2 h-2 rounded-full bg-blue-400 animate-pulse"></span>
                    @endif
                </p>
                <p class="text-xs text-slate-500 mt-0.5">
                    @if($downloading) <span class="text-blue-400">● {{ $downloading }} descargando</span> @endif
                    @if($queued) <span class="text-amber-400 ml-2">⏳ {{ $queued }} en cola</span> @endif
                    @if($done) <span class="text-emerald-400 ml-2">✓ {{ $done }} completadas</span> @endif
                    @if(!$downloading && !$queued && !$done) <span>Sin descargas activas</span> @endif
                </p>
            </div>
            @if(!empty($downloads))
            <div class="flex gap-2">
                @if($downloading || $queued)
                <button wire:click="stopDownloads" class="text-xs px-3 py-1.5 rounded bg-red-500/20 text-red-400 hover:bg-red-500/30 border border-red-500/30">
                    <span wire:loading.remove wire:target="stopDownloads">■ Detener</span>
                    <span wire:loading wire:target="stopDownloads">…</span>
                </button>
                @endif
                <button wire:click="clearAll" class="text-xs px-3 py-1.5 rounded bg-white/5 hover:bg-white/10 text-slate-400">
                    <span wire:loading.remove wire:target="clearAll">🗑 Limpiar</span>
                    <span wire:loading wire:target="clearAll">…</span>
                </button>
            </div>
            @endif
        </div>

        {{-- Empty state --}}
        @if(empty($downloads))
        <div class="text-center py-10 text-slate-700">
            <svg class="h-12 w-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            <p class="text-sm">Pegá una URL arriba para empezar a descargar</p>
        </div>
        @else
        {{-- Lista --}}
        <div class="space-y-2 max-h-[420px] overflow-y-auto pr-1 custom-scrollbar">
            @foreach($downloads as $item)
            @php $status = $item['status'] ?? 'queued'; $progress = (float)($item['progress'] ?? 0); @endphp
            <div class="flex items-center gap-3 p-3 rounded-xl bg-white/5 transition-colors">
                @if(!empty($item['thumbnail']))
                    <img src="{{ $item['thumbnail'] }}" class="w-14 h-10 object-cover rounded flex-shrink-0" onerror="this.style.display='none'">
                @else
                    <div class="w-14 h-10 rounded bg-white/5 flex-shrink-0 flex items-center justify-center text-slate-600 text-lg">🎵</div>
                @endif

                <div class="flex-1 min-w-0">
                    <p class="text-sm text-white truncate font-medium">{{ $item['title'] ?? 'Descargando...' }}</p>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="text-xs font-medium
                            {{ $status==='completed' ? 'text-emerald-400' : '' }}
                            {{ $status==='downloading' ? 'text-blue-400' : '' }}
                            {{ $status==='queued' ? 'text-amber-400' : '' }}
                            {{ $status==='failed' ? 'text-red-400' : '' }}
                            {{ $status==='stopped' ? 'text-slate-500' : '' }}
                        ">
                            @if($status==='completed') ✓ Completada
                            @elseif($status==='downloading') ↓ {{ $progress > 0 ? round($progress).'%' : 'iniciando...' }}
                            @elseif($status==='queued') ⏳ En cola
                            @elseif($status==='failed') ✗ Error
                            @else ■ Detenida @endif
                        </span>
                        @if(!empty($item['format']))<span class="text-xs px-1.5 py-0.5 rounded bg-white/5 text-slate-500">{{ $item['format'] }}</span>@endif
                        @if(!empty($item['audio_format']) && empty($item['format']))<span class="text-xs px-1.5 py-0.5 rounded bg-white/5 text-slate-500">{{ strtoupper($item['audio_format']) }}</span>@endif
                    </div>
                    @if($status==='downloading')
                    <div class="mt-1.5 h-1 rounded-full bg-white/10 overflow-hidden">
                        <div class="h-full rounded-full bg-gradient-to-r from-blue-500 to-cyan-400 transition-all duration-500"
                             style="width: {{ min($progress, 100) }}%"></div>
                    </div>
                    @endif
                </div>

                @if($status==='completed' && !empty($item['filename']))
                <div class="flex gap-1 flex-shrink-0">
                    <a href="/download/{{ urlencode($item['filename']) }}" target="_blank"
                       class="p-2 rounded-lg bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-400 text-xs" title="Descargar">⬇</a>
                    <a href="/stream/{{ urlencode($item['filename']) }}" target="_blank"
                       class="p-2 rounded-lg bg-blue-500/20 hover:bg-blue-500/30 text-blue-400 text-xs" title="Reproducir">▶</a>
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Auto-refresh cuando hay descargas activas --}}
    @if($downloading || $queued)
        <div wire:poll.2s="fetchDownloads"></div>
    @else
        <div wire:poll.5s="fetchDownloads"></div>
    @endif
</div>

<style>
.custom-scrollbar::-webkit-scrollbar{width:4px}
.custom-scrollbar::-webkit-scrollbar-thumb{background:rgba(255,255,255,.1);border-radius:2px}
</style>
