<main wire:poll.visible.1000ms="refreshMarket" class="min-h-screen bg-[#0b0d10] text-zinc-100">
    <section class="mx-auto flex min-h-screen w-full max-w-[120rem] flex-col gap-4 px-4 py-4 sm:px-6 2xl:px-8">
        <header class="grid gap-4 border-b border-white/10 pb-4 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-end">
            <div>
                <p class="text-xs font-semibold uppercase text-cyan-300">Live market desk</p>
                <h1 class="mt-1 text-3xl font-semibold tracking-normal text-white sm:text-4xl">Crypto Dashboard</h1>
            </div>

            <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-md border border-white/10 bg-white/[0.04] px-3 py-2">
                    <p class="text-xs text-zinc-500">Markets</p>
                    <p class="mt-1 text-lg font-semibold text-white">{{ $assets->count() }}</p>
                </div>
                <div class="rounded-md border border-white/10 bg-white/[0.04] px-3 py-2">
                    <p class="text-xs text-zinc-500">Pinned</p>
                    <p class="mt-1 text-lg font-semibold text-amber-200">{{ $board['pinned']->count() }}</p>
                </div>
                <div class="rounded-md border border-white/10 bg-white/[0.04] px-3 py-2">
                    <p class="text-xs text-zinc-500">Interval</p>
                    <p class="mt-1 text-lg font-semibold text-cyan-200">{{ $interval }}</p>
                </div>
                <div class="rounded-md border border-white/10 bg-white/[0.04] px-3 py-2">
                    <p class="text-xs text-zinc-500">Engine</p>
                    <p class="mt-1 text-lg font-semibold text-emerald-200">{{ $forecast?->source ?? 'live' }}</p>
                </div>
            </div>
        </header>

        @if ($notice)
            <div class="rounded-md border border-amber-300/30 bg-amber-300/10 px-4 py-3 text-sm text-amber-100">
                {{ $notice }}
            </div>
        @endif

        <div class="grid flex-1 gap-4 xl:grid-cols-[24rem_minmax(0,1fr)] 2xl:grid-cols-[24rem_minmax(0,1fr)_28rem]">
            <aside class="min-h-0 overflow-hidden rounded-md border border-white/10 bg-[#111317]">
                <div class="border-b border-white/10 px-4 py-3">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-sm font-semibold uppercase text-zinc-300">Pair Finder</h2>
                        <button type="button" wire:click="clearPairSearch" class="h-8 rounded-md border border-white/10 px-2 text-xs font-semibold text-zinc-300 hover:bg-white/[0.06]">
                            Clear
                        </button>
                    </div>

                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <label class="block">
                            <span class="text-xs text-zinc-500">First currency</span>
                            <input
                                type="search"
                                wire:model.live.debounce.250ms="baseSearch"
                                class="mt-1 h-10 w-full rounded-md border border-white/10 bg-black/30 px-3 text-sm font-semibold text-white outline-none focus:border-cyan-300"
                                placeholder="BTC"
                            >
                        </label>
                        <label class="block">
                            <span class="text-xs text-zinc-500">Second currency</span>
                            <input
                                type="search"
                                wire:model.live.debounce.250ms="quoteSearch"
                                class="mt-1 h-10 w-full rounded-md border border-white/10 bg-black/30 px-3 text-sm font-semibold text-white outline-none focus:border-cyan-300"
                                placeholder="USDT"
                            >
                        </label>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-1.5">
                        @foreach ($board['base_suggestions'] as $baseAsset)
                            <button type="button" wire:key="base-chip-{{ $baseAsset }}" wire:click="setBaseSearch('{{ $baseAsset }}')" class="rounded-md bg-cyan-300/10 px-2 py-1 text-xs font-semibold text-cyan-100 hover:bg-cyan-300/20">
                                {{ $baseAsset }}
                            </button>
                        @endforeach

                        @foreach ($board['quote_suggestions'] as $quoteAsset)
                            <button type="button" wire:key="quote-chip-{{ $quoteAsset }}" wire:click="setQuoteSearch('{{ $quoteAsset }}')" class="rounded-md bg-amber-300/10 px-2 py-1 text-xs font-semibold text-amber-100 hover:bg-amber-300/20">
                                {{ $quoteAsset }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="max-h-[calc(100vh-16rem)] overflow-y-auto 2xl:max-h-[calc(100vh-14rem)]">
                    @forelse ($board['filtered'] as $row)
                        <div wire:key="market-row-{{ $row['symbol'] }}" class="grid grid-cols-[minmax(0,1fr)_3.25rem] gap-2 border-b border-white/5 px-3 py-3 {{ $row['is_selected'] ? 'bg-cyan-300/10' : '' }}">
                            <button type="button" wire:click="selectAsset('{{ $row['symbol'] }}')" class="min-w-0 text-left">
                                <span class="flex items-center gap-2">
                                    <span class="truncate text-sm font-semibold text-white">{{ $row['display_pair'] }}</span>
                                    @if ($row['is_pinned'])
                                        <span class="rounded bg-amber-300/15 px-1.5 py-0.5 text-[0.65rem] font-semibold text-amber-100">PIN</span>
                                    @endif
                                </span>
                                <span class="mt-1 grid grid-cols-[minmax(0,1fr)_4.75rem] gap-2 text-xs">
                                    <span class="truncate text-zinc-500">{{ $row['symbol'] }} · {{ $row['updated_at'] }}</span>
                                    <span class="text-right font-semibold {{ $row['change_positive'] ? 'text-emerald-300' : 'text-rose-300' }}">{{ $row['change'] }}</span>
                                </span>
                                <span class="mt-1 block text-lg font-semibold text-zinc-100">{{ $row['price'] }}</span>
                            </button>

                            @if ($row['is_pinned'])
                                <button type="button" wire:click="unpinAsset('{{ $row['symbol'] }}')" class="h-9 self-center rounded-md border border-amber-300/30 bg-amber-300/10 text-xs font-semibold text-amber-100 hover:bg-amber-300/20">
                                    Drop
                                </button>
                            @else
                                <button type="button" wire:click="pinAsset('{{ $row['symbol'] }}')" class="h-9 self-center rounded-md border border-white/10 bg-white/[0.04] text-xs font-semibold text-zinc-300 hover:bg-white/[0.08]">
                                    Pin
                                </button>
                            @endif
                        </div>
                    @empty
                        <div class="px-4 py-10 text-sm text-zinc-500">No matching markets.</div>
                    @endforelse
                </div>
            </aside>

            <section class="flex min-w-0 flex-col gap-4">
                <div class="rounded-md border border-white/10 bg-[#111317]">
                    <div class="grid gap-4 border-b border-white/10 px-4 py-4 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-center">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-3xl font-semibold text-white">{{ $board['selected']['display_pair'] ?? 'Market' }}</h2>
                                @if ($selectedAsset)
                                    <button type="button" wire:click="pinAsset('{{ $selectedAsset->symbol }}')" class="h-8 rounded-md border border-amber-300/30 bg-amber-300/10 px-3 text-xs font-semibold text-amber-100 hover:bg-amber-300/20">
                                        Pin selected
                                    </button>
                                @endif
                            </div>
                            <p class="mt-1 text-sm text-zinc-500">{{ $board['selected']['updated_full'] ?? 'Waiting for live data' }}</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            @foreach ($intervalOptions as $value => $label)
                                <button
                                    type="button"
                                    wire:key="interval-{{ $value }}"
                                    wire:click="setInterval('{{ $value }}')"
                                    class="h-9 min-w-12 rounded-md border px-3 text-sm font-medium {{ $interval === $value ? 'border-cyan-300 bg-cyan-300 text-zinc-950' : 'border-white/10 bg-white/[0.04] text-zinc-200 hover:bg-white/[0.08]' }}"
                                >
                                    {{ $label }}
                                </button>
                            @endforeach

                            <button type="button" wire:click="loadHistory" class="h-9 rounded-md bg-white px-3 text-sm font-semibold text-zinc-950 hover:bg-zinc-200">
                                Load history
                            </button>
                            <a href="{{ route('markets.stats', ['symbol' => $selectedAsset?->symbol]) }}" class="h-9 rounded-md border border-white/10 px-3 py-2 text-sm font-semibold text-zinc-200 hover:bg-white/[0.06]">
                                Statistics
                            </a>
                        </div>
                    </div>

                    <div class="grid gap-3 px-4 py-4 md:grid-cols-4">
                        <div class="rounded-md border border-white/10 bg-white/[0.035] px-3 py-3">
                            <p class="text-xs text-zinc-500">Last</p>
                            <p class="mt-1 text-2xl font-semibold text-white">{{ $board['selected']['price'] ?? '0.00' }}</p>
                        </div>
                        <div class="rounded-md border border-white/10 bg-white/[0.035] px-3 py-3">
                            <p class="text-xs text-zinc-500">Bid / Ask</p>
                            <p class="mt-1 text-lg font-semibold text-zinc-100">{{ $board['selected']['bid'] ?? '0.00' }} / {{ $board['selected']['ask'] ?? '0.00' }}</p>
                        </div>
                        <div class="rounded-md border border-white/10 bg-white/[0.035] px-3 py-3">
                            <p class="text-xs text-zinc-500">Range</p>
                            <p class="mt-1 text-lg font-semibold text-zinc-100">{{ $board['selected']['low'] ?? '0.00' }} - {{ $board['selected']['high'] ?? '0.00' }}</p>
                        </div>
                        <div class="rounded-md border border-white/10 bg-white/[0.035] px-3 py-3">
                            <p class="text-xs text-zinc-500">Volume / Trades</p>
                            <p class="mt-1 text-lg font-semibold text-zinc-100">{{ $board['selected']['quote_volume'] ?? '0' }} / {{ $board['selected']['trades'] ?? '0' }}</p>
                        </div>
                    </div>

                    <div class="px-4 pb-4">
                        <div data-interactive-chart class="relative h-[25rem] overflow-hidden rounded-md border border-white/10 bg-[#0b0d10] 2xl:h-[36rem]">
                            <svg viewBox="0 0 720 260" role="img" class="h-full w-full">
                                <rect width="720" height="260" fill="#0b0d10"></rect>
                                <line x1="18" y1="242" x2="702" y2="242" stroke="#3f3f46" stroke-width="1"></line>
                                <line x1="18" y1="18" x2="18" y2="242" stroke="#3f3f46" stroke-width="1"></line>

                                @if ($chart['history'])
                                    <polyline points="{{ $chart['history'] }}" fill="none" stroke="#22d3ee" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></polyline>
                                @endif

                                @if ($chart['forecast'])
                                    <polyline points="{{ $chart['forecast'] }}" fill="none" stroke="#fbbf24" stroke-width="3" stroke-dasharray="8 7" stroke-linecap="round" stroke-linejoin="round"></polyline>
                                @endif

                                <line data-chart-guide class="hidden" y1="18" y2="242" stroke="#f8fafc" stroke-width="1" stroke-dasharray="4 5" opacity="0.72"></line>
                                <circle data-chart-marker class="hidden" r="5" fill="#f8fafc" stroke="#0b0d10" stroke-width="2"></circle>
                                <rect x="0" y="0" width="720" height="260" fill="transparent"></rect>
                            </svg>
                            <div data-chart-tooltip class="pointer-events-none absolute z-20 hidden max-w-72 rounded-md border border-white/15 bg-zinc-950/95 px-3 py-2 text-xs text-zinc-100 shadow-2xl shadow-black/40"></div>
                            <script type="application/json" data-chart-payload>{!! json_encode($chart['tooltip'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
                        </div>
                    </div>
                </div>

                <section class="rounded-md border border-white/10 bg-[#111317]">
                    <div class="flex items-center justify-between border-b border-white/10 px-4 py-3">
                        <h2 class="text-sm font-semibold uppercase text-zinc-300">Live Ticks</h2>
                        <span class="text-xs text-zinc-500">{{ $snapshots->count() }} updates</span>
                    </div>

                    <div class="max-h-80 overflow-auto">
                        @forelse ($snapshots as $snapshot)
                            <div wire:key="tick-{{ $snapshot->id }}" class="grid grid-cols-[5rem_minmax(0,1fr)_minmax(0,1fr)_7rem] gap-3 border-b border-white/5 px-4 py-3 text-sm">
                                <span class="font-mono text-xs text-zinc-500">{{ $snapshot->source_event_at->format('H:i:s') }}</span>
                                <span class="font-semibold text-white">{{ number_format((float) $snapshot->price, (float) $snapshot->price >= 1 ? 2 : 8) }}</span>
                                <span class="text-zinc-400">{{ number_format((float) $snapshot->bid_price, (float) $snapshot->bid_price >= 1 ? 2 : 8) }} / {{ number_format((float) $snapshot->ask_price, (float) $snapshot->ask_price >= 1 ? 2 : 8) }}</span>
                                <span class="text-right text-zinc-400">{{ number_format((float) $snapshot->quote_volume, 0) }}</span>
                            </div>
                        @empty
                            <div class="px-4 py-10 text-sm text-zinc-500">No live ticks loaded.</div>
                        @endforelse
                    </div>
                </section>
            </section>

            <aside class="grid min-w-0 gap-4 xl:col-span-2 xl:grid-cols-2 2xl:col-span-1 2xl:grid-cols-1 2xl:self-start">
                <section class="rounded-md border border-white/10 bg-[#111317]">
                    <div class="flex items-center justify-between border-b border-white/10 px-4 py-3">
                        <h2 class="text-sm font-semibold uppercase text-zinc-300">Pinned Rates</h2>
                        <span class="text-xs text-zinc-500">{{ $board['pinned']->count() }}/12</span>
                    </div>

                    <div class="max-h-[24rem] overflow-auto 2xl:max-h-[36rem]">
                        @forelse ($board['pinned'] as $row)
                            <div wire:key="pinned-{{ $row['symbol'] }}" class="grid grid-cols-[minmax(0,1fr)_3.5rem] gap-2 border-b border-white/5 px-4 py-3">
                                <button type="button" wire:click="selectAsset('{{ $row['symbol'] }}')" class="min-w-0 text-left">
                                    <span class="block truncate text-sm font-semibold text-white">{{ $row['display_pair'] }}</span>
                                    <span class="mt-1 grid grid-cols-[minmax(0,1fr)_4.75rem] gap-2">
                                        <span class="truncate text-lg font-semibold text-zinc-100">{{ $row['price'] }}</span>
                                        <span class="text-right text-xs font-semibold {{ $row['change_positive'] ? 'text-emerald-300' : 'text-rose-300' }}">{{ $row['change'] }}</span>
                                    </span>
                                    <span class="mt-1 block text-xs text-zinc-500">{{ $row['symbol'] }} · {{ $row['updated_at'] }}</span>
                                </button>
                                <button type="button" wire:click="unpinAsset('{{ $row['symbol'] }}')" class="h-9 self-center rounded-md border border-white/10 text-xs font-semibold text-zinc-300 hover:bg-white/[0.06]">
                                    Drop
                                </button>
                            </div>
                        @empty
                            <div class="px-4 py-10 text-sm text-zinc-500">No pinned rates.</div>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-md border border-white/10 bg-[#111317]">
                    <div class="border-b border-white/10 px-4 py-3">
                        <h2 class="text-sm font-semibold uppercase text-zinc-300">Forecast Desk</h2>
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
                                <p class="text-xs text-zinc-500">{{ $forecast->completed_at?->toDayDateTimeString() }}</p>
                                <p class="mt-1 text-sm font-semibold text-white">{{ $forecast->source }} / {{ $forecast->horizon }} points</p>
                                <div class="mt-3 max-h-52 overflow-auto text-xs text-zinc-300">
                                    @forelse ($forecast->point_forecast ?? [] as $index => $value)
                                        <div class="flex justify-between border-b border-white/5 py-1">
                                            <span>#{{ $index + 1 }}</span>
                                            <span>{{ number_format((float) $value, (float) $value >= 1 ? 2 : 8) }}</span>
                                        </div>
                                    @empty
                                        <p>No forecast points stored.</p>
                                    @endforelse
                                </div>
                            </div>
                        @else
                            <div class="rounded-md border border-white/10 bg-white/[0.04] p-3 text-sm text-zinc-500">
                                No forecast stored.
                            </div>
                        @endif
                    </div>
                </section>
            </aside>
        </div>
    </section>
</main>
