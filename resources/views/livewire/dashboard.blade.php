<div class="max-w-4xl mx-auto py-12 px-4">
    <!-- Header -->
    <div class="mb-12 text-center animate-fade-in">
        <h1 class="text-5xl font-extrabold tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-emerald-400 mb-4">
            YT Playlist Downloader
        </h1>
        <p class="text-slate-400 text-lg">Download your favorite playlists as high-quality MP3</p>
    </div>

    <!-- URL Input -->
    <div class="glass-card p-8 mb-12 transform hover:scale-[1.01] transition-transform duration-300">
        <form wire:submit="download" class="flex flex-col md:flex-row gap-4">
            <input 
                type="text" 
                wire:model="url" 
                placeholder="Paste YouTube Playlist URL here..." 
                class="glass-input flex-1 text-lg"
            >
            <button type="submit" class="glass-button text-lg flex items-center justify-center gap-2">
                <span>Download Playlist</span>
                <svg wire:loading wire:target="download" class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </button>
        </form>
        @error('url') <span class="text-red-400 text-sm mt-2 block">{{ $message }}</span> @enderror
    </div>

    <!-- Active Downloads -->
    <div class="space-y-6" wire:poll.2s="fetchDownloads">
        @forelse($downloads as $id => $item)
            <div 
                class="glass-card p-6 flex items-center gap-6 animate-slide-up"
                style="animation-delay: {{ $loop->index * 100 }}ms"
            >
                <div class="flex-1 min-w-0">
                    <h3 class="text-lg font-semibold text-white truncate mb-2">{{ $item['title'] }}</h3>
                    
                    <div class="relative pt-1">
                        <div class="flex mb-2 items-center justify-between">
                            <div>
                                <span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full text-blue-200 bg-blue-500/30">
                                    {{ ucfirst($item['status']) }}
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-semibold inline-block text-blue-200">
                                    {{ $item['progress'] }}%
                                </span>
                            </div>
                        </div>
                        <div class="overflow-hidden h-2 mb-4 text-xs flex rounded-full bg-white/5">
                            <div 
                                style="width:{{ $item['progress'] }}%" 
                                class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-gradient-to-r from-blue-500 to-emerald-500 transition-all duration-500 shadow-[0_0_10px_rgba(59,130,246,0.5)]"
                            ></div>
                        </div>
                    </div>
                </div>

                @if($item['status'] === 'completed')
                    <div class="text-emerald-400">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                @elseif($item['status'] === 'failed')
                    <div class="text-red-400" title="{{ $item['error'] ?? 'Unknown error' }}">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                @endif
            </div>
        @empty
            <div class="text-center py-12 glass-card border-dashed">
                <p class="text-slate-500 text-lg italic">No active downloads yet. Paste a link to start!</p>
            </div>
        @endforelse
    </div>

    <!-- Toast Notifications -->
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
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in { animation: fade-in 0.8s ease-out forwards; }
.animate-slide-up { animation: slide-up 0.5s ease-out forwards; }
</style>
