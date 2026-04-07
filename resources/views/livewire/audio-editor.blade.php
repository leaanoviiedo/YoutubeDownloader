<div class="max-w-4xl mx-auto py-10 px-4">
    <div class="mb-8 text-center">
        <div class="inline-flex items-center gap-3 mb-2">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-500 to-pink-500 flex items-center justify-center shadow-lg shadow-violet-500/30">
                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path></svg>
            </div>
            <h1 class="text-4xl font-extrabold tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-violet-400 via-pink-300 to-rose-400">Editor de Audio</h1>
        </div>
        <p class="text-slate-400 text-sm">Recortá, convertí y normalizá tus audios usando ffmpeg</p>
    </div>

    <div class="flex gap-1 mb-6 p-1 rounded-xl bg-white/5 border border-white/10">
        <button wire:click="$set('tool','trim')"      class="flex-1 py-2.5 px-4 rounded-lg text-sm font-medium transition-all {{ $tool==='trim'      ? 'bg-violet-600 text-white shadow' : 'text-slate-400 hover:text-white' }}">✂️ Recortar</button>
        <button wire:click="$set('tool','convert')"   class="flex-1 py-2.5 px-4 rounded-lg text-sm font-medium transition-all {{ $tool==='convert'   ? 'bg-violet-600 text-white shadow' : 'text-slate-400 hover:text-white' }}">🔄 Convertir</button>
        <button wire:click="$set('tool','normalize')" class="flex-1 py-2.5 px-4 rounded-lg text-sm font-medium transition-all {{ $tool==='normalize' ? 'bg-violet-600 text-white shadow' : 'text-slate-400 hover:text-white' }}">🔊 Normalizar</button>
        <button wire:click="loadFiles" class="px-3 rounded-lg text-slate-500 hover:text-slate-300 transition-colors text-xs" title="Actualizar lista">↺</button>
    </div>

    <div class="flex flex-wrap lg:flex-nowrap gap-6 mb-6">
        <div class="w-full lg:w-2/3 glass-card p-5 border border-white/5 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-semibold text-white">📁 Archivos disponibles</h3>
                <p class="text-xs text-slate-400 mt-0.5">{{ count($downloadedFiles) }} audios listos para editar</p>
            </div>
            <div class="flex items-center gap-2">
                <button wire:click="loadFiles" class="px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 text-slate-300 transition-colors text-xs" title="Actualizar lista">↺ Actualizar</button>
            </div>
        </div>
        
        <div class="w-full lg:w-1/3 glass-card p-5 border border-violet-500/20 bg-violet-500/5 hover:bg-violet-500/10 transition-colors relative group">
            <input type="file" wire:model="localAudio" id="localAudioInput" accept="audio/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" title="Subir audio local">
            <div class="flex items-center justify-between pointer-events-none">
                <div>
                    <h3 class="text-sm font-semibold text-violet-300 group-hover:text-violet-200">⬆️ Subir desde la PC</h3>
                    <p class="text-xs text-violet-400/70 mt-0.5">MP3, WAV, FLAC (Max 50MB)</p>
                </div>
                <div wire:loading.remove wire:target="localAudio" class="w-8 h-8 rounded-full bg-violet-500/20 flex items-center justify-center text-violet-300 group-hover:scale-110 transition-transform">
                    +
                </div>
                <div wire:loading wire:target="localAudio" class="text-violet-300">
                    <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </div>
            </div>
            @error('localAudio')<p class="text-red-400 text-xs mt-2 relative z-20">{{ $message }}</p>@enderror
        </div>
    </div>

    {{-- ── TRIM ── --}}
    @if($tool === 'trim')
    <div class="glass-card p-6 animate-fade-in border border-violet-500/20">
        <h2 class="text-sm font-bold text-violet-300 uppercase tracking-wider mb-1">✂️ Recortar Audio</h2>
        <p class="text-slate-500 text-xs mb-5">Extrae un fragmento sin re-encodear (instantáneo, sin pérdida de calidad)</p>

        <div class="space-y-4">
            <div>
                <label class="label-field">Archivo</label>
                <select wire:model="trimFile" class="glass-input w-full text-sm">
                    <option value="">-- Seleccioná un archivo --</option>
                    @foreach($downloadedFiles as $f)<option value="{{ $f }}">{{ $f }}</option>@endforeach
                </select>
                @error('trimFile')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="label-field">Inicio (HH:MM:SS)</label>
                    <input type="text" wire:model="trimStart" placeholder="00:00:00" class="glass-input w-full text-sm font-mono">
                </div>
                <div>
                    <label class="label-field">Fin (HH:MM:SS)</label>
                    <input type="text" wire:model="trimEnd" placeholder="00:01:30" class="glass-input w-full text-sm font-mono">
                    @error('trimEnd')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <button wire:click="runTrim" wire:loading.attr="disabled" wire:target="runTrim" class="w-full py-2.5 rounded-xl text-sm font-medium btn-violet flex items-center justify-center gap-2">
                <span wire:loading.remove wire:target="runTrim">✂️ Recortar</span>
                <span wire:loading wire:target="runTrim" class="flex items-center gap-1.5"><svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Procesando...</span>
            </button>
        </div>
        @if($trimError) <div class="mt-4 p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 text-xs">{{ $trimError }}</div> @endif
        @if($trimResult) @include('livewire.partials.edit-result', ['file'=>$trimResult,'color'=>'violet']) @endif
    </div>
    @endif

    {{-- ── CONVERT ── --}}
    @if($tool === 'convert')
    <div class="glass-card p-6 animate-fade-in border border-pink-500/20">
        <h2 class="text-sm font-bold text-pink-300 uppercase tracking-wider mb-1">🔄 Convertir Formato</h2>
        <p class="text-slate-500 text-xs mb-5">Convierte entre MP3, FLAC, OGG, WAV, AAC y M4A</p>

        <div class="space-y-4">
            <div>
                <label class="label-field">Archivo de entrada</label>
                <select wire:model="convertFile" class="glass-input w-full text-sm">
                    <option value="">-- Seleccioná un archivo --</option>
                    @foreach($downloadedFiles as $f)<option value="{{ $f }}">{{ $f }}</option>@endforeach
                </select>
                @error('convertFile')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="label-field">Formato destino</label>
                    <div class="flex flex-wrap gap-1.5 mt-1.5">
                        @foreach(['mp3','flac','ogg','wav','aac','m4a'] as $fmt)
                            <button type="button" wire:click="$set('convertFormat','{{ $fmt }}')" class="fpill {{ $convertFormat===$fmt ? 'fpill-pink' : '' }}">{{ strtoupper($fmt) }}</button>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label class="label-field">Bitrate (lossy)</label>
                    <div class="flex flex-wrap gap-1.5 mt-1.5">
                        @foreach(['0'=>'Auto','320k'=>'320k','192k'=>'192k','128k'=>'128k'] as $v=>$l)
                            <button type="button" wire:click="$set('convertBitrate','{{ $v }}')" class="fpill {{ $convertBitrate===$v ? 'fpill-pink' : '' }}">{{ $l }}</button>
                        @endforeach
                    </div>
                </div>
            </div>
            <button wire:click="runConvert" wire:loading.attr="disabled" wire:target="runConvert" class="w-full py-2.5 rounded-xl text-sm font-medium btn-pink flex items-center justify-center gap-2">
                <span wire:loading.remove wire:target="runConvert">🔄 Convertir a {{ strtoupper($convertFormat) }}</span>
                <span wire:loading wire:target="runConvert" class="flex items-center gap-1.5"><svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>Convirtiendo...</span>
            </button>
        </div>
        @if($convertError) <div class="mt-4 p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 text-xs">{{ $convertError }}</div> @endif
        @if($convertResult)
            <div class="mt-5 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20">
                <p class="text-emerald-400 text-sm font-semibold mb-1">✅ Conversión completada</p>
                <p class="text-slate-400 text-xs font-mono mb-3">{{ $convertResult }}</p>
                <div class="flex gap-2">
                    <button wire:click="playFile('{{ $convertResult }}')" class="text-xs px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/15 text-slate-300 transition-colors">{{ $playingFile===$convertResult ? '⏸' : '▶' }}</button>
                    <a href="{{ route('track.download',['filename'=>$convertResult]) }}" class="text-xs px-3 py-1.5 rounded-lg bg-emerald-500/20 text-emerald-400 hover:bg-emerald-500/30 transition-colors">⬇ Descargar</a>
                </div>
                @if($playingFile===$convertResult)
                    <audio controls autoplay class="w-full h-10 mt-3 rounded-lg" style="filter:hue-rotate(300deg) saturate(1.5)"><source src="{{ route('track.play',['filename'=>$convertResult]) }}"></audio>
                @endif
            </div>
        @endif
    </div>
    @endif

    {{-- ── NORMALIZE ── --}}
    @if($tool === 'normalize')
    <div class="glass-card p-6 animate-fade-in border border-rose-500/20">
        <h2 class="text-sm font-bold text-rose-300 uppercase tracking-wider mb-1">🔊 Normalizar Volumen</h2>
        <p class="text-slate-500 text-xs mb-4">Normalización EBU R128 en dos pasadas — estándar de transmisión profesional</p>
        <div class="p-3 rounded-lg bg-amber-500/10 border border-amber-500/20 mb-5">
            <p class="text-amber-400 text-xs">⚠️ Puede tardar 2-3 minutos en archivos largos (procesa dos veces)</p>
        </div>
        <div class="grid grid-cols-3 gap-3 mb-5 text-center">
            @foreach(['Target' => '-16 LUFS', 'True Peak' => '-1.5 dBTP', 'LRA' => '11 LU'] as $k => $v)
            <div class="p-3 rounded-lg bg-white/5 border border-white/10">
                <p class="text-xs text-slate-500 mb-0.5">{{ $k }}</p>
                <p class="text-sm font-bold text-white">{{ $v }}</p>
            </div>
            @endforeach
        </div>
        <div class="space-y-4">
            <div>
                <label class="label-field">Archivo</label>
                <select wire:model="normalizeFile" class="glass-input w-full text-sm">
                    <option value="">-- Seleccioná un archivo --</option>
                    @foreach($downloadedFiles as $f)<option value="{{ $f }}">{{ $f }}</option>@endforeach
                </select>
                @error('normalizeFile')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <button wire:click="runNormalize" wire:loading.attr="disabled" wire:target="runNormalize" class="w-full py-2.5 rounded-xl text-sm font-medium btn-rose flex items-center justify-center gap-2">
                <span wire:loading.remove wire:target="runNormalize">🔊 Normalizar</span>
                <span wire:loading wire:target="runNormalize" class="flex items-center gap-1.5"><svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>Analizando y normalizando...</span>
            </button>
        </div>
        @if($normalizeError) <div class="mt-4 p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 text-xs">{{ $normalizeError }}</div> @endif
        @if($normalizeResult)
            <div class="mt-5 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20">
                <p class="text-emerald-400 text-sm font-semibold mb-1">✅ Normalización completada</p>
                <p class="text-slate-400 text-xs font-mono mb-3">{{ $normalizeResult }}</p>
                <div class="flex gap-2">
                    <button wire:click="playFile('{{ $normalizeResult }}')" class="text-xs px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/15 text-slate-300 transition-colors">{{ $playingFile===$normalizeResult ? '⏸' : '▶' }}</button>
                    <a href="{{ route('track.download',['filename'=>$normalizeResult]) }}" class="text-xs px-3 py-1.5 rounded-lg bg-emerald-500/20 text-emerald-400 hover:bg-emerald-500/30 transition-colors">⬇ Descargar</a>
                </div>
                @if($playingFile===$normalizeResult)
                    <audio controls autoplay class="w-full h-10 mt-3 rounded-lg" style="filter:hue-rotate(340deg) saturate(1.5)"><source src="{{ route('track.play',['filename'=>$normalizeResult]) }}"></audio>
                @endif
            </div>
        @endif
    </div>
    @endif

    <div x-data="{show:false,msg:''}" x-on:notify.window="show=true;msg=$event.detail;setTimeout(()=>show=false,3000)"
        x-show="show" x-transition class="fixed bottom-8 right-8 z-50 glass-card p-4 flex items-center gap-3 border-violet-500/40" style="display:none">
        <div class="w-6 h-6 rounded-full bg-violet-500 flex items-center justify-center flex-shrink-0"><svg class="h-3 w-3 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"></path></svg></div>
        <span class="text-white text-sm font-medium" x-text="msg"></span>
    </div>
</div>

<style>
@keyframes fade-in{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
.animate-fade-in{animation:fade-in .35s ease-out forwards}
.label-field{display:block;font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;font-weight:500;color:#94a3b8;margin-bottom:.375rem}
.fpill{display:inline-flex;align-items:center;padding:.2rem .6rem;border-radius:9999px;font-size:.7rem;font-weight:600;border:1px solid rgba(255,255,255,.1);background:rgba(255,255,255,.04);color:#94a3b8;transition:all .15s;cursor:pointer}
.fpill:hover{border-color:rgba(255,255,255,.2);color:#e2e8f0}
.fpill-pink{border-color:rgba(236,72,153,.5)!important;background:rgba(236,72,153,.15)!important;color:#f9a8d4!important}
.btn-violet{background:linear-gradient(135deg,rgba(139,92,246,.2),rgba(167,139,250,.1));border:1px solid rgba(139,92,246,.4);color:#c4b5fd;transition:all .2s}
.btn-violet:hover:not(:disabled){background:linear-gradient(135deg,rgba(139,92,246,.35),rgba(167,139,250,.2));transform:translateY(-1px);box-shadow:0 0 18px rgba(139,92,246,.2)}
.btn-pink{background:linear-gradient(135deg,rgba(236,72,153,.2),rgba(251,113,133,.1));border:1px solid rgba(236,72,153,.4);color:#fbcfe8;transition:all .2s}
.btn-pink:hover:not(:disabled){background:linear-gradient(135deg,rgba(236,72,153,.35),rgba(251,113,133,.2));transform:translateY(-1px);box-shadow:0 0 18px rgba(236,72,153,.2)}
.btn-rose{background:linear-gradient(135deg,rgba(244,63,94,.2),rgba(251,113,133,.1));border:1px solid rgba(244,63,94,.4);color:#fecdd3;transition:all .2s}
.btn-rose:hover:not(:disabled){background:linear-gradient(135deg,rgba(244,63,94,.35),rgba(251,113,133,.2));transform:translateY(-1px);box-shadow:0 0 18px rgba(244,63,94,.2)}
button:disabled{opacity:.5;cursor:not-allowed}
</style>
