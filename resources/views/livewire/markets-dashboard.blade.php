@php
    $latestSnapshot = $selectedAsset?->latestSnapshot;
    $formatPrice = fn ($value) => $value === null
        ? '0.00'
        : number_format((float) $value, (float) $value >= 1 ? 2 : 8);
@endphp

<main wire:poll.1000ms="refreshMarket" class="min-h-screen bg-[radial-gradient(circle_at_top_left,#0f766e_0,#0f172a_28rem,#09090b_58rem)]">
    <section class="mx-auto flex min-h-screen w-full max-w-7xl flex-col gap-5 px-4 py-5 sm:px-6 lg:px-8">
        <header class="flex flex-col gap-4 border-b border-white/10 pb-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-medium text-teal-300">Binance Spot / TimesFM</p>
                <h1 class="mt-1 text-3xl font-semibold tracking-normal text-white sm:text-4xl">Crypto Forecast</h1>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <a href="{{ route('markets.stats', ['symbol' => $selectedAsset?->symbol]) }}" class="h-10 rounded-md border border-white/10 px-3 py-2 text-center text-sm font-semibold text-zinc-200 hover:bg-white/[0.06]">
                    Statistics
                </a>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-md border border-white/10 bg-white/[0.06] px-3 py-2">
                    <p class="text-xs text-zinc-400">Markets</p>
                    <p class="text-lg font-semibold text-white">{{ $assets->count() }}</p>
                </div>
                <div class="rounded-md border border-white/10 bg-white/[0.06] px-3 py-2">
                    <p class="text-xs text-zinc-400">Snapshots</p>
                    <p class="text-lg font-semibold text-white">{{ $snapshots->count() }}</p>
                </div>
                <div class="rounded-md border border-white/10 bg-white/[0.06] px-3 py-2">
                    <p class="text-xs text-zinc-400">Interval</p>
                    <p class="text-lg font-semibold text-white">{{ $interval }}</p>
                </div>
                <div class="rounded-md border border-white/10 bg-white/[0.06] px-3 py-2">
                    <p class="text-xs text-zinc-400">Engine</p>
                    <p class="text-lg font-semibold text-white">{{ $forecast?->source ?? 'live' }}</p>
                </div>
                </div>
            </div>
        </header>

        @if ($notice)
            <div class="rounded-md border border-amber-300/30 bg-amber-300/10 px-4 py-3 text-sm text-amber-100">
                {{ $notice }}
            </div>
        @endif

        <div class="grid flex-1 gap-5 lg:grid-cols-[23rem_minmax(0,1fr)]">
            <section class="overflow-hidden rounded-md border border-white/10 bg-zinc-950/70">
                <div class="flex items-center justify-between border-b border-white/10 px-4 py-3">
                    <h2 class="text-sm font-semibold uppercase text-zinc-300">USDT Markets</h2>
                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                </div>

                <div class="max-h-[calc(100vh-12rem)] overflow-y-auto">
                    @forelse ($assets as $asset)
                        @php
                            $snapshot = $asset->latestSnapshot;
                            $isSelected = $selectedAsset?->is($asset);
                            $change = $snapshot
                                ? (((float) $snapshot->price - (float) $snapshot->open_price) / max((float) $snapshot->open_price, 0.00000001)) * 100
                                : 0;
                        @endphp

                        <button
                            type="button"
                            wire:key="asset-{{ $asset->symbol }}"
                            wire:click="selectAsset('{{ $asset->symbol }}')"
                            class="grid w-full grid-cols-[minmax(0,1fr)_auto] gap-3 border-b border-white/5 px-4 py-3 text-left transition {{ $isSelected ? 'bg-teal-400/12' : 'hover:bg-white/[0.04]' }}"
                        >
                            <span>
                                <span class="block text-sm font-semibold text-white">{{ $asset->display_pair }}</span>
                                <span class="mt-1 block text-xs text-zinc-400">{{ $asset->symbol }}</span>
                            </span>
                            <span class="text-right">
                                <span class="block text-sm font-semibold text-white">{{ $formatPrice($snapshot?->price) }}</span>
                                <span class="mt-1 block text-xs {{ $change >= 0 ? 'text-emerald-300' : 'text-rose-300' }}">
                                    {{ $change >= 0 ? '+' : '' }}{{ number_format($change, 2) }}%
                                </span>
                            </span>
                        </button>
                    @empty
                        <div class="px-4 py-10 text-sm text-zinc-400">No market snapshots loaded.</div>
                    @endforelse
                </div>
            </section>

            <section class="flex min-w-0 flex-col gap-5">
                <div class="rounded-md border border-white/10 bg-zinc-950/70">
                    <div class="flex flex-col gap-4 border-b border-white/10 px-4 py-4 xl:flex-row xl:items-center xl:justify-between">
                        <div>
                            <h2 class="text-2xl font-semibold text-white">{{ $selectedAsset?->display_pair ?? 'Market' }}</h2>
                            <p class="mt-1 text-sm text-zinc-400">{{ $latestSnapshot?->source_event_at?->toDayDateTimeString() ?? 'Waiting for snapshots' }}</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            @foreach ($intervalOptions as $value => $label)
                                <button
                                    type="button"
                                    wire:key="interval-{{ $value }}"
                                    wire:click="setInterval('{{ $value }}')"
                                    class="h-9 min-w-12 rounded-md border px-3 text-sm font-medium {{ $interval === $value ? 'border-teal-300 bg-teal-300 text-zinc-950' : 'border-white/10 bg-white/[0.04] text-zinc-200 hover:bg-white/[0.08]' }}"
                                >
                                    {{ $label }}
                                </button>
                            @endforeach

                            <button type="button" wire:click="loadHistory" class="h-9 rounded-md bg-white px-3 text-sm font-semibold text-zinc-950 hover:bg-zinc-200">
                                Load history
                            </button>
                        </div>
                    </div>

                    <div class="grid gap-4 px-4 py-4 md:grid-cols-4">
                        <div>
                            <p class="text-xs text-zinc-400">Last</p>
                            <p class="mt-1 text-2xl font-semibold text-white">{{ $formatPrice($latestSnapshot?->price) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-zinc-400">High</p>
                            <p class="mt-1 text-xl font-semibold text-emerald-300">{{ $formatPrice($latestSnapshot?->high_price) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-zinc-400">Low</p>
                            <p class="mt-1 text-xl font-semibold text-rose-300">{{ $formatPrice($latestSnapshot?->low_price) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-zinc-400">Quote volume</p>
                            <p class="mt-1 text-xl font-semibold text-sky-300">{{ number_format((float) ($latestSnapshot?->quote_volume ?? 0), 0) }}</p>
                        </div>
                    </div>

                    <div class="grid gap-4 px-4 pb-4 md:grid-cols-4">
                        <div>
                            <p class="text-xs text-zinc-400">Bid</p>
                            <p class="mt-1 text-lg font-semibold text-zinc-100">{{ $formatPrice($latestSnapshot?->bid_price) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-zinc-400">Ask</p>
                            <p class="mt-1 text-lg font-semibold text-zinc-100">{{ $formatPrice($latestSnapshot?->ask_price) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-zinc-400">24h Change</p>
                            <p class="mt-1 text-lg font-semibold {{ (float) ($latestSnapshot?->price_change_percent ?? 0) >= 0 ? 'text-emerald-300' : 'text-rose-300' }}">
                                {{ (float) ($latestSnapshot?->price_change_percent ?? 0) >= 0 ? '+' : '' }}{{ number_format((float) ($latestSnapshot?->price_change_percent ?? 0), 2) }}%
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-zinc-400">Trades</p>
                            <p class="mt-1 text-lg font-semibold text-zinc-100">{{ number_format((int) ($latestSnapshot?->trade_count ?? 0)) }}</p>
                        </div>
                    </div>

                    <div class="px-4 pb-4">
                        <div class="h-[22rem] overflow-hidden rounded-md border border-white/10 bg-zinc-900">
                            <svg viewBox="0 0 720 260" role="img" class="h-full w-full">
                                <rect width="720" height="260" fill="#18181b"></rect>
                                <line x1="18" y1="242" x2="702" y2="242" stroke="#3f3f46" stroke-width="1"></line>
                                <line x1="18" y1="18" x2="18" y2="242" stroke="#3f3f46" stroke-width="1"></line>

                                @if ($chart['history'])
                                    <polyline points="{{ $chart['history'] }}" fill="none" stroke="#2dd4bf" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></polyline>
                                @endif

                                @if ($chart['forecast'])
                                    <polyline points="{{ $chart['forecast'] }}" fill="none" stroke="#fbbf24" stroke-width="3" stroke-dasharray="8 7" stroke-linecap="round" stroke-linejoin="round"></polyline>
                                @endif
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_22rem]">
                    <section class="rounded-md border border-white/10 bg-zinc-950/70">
                        <div class="flex items-center justify-between border-b border-white/10 px-4 py-3">
                            <h2 class="text-sm font-semibold uppercase text-zinc-300">JSON History</h2>
                            <span class="text-xs text-zinc-500">{{ $snapshots->count() }} rows</span>
                        </div>

                        <div class="max-h-96 overflow-auto">
                            @forelse ($snapshots as $snapshot)
                                <details wire:key="snapshot-{{ $snapshot->id }}" class="border-b border-white/5 px-4 py-3">
                                    <summary class="cursor-pointer text-sm text-zinc-200">
                                        {{ $snapshot->source_event_at->format('H:i:s') }} / {{ $formatPrice($snapshot->price) }}
                                    </summary>
                                    <pre class="mt-3 overflow-auto rounded-md bg-black/40 p-3 text-xs leading-5 text-zinc-300">{{ json_encode($snapshot->raw_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                </details>
                            @empty
                                <div class="px-4 py-10 text-sm text-zinc-400">No JSON snapshots loaded.</div>
                            @endforelse
                        </div>
                    </section>

                    <section class="rounded-md border border-white/10 bg-zinc-950/70">
                        <div class="border-b border-white/10 px-4 py-3">
                            <h2 class="text-sm font-semibold uppercase text-zinc-300">Forecast</h2>
                        </div>

                        <div class="space-y-4 p-4">
                            <div class="grid grid-cols-2 gap-2">
                                @foreach ($forecastOptions as $value => $label)
                                    <button
                                        type="button"
                                        wire:key="forecast-{{ $value }}"
                                        wire:click="setForecastPeriod('{{ $value }}')"
                                        class="h-10 rounded-md border text-sm font-medium {{ $forecastPeriod === $value ? 'border-amber-300 bg-amber-300 text-zinc-950' : 'border-white/10 bg-white/[0.04] text-zinc-200 hover:bg-white/[0.08]' }}"
                                    >
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>

                            <button type="button" wire:click="runForecast" class="h-11 w-full rounded-md bg-amber-300 text-sm font-semibold text-zinc-950 hover:bg-amber-200">
                                Run forecast
                            </button>

                            @if ($forecast)
                                <div class="rounded-md border border-white/10 bg-white/[0.04] p-3">
                                    <p class="text-xs text-zinc-400">{{ $forecast->completed_at?->toDayDateTimeString() }}</p>
                                    <p class="mt-1 text-sm font-semibold text-white">{{ $forecast->source }} / {{ $forecast->horizon }} points</p>
                                    <div class="mt-3 max-h-44 overflow-auto text-xs text-zinc-300">
                                        @forelse ($forecast->point_forecast ?? [] as $index => $value)
                                            <div class="flex justify-between border-b border-white/5 py-1">
                                                <span>#{{ $index + 1 }}</span>
                                                <span>{{ $formatPrice($value) }}</span>
                                            </div>
                                        @empty
                                            <p>No forecast points stored.</p>
                                        @endforelse
                                    </div>
                                </div>
                            @else
                                <div class="rounded-md border border-white/10 bg-white/[0.04] p-3 text-sm text-zinc-400">
                                    No forecast stored.
                                </div>
                            @endif
                        </div>
                    </section>
                </div>
            </section>
        </div>
    </section>
</main>
