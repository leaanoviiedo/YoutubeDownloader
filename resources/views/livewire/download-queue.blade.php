<div>
    @if(!empty($downloads))
    <div class="glass-card p-5 mt-6">
        {{-- Header con controles --}}
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            @php
                $downloading = count(array_filter($downloads, fn($d) => $d['status'] === 'downloading'));
                $queued      = count(array_filter($downloads, fn($d) => $d['status'] === 'queued'));
                $done        = count(array_filter($downloads, fn($d) => $d['status'] === 'completed'));
            @endphp
            <div>
                <p class="text-sm font-bold text-white">📥 Cola de descargas</p>
                <p class="text-xs text-slate-500 mt-0.5">
                    @if($downloading) <span class="text-blue-400 animate-pulse">● {{ $downloading }} descargando</span> @endif
                    @if($queued) <span class="text-amber-400 ml-2">⏳ {{ $queued }} en cola</span> @endif
                    @if($done) <span class="text-emerald-400 ml-2">✓ {{ $done }} completadas</span> @endif
                </p>
            </div>
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
        </div>

        {{-- Lista de descargas --}}
        <div class="space-y-2 max-h-[420px] overflow-y-auto pr-1 custom-scrollbar">
            @foreach($downloads as $item)
            <div class="flex items-center gap-3 p-3 rounded-xl bg-white/5">
                @if(!empty($item['thumbnail']))
                    <img src="{{ $item['thumbnail'] }}" class="w-14 h-10 object-cover rounded flex-shrink-0" onerror="this.style.display='none'">
                @endif

                <div class="flex-1 min-w-0">
                    <p class="text-sm text-white truncate font-medium">{{ $item['title'] ?? 'Sin título' }}</p>
                    <div class="flex items-center gap-2 mt-0.5">
                        @php $status = $item['status'] ?? 'queued'; @endphp
                        <span class="text-xs font-medium
                            {{ $status === 'completed' ? 'text-emerald-400' : '' }}
                            {{ $status === 'downloading' ? 'text-blue-400' : '' }}
                            {{ $status === 'queued' ? 'text-amber-400' : '' }}
                            {{ $status === 'failed' ? 'text-red-400' : '' }}
                            {{ $status === 'stopped' ? 'text-slate-500' : '' }}
                        ">
                            @if($status === 'completed') ✓ Completada
                            @elseif($status === 'downloading') ↓ {{ $item['progress'] ?? '' }}
                            @elseif($status === 'queued') ⏳ En cola
                            @elseif($status === 'failed') ✗ Error
                            @else ■ Detenida
                            @endif
                        </span>
                        @if(!empty($item['format']))
                            <span class="text-xs px-1.5 py-0.5 rounded bg-white/5 text-slate-500">{{ $item['format'] }}</span>
                        @endif
                    </div>

                    @if($status === 'downloading')
                    <div class="mt-1.5 h-1 rounded-full bg-white/10 overflow-hidden w-full">
                        <div class="h-full rounded-full bg-gradient-to-r from-blue-500 to-cyan-400 transition-all"
                             style="width: {{ preg_replace('/[^0-9.]/', '', $item['progress'] ?? '0') . '%' }}"></div>
                    </div>
                    @endif
                </div>

                {{-- Acciones --}}
                @if($status === 'completed' && !empty($item['file']))
                <div class="flex gap-1 flex-shrink-0">
                    <a href="/download/{{ urlencode($item['file']) }}" target="_blank"
                       class="p-2 rounded-lg bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-400 text-xs" title="Descargar">
                        ⬇
                    </a>
                    <a href="/stream/{{ urlencode($item['file']) }}" target="_blank"
                       class="p-2 rounded-lg bg-blue-500/20 hover:bg-blue-500/30 text-blue-400 text-xs" title="Reproducir">
                        ▶
                    </a>
                </div>
                @endif
            </div>
            @endforeach
        </div>

        {{-- Auto-refresh JS --}}
        <div wire:poll.3s="fetchDownloads"></div>
    </div>
    @endif
</div>
