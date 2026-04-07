<x-layouts.app>
<div class="max-w-2xl mx-auto py-10 px-4">

    <div class="mb-8">
        <h1 class="text-2xl font-bold text-white">⚙️ Configuración</h1>
        <p class="text-slate-400 text-sm mt-1">Autenticación con YouTube para evitar bloqueos</p>
    </div>

    {{-- Status de cookies --}}
    @php $hasCookies = file_exists(storage_path('app/youtube_cookies.txt')); @endphp
    <div class="glass-card p-5 mb-6 {{ $hasCookies ? 'border border-emerald-500/30' : 'border border-amber-500/30' }}">
        <div class="flex items-center gap-3">
            <span class="text-2xl">{{ $hasCookies ? '✅' : '⚠️' }}</span>
            <div>
                <p class="font-semibold text-white text-sm">
                    {{ $hasCookies ? 'Cookies de YouTube activas' : 'Sin cookies de YouTube' }}
                </p>
                <p class="text-xs text-slate-400 mt-0.5">
                    {{ $hasCookies
                        ? 'El servidor usa tus cookies para autenticarse. Las descargas individuales deberían funcionar.'
                        : 'Sin cookies, YouTube puede bloquear las descargas individuales. Las playlists suelen funcionar igual.' }}
                </p>
            </div>
        </div>
        @if($hasCookies)
        <div class="mt-3 pt-3 border-t border-white/5 flex items-center justify-between">
            <p class="text-xs text-slate-500">
                Archivo: <code class="text-slate-400">storage/app/youtube_cookies.txt</code>
                ({{ round(filesize(storage_path('app/youtube_cookies.txt')) / 1024, 1) }} KB)
            </p>
            <form method="POST" action="{{ route('upload.cookies') }}">
                @csrf
                <input type="hidden" name="delete_cookies" value="1">
                <button type="button" onclick="if(confirm('¿Eliminar cookies?')) deleteCookies()" class="text-xs text-red-400 hover:text-red-300">Eliminar</button>
            </form>
        </div>
        @endif
    </div>

    {{-- Opción 1: Subir cookies.txt --}}
    <div class="glass-card p-6 mb-5">
        <h2 class="font-semibold text-white mb-1">📄 Subir archivo cookies.txt</h2>
        <p class="text-slate-400 text-xs mb-4">
            El método más simple. Descargá tus cookies de YouTube con la extensión del navegador y subila acá.
        </p>

        <ol class="text-xs text-slate-400 space-y-1 mb-5 ml-3 list-decimal">
            <li>Instalá la extensión <strong class="text-slate-300">Get cookies.txt LOCALLY</strong> en Chrome/Firefox</li>
            <li>Entrá a <a href="https://youtube.com" target="_blank" class="text-blue-400 underline">youtube.com</a> con tu cuenta de Google</li>
            <li>Hacé clic en la extensión → Export → descargá el archivo</li>
            <li>Seleccioná el archivo en el botón de abajo y subilo</li>
        </ol>

        <form method="POST" action="{{ route('upload.cookies') }}" enctype="multipart/form-data">
            @csrf
            <div id="drop-zone" class="border-2 border-dashed border-white/20 rounded-xl p-8 text-center cursor-pointer hover:border-blue-400/50 transition-colors mb-4">
                <input type="file" name="cookies" id="cookie-file" accept=".txt" class="hidden" onchange="updateFile(this)">
                <label for="cookie-file" class="cursor-pointer">
                    <p class="text-3xl mb-2">📁</p>
                    <p class="text-sm text-slate-300" id="file-label">Clic para seleccionar o arrastrá el archivo</p>
                    <p class="text-xs text-slate-600 mt-1">Solo archivos .txt</p>
                </label>
            </div>
            @error('cookies')<p class="text-red-400 text-sm mb-3">{{ $message }}</p>@enderror
            <button type="submit" id="upload-btn" disabled
                class="w-full py-3 rounded-xl font-medium text-sm bg-blue-600 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-blue-500 text-white transition-all">
                ⬆️ Subir cookies.txt
            </button>
        </form>
    </div>

    {{-- Nota explicativa --}}
    <div class="glass-card p-5 border border-white/5">
        <h3 class="text-sm font-semibold text-white mb-2">ℹ️ ¿Por qué pide cookies?</h3>
        <p class="text-xs text-slate-400 leading-relaxed">
            YouTube detecta que las solicitudes vienen de un servidor (no de un navegador real) y las bloquea.
            Al pasar cookies de una cuenta autenticada, yt-dlp puede identificarse como un usuario real y evitar el bloqueo.
            Las cookies <strong class="text-slate-300">no dan acceso a tu cuenta</strong> — solo le dicen a YouTube que sos un usuario verificado.
        </p>
        <p class="text-xs text-slate-500 mt-3">
            💡 Las playlists generalmente funcionan sin cookies porque usan la API de YouTube directamente.
        </p>
    </div>
</div>

<script>
function updateFile(input) {
    const label = document.getElementById('file-label');
    const btn   = document.getElementById('upload-btn');
    if (input.files && input.files[0]) {
        label.textContent = '✅ ' + input.files[0].name;
        btn.disabled = false;
    }
}
// Drag and drop
const zone = document.getElementById('drop-zone');
zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('border-blue-400/60'); });
zone.addEventListener('dragleave', () => zone.classList.remove('border-blue-400/60'));
zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('border-blue-400/60');
    const file = e.dataTransfer.files[0];
    if (file) {
        const dt = new DataTransfer();
        dt.items.add(file);
        document.getElementById('cookie-file').files = dt.files;
        updateFile(document.getElementById('cookie-file'));
    }
});
</script>
</x-layouts.app>
