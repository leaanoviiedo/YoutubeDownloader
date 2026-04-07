<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="Descargá y editá audio de YouTube en alta calidad">
        <title>YT Downloader & Editor</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body>
        <nav class="border-b border-white/10 bg-black/20 backdrop-blur-md sticky top-0 z-40">
            <div class="max-w-5xl mx-auto px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-blue-500 to-emerald-500 flex items-center justify-center">
                        <svg class="h-4 w-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V8.69a8.27 8.27 0 004.84 1.56V6.82a4.85 4.85 0 01-1.07-.13z"/></svg>
                    </div>
                    <span class="font-bold text-white text-sm tracking-tight">YT Suite</span>
                </div>
                <div class="flex items-center gap-1">
                    <a href="{{ route('home') }}" class="px-4 py-1.5 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('home') ? 'bg-white/10 text-white' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                        📥 Downloader
                    </a>
                    <a href="{{ route('audio.editor') }}" class="px-4 py-1.5 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('audio.editor') ? 'bg-white/10 text-white' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                        🎛️ Editor
                    </a>
                </div>
            </div>
        </nav>
        <main>
            {{ $slot }}
        </main>
        @livewireScripts
    </body>
</html>
