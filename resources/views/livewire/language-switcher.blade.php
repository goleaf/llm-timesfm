<div class="fixed bottom-4 right-4 z-50 flex items-center gap-1 rounded-md border border-white/10 bg-zinc-950/90 p-1 shadow-2xl shadow-black/40 backdrop-blur">
    @foreach ($locales as $locale => $label)
        <button
            type="button"
            wire:key="locale-{{ $locale }}"
            wire:click="setLocale('{{ $locale }}')"
            class="h-8 min-w-10 rounded-md px-2 text-xs font-semibold {{ $currentLocale === $locale ? 'bg-cyan-300 text-zinc-950' : 'text-zinc-300 hover:bg-white/[0.08]' }}"
            aria-label="{{ __('ui.language.switch_to', ['language' => $label]) }}"
        >
            {{ $label }}
        </button>
    @endforeach
</div>
