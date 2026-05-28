@php
    $latestSnapshot = $selectedAsset?->latestSnapshot;
    $formatPrice = fn ($value) => $value === null
        ? '0.00'
        : number_format((float) $value, (float) $value >= 1 ? 2 : 8);
@endphp

<main wire:poll.1000ms="refreshMarket" class="min-h-screen bg-[radial-gradient(circle_at_top_left,#0f766e_0,#0f172a_28rem,#09090b_58rem)]">
    <section class="mx-auto flex min-h-screen w-full max-w-[120rem] flex-col gap-5 px-4 py-5 sm:px-6 2xl:px-8">
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

        <div class="grid flex-1 gap-5 xl:grid-cols-[22rem_minmax(0,1fr)] 2xl:grid-cols-[22rem_minmax(0,1fr)_38rem]">
            <section class="overflow-hidden rounded-md border border-white/10 bg-zinc-950/70 2xl:self-start">
                <div class="flex items-center justify-between border-b border-white/10 px-4 py-3">
                    <h2 class="text-sm font-semibold uppercase text-zinc-300">USDT Markets</h2>
                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                </div>

                <div class="max-h-[calc(100vh-12rem)] overflow-y-auto 2xl:max-h-[calc(100vh-9rem)]">
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
                        <div data-interactive-chart class="relative h-[24rem] overflow-hidden rounded-md border border-white/10 bg-zinc-900 2xl:h-[34rem]">
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

                                <line data-chart-guide class="hidden" y1="18" y2="242" stroke="#f8fafc" stroke-width="1" stroke-dasharray="4 5" opacity="0.72"></line>
                                <circle data-chart-marker class="hidden" r="5" fill="#f8fafc" stroke="#18181b" stroke-width="2"></circle>
                                <rect x="0" y="0" width="720" height="260" fill="transparent"></rect>
                            </svg>
                            <div data-chart-tooltip class="pointer-events-none absolute z-20 hidden max-w-72 rounded-md border border-white/15 bg-zinc-950/95 px-3 py-2 text-xs text-zinc-100 shadow-2xl shadow-black/40"></div>
                            <script type="application/json" data-chart-payload>{!! json_encode($chart['tooltip'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
                        </div>
                    </div>
                </div>
            </section>

            <aside class="grid min-w-0 gap-5 xl:col-span-2 xl:grid-cols-[minmax(0,1fr)_26rem] 2xl:col-span-1 2xl:grid-cols-1 2xl:grid-rows-[minmax(0,1fr)_auto]">
                    <section class="min-h-0 rounded-md border border-white/10 bg-zinc-950/70">
                        <div class="flex items-center justify-between border-b border-white/10 px-4 py-3">
                            <h2 class="text-sm font-semibold uppercase text-zinc-300">Structured JSON History</h2>
                            <span class="text-xs text-zinc-500">{{ $snapshotRows->count() }} snapshots</span>
                        </div>

                        <div class="max-h-[42rem] overflow-auto 2xl:max-h-[calc(100vh-14rem)]">
                            @forelse ($snapshotRows as $snapshotRow)
                                <details wire:key="snapshot-{{ $snapshotRow['id'] }}" class="border-b border-white/5" @if ($loop->first) open @endif>
                                    <summary class="cursor-pointer px-4 py-3 text-sm text-zinc-200 transition hover:bg-white/[0.04]">
                                        <span class="grid gap-3 xl:grid-cols-[7rem_minmax(0,1fr)_6rem] 2xl:grid-cols-[6rem_minmax(0,1fr)_5rem]">
                                            <span class="font-mono text-xs text-zinc-400">{{ $snapshotRow['time'] }}</span>
                                            <span class="min-w-0">
                                                <span class="block truncate font-semibold text-white">{{ $snapshotRow['price'] }}</span>
                                                <span class="mt-1 block truncate text-xs text-zinc-500">{{ $snapshotRow['date'] }}</span>
                                            </span>
                                            <span class="text-right text-xs font-semibold {{ $snapshotRow['change_positive'] ? 'text-emerald-300' : 'text-rose-300' }}">
                                                {{ $snapshotRow['change'] }}
                                            </span>
                                        </span>
                                    </summary>

                                    <div class="space-y-4 px-4 pb-4">
                                        <div class="grid gap-2 sm:grid-cols-2 2xl:grid-cols-3">
                                            @foreach ($snapshotRow['summary'] as $field)
                                                <div class="rounded-md border border-white/10 bg-white/[0.04] px-3 py-2">
                                                    <p class="text-[0.68rem] uppercase text-zinc-500">{{ $field['label'] }}</p>
                                                    <p class="mt-1 break-words text-sm font-semibold text-zinc-100">{{ $field['value'] }}</p>
                                                </div>
                                            @endforeach
                                        </div>

                                        @foreach ($snapshotRow['sections'] as $section)
                                            <div class="rounded-md border border-white/10 bg-black/20">
                                                <div class="border-b border-white/10 px-3 py-2 text-xs font-semibold uppercase text-zinc-400">{{ $section['title'] }}</div>
                                                <div class="divide-y divide-white/5">
                                                    @foreach ($section['rows'] as $field)
                                                        <div class="grid grid-cols-[8rem_minmax(0,1fr)] gap-3 px-3 py-2 text-xs">
                                                            <span class="text-zinc-500">{{ $field['label'] }}</span>
                                                            <span class="min-w-0 break-words text-right font-medium text-zinc-100">{{ $field['value'] }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach

                                        <details class="rounded-md border border-white/10 bg-black/20">
                                            <summary class="cursor-pointer px-3 py-2 text-xs font-semibold uppercase text-zinc-400">Raw fields</summary>
                                            <div class="max-h-72 overflow-auto border-t border-white/10">
                                                @forelse ($snapshotRow['payload_rows'] as $field)
                                                    <div class="grid grid-cols-[9rem_minmax(0,1fr)] gap-3 border-b border-white/5 px-3 py-2 text-xs">
                                                        <span class="text-zinc-500">{{ $field['label'] }}</span>
                                                        <span class="min-w-0 break-all text-right font-mono text-zinc-200">{{ $field['value'] }}</span>
                                                    </div>
                                                @empty
                                                    <div class="px-3 py-4 text-xs text-zinc-500">No raw fields.</div>
                                                @endforelse
                                            </div>
                                        </details>

                                        <details class="rounded-md border border-white/10 bg-black/20">
                                            <summary class="cursor-pointer px-3 py-2 text-xs font-semibold uppercase text-zinc-400">Raw JSON</summary>
                                            <pre class="max-h-72 overflow-auto border-t border-white/10 p-3 text-xs leading-5 text-zinc-300">{{ $snapshotRow['raw_json'] }}</pre>
                                        </details>
                                    </div>
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

            </aside>
        </div>
    </section>
</main>
