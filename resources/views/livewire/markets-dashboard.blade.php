<main wire:poll.visible.1000ms="refreshMarket" class="min-h-screen bg-[#0b0d10] text-zinc-100">
    <section class="mx-auto flex min-h-screen w-full max-w-[120rem] flex-col gap-4 px-4 py-4 sm:px-6 2xl:px-8">
        <header class="grid gap-4 border-b border-white/10 pb-4 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-end">
            <div>
                <p class="text-xs font-semibold uppercase text-cyan-300">{{ __('ui.market.eyebrow') }}</p>
                <h1 class="mt-1 text-3xl font-semibold tracking-normal text-white sm:text-4xl">{{ __('ui.market.title') }}</h1>
            </div>

            <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-md border border-white/10 bg-white/[0.04] px-3 py-2">
                    <p class="text-xs text-zinc-500">{{ __('ui.common.markets') }}</p>
                    <p class="mt-1 text-lg font-semibold text-white">{{ $assets->count() }}</p>
                </div>
                <div class="rounded-md border border-white/10 bg-white/[0.04] px-3 py-2">
                    <p class="text-xs text-zinc-500">{{ __('ui.common.pinned') }}</p>
                    <p class="mt-1 text-lg font-semibold text-amber-200">{{ $board['pinned']->count() }}</p>
                </div>
                <div class="rounded-md border border-white/10 bg-white/[0.04] px-3 py-2">
                    <p class="text-xs text-zinc-500">{{ __('ui.common.interval') }}</p>
                    <p class="mt-1 text-lg font-semibold text-cyan-200">{{ $interval }}</p>
                </div>
                <div class="rounded-md border border-white/10 bg-white/[0.04] px-3 py-2">
                    <p class="text-xs text-zinc-500">{{ __('ui.common.engine') }}</p>
                    <p class="mt-1 text-lg font-semibold text-emerald-200">{{ $forecast?->source ?? __('ui.chart.live_price') }}</p>
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
                        <h2 class="text-sm font-semibold uppercase text-zinc-300">{{ __('ui.market.pair_finder') }}</h2>
                        <button type="button" wire:click="clearPairSearch" class="h-8 rounded-md border border-white/10 px-2 text-xs font-semibold text-zinc-300 hover:bg-white/[0.06]">
                            {{ __('ui.common.clear') }}
                        </button>
                    </div>

                    <div class="mt-3 grid gap-3">
                        <div class="grid grid-cols-2 gap-2">
                            <label class="block">
                                <span class="text-xs text-zinc-500">{{ __('ui.market.first_currency') }}</span>
                                <input
                                    type="search"
                                    wire:model.live.debounce.250ms="baseSearch"
                                    class="mt-1 h-10 w-full rounded-md border border-white/10 bg-black/30 px-3 text-sm font-semibold text-white outline-none focus:border-cyan-300"
                                    placeholder="BTC"
                                    list="base-currency-options"
                                >
                                <datalist id="base-currency-options">
                                    @foreach ($board['base_options'] as $option)
                                        <option value="{{ $option['asset'] }}">{{ $option['pin_pair'] }}</option>
                                    @endforeach
                                </datalist>
                            </label>
                            <label class="block">
                                <span class="text-xs text-zinc-500">{{ __('ui.market.second_currency') }}</span>
                                <input
                                    type="search"
                                    wire:model.live.debounce.250ms="quoteSearch"
                                    class="mt-1 h-10 w-full rounded-md border border-white/10 bg-black/30 px-3 text-sm font-semibold text-white outline-none focus:border-cyan-300"
                                    placeholder="USDT"
                                >
                            </label>
                        </div>

                        <div class="overflow-hidden rounded-md border border-white/10 bg-black/20">
                            <div class="flex items-center justify-between border-b border-white/5 px-3 py-2">
                                <span class="text-xs font-semibold uppercase text-zinc-400">{{ __('ui.market.first_currency_list') }}</span>
                                <span class="text-xs text-zinc-500">{{ __('ui.market.shown', ['count' => $board['base_options']->count()]) }}</span>
                            </div>

                            <div class="max-h-56 overflow-auto">
                                @forelse ($board['base_options'] as $option)
                                    <div wire:key="base-option-{{ $option['asset'] }}" class="grid grid-cols-[minmax(0,1fr)_3.5rem] gap-2 border-b border-white/5 px-3 py-2 {{ $option['is_selected'] ? 'bg-cyan-300/10' : '' }}">
                                        <button type="button" wire:click="setBaseSearch('{{ $option['asset'] }}')" class="min-w-0 text-left">
                                            <span class="flex items-center gap-2">
                                                <span class="text-sm font-semibold text-white">{{ $option['asset'] }}</span>
                                                @if ($option['is_pinned'])
                                                    <span class="rounded bg-amber-300/15 px-1.5 py-0.5 text-[0.65rem] font-semibold text-amber-100">{{ __('ui.market.pin_badge') }}</span>
                                                @endif
                                            </span>
                                            <span class="mt-1 grid grid-cols-[minmax(0,1fr)_4.5rem] gap-2 text-xs">
                                                <span class="truncate text-zinc-500">{{ $option['market_count'] }} · {{ $option['quote_assets'] }} · {{ $option['pin_pair'] }}</span>
                                                <span class="text-right font-semibold {{ $option['change_positive'] ? 'text-emerald-300' : 'text-rose-300' }}">{{ $option['change'] }}</span>
                                            </span>
                                            <span class="mt-1 block truncate text-xs font-semibold text-zinc-300">{{ $option['price'] }}</span>
                                        </button>

                                        @if ($option['is_pinned'])
                                            <button type="button" wire:click="unpinAsset('{{ $option['pin_symbol'] }}')" class="h-8 self-center rounded-md border border-amber-300/30 bg-amber-300/10 text-xs font-semibold text-amber-100 hover:bg-amber-300/20">
                                                {{ __('ui.market.drop') }}
                                            </button>
                                        @else
                                            <button type="button" wire:click="pinAsset('{{ $option['pin_symbol'] }}')" class="h-8 self-center rounded-md border border-white/10 bg-white/[0.04] text-xs font-semibold text-zinc-300 hover:bg-white/[0.08]">
                                                {{ __('ui.market.pin') }}
                                            </button>
                                        @endif
                                    </div>
                                @empty
                                    <div class="px-3 py-6 text-sm text-zinc-500">{{ __('ui.market.no_first_currencies') }}</div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-1.5">
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
                                        <span class="rounded bg-amber-300/15 px-1.5 py-0.5 text-[0.65rem] font-semibold text-amber-100">{{ __('ui.market.pin_badge') }}</span>
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
                                    {{ __('ui.market.drop') }}
                                </button>
                            @else
                                <button type="button" wire:click="pinAsset('{{ $row['symbol'] }}')" class="h-9 self-center rounded-md border border-white/10 bg-white/[0.04] text-xs font-semibold text-zinc-300 hover:bg-white/[0.08]">
                                    {{ __('ui.market.pin') }}
                                </button>
                            @endif
                        </div>
                    @empty
                        <div class="px-4 py-10 text-sm text-zinc-500">{{ __('ui.market.no_matching_markets') }}</div>
                    @endforelse
                </div>
            </aside>

            <section class="flex min-w-0 flex-col gap-4">
                <div class="rounded-md border border-white/10 bg-[#111317]">
                    <div class="grid gap-4 border-b border-white/10 px-4 py-4 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-center">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-3xl font-semibold text-white">{{ $board['selected']['display_pair'] ?? __('ui.common.market') }}</h2>
                                @if ($selectedAsset)
                                    <button type="button" wire:click="pinAsset('{{ $selectedAsset->symbol }}')" class="h-8 rounded-md border border-amber-300/30 bg-amber-300/10 px-3 text-xs font-semibold text-amber-100 hover:bg-amber-300/20">
                                        {{ __('ui.market.pin_selected') }}
                                    </button>
                                @endif
                            </div>
                            <p class="mt-1 text-sm text-zinc-500">{{ $board['selected']['updated_full'] ?? __('ui.common.waiting_for_live_data') }}</p>
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
                                {{ __('ui.market.load_history') }}
                            </button>
                            <a href="{{ route('markets.analyses', ['symbol' => $selectedAsset?->symbol]) }}" class="h-9 rounded-md border border-white/10 px-3 py-2 text-sm font-semibold text-zinc-200 hover:bg-white/[0.06]">
                                {{ __('ui.common.analyses') }}
                            </a>
                        </div>
                    </div>

                    <div class="grid gap-3 px-4 py-4 md:grid-cols-4">
                        <div class="rounded-md border border-white/10 bg-white/[0.035] px-3 py-3">
                            <p class="text-xs text-zinc-500">{{ __('ui.market.last') }}</p>
                            <p class="mt-1 text-2xl font-semibold text-white">{{ $board['selected']['price'] ?? '0.00' }}</p>
                        </div>
                        <div class="rounded-md border border-white/10 bg-white/[0.035] px-3 py-3">
                            <p class="text-xs text-zinc-500">{{ __('ui.market.bid_ask') }}</p>
                            <p class="mt-1 text-lg font-semibold text-zinc-100">{{ $board['selected']['bid'] ?? '0.00' }} / {{ $board['selected']['ask'] ?? '0.00' }}</p>
                        </div>
                        <div class="rounded-md border border-white/10 bg-white/[0.035] px-3 py-3">
                            <p class="text-xs text-zinc-500">{{ __('ui.market.range') }}</p>
                            <p class="mt-1 text-lg font-semibold text-zinc-100">{{ $board['selected']['low'] ?? '0.00' }} - {{ $board['selected']['high'] ?? '0.00' }}</p>
                        </div>
                        <div class="rounded-md border border-white/10 bg-white/[0.035] px-3 py-3">
                            <p class="text-xs text-zinc-500">{{ __('ui.market.volume_trades') }}</p>
                            <p class="mt-1 text-lg font-semibold text-zinc-100">{{ $board['selected']['quote_volume'] ?? '0' }} / {{ $board['selected']['trades'] ?? '0' }}</p>
                        </div>
                    </div>

                    <div class="px-4 pb-4">
                        <div data-interactive-chart data-chart-key="{{ $selectedAsset?->symbol ?? 'market' }}-{{ $interval }}" class="relative h-[25rem] overflow-hidden rounded-md border border-white/10 bg-[#0b0d10] 2xl:h-[36rem]">
                            <div data-chart-control class="absolute right-3 top-3 z-10 flex items-center gap-1 rounded-md border border-white/10 bg-zinc-950/85 p-1 shadow-lg shadow-black/30">
                                <span data-chart-zoom-label class="min-w-12 px-2 text-center text-xs font-semibold text-zinc-300">100%</span>
                                <button type="button" data-chart-zoom="out" aria-label="{{ __('ui.market.zoom_out') }}" class="h-8 w-8 rounded-md border border-white/10 text-sm font-semibold text-zinc-100 hover:bg-white/[0.08]">
                                    -
                                </button>
                                <button type="button" data-chart-zoom="in" aria-label="{{ __('ui.market.zoom_in') }}" class="h-8 w-8 rounded-md border border-white/10 text-sm font-semibold text-zinc-100 hover:bg-white/[0.08]">
                                    +
                                </button>
                                <button type="button" data-chart-zoom="reset" aria-label="{{ __('ui.market.reset_zoom') }}" class="h-8 rounded-md border border-white/10 px-2 text-xs font-semibold text-zinc-100 hover:bg-white/[0.08]">
                                    1:1
                                </button>
                            </div>
                            <svg viewBox="0 0 720 260" role="img" class="h-full w-full">
                                <rect width="720" height="260" fill="#0b0d10"></rect>
                                <line x1="18" y1="242" x2="702" y2="242" stroke="#3f3f46" stroke-width="1"></line>
                                <line x1="18" y1="18" x2="18" y2="242" stroke="#3f3f46" stroke-width="1"></line>

                                @foreach ($chart['scale_ticks'] as $tick)
                                    <line x1="18" y1="{{ $tick['y'] }}" x2="702" y2="{{ $tick['y'] }}" stroke="#27272a" stroke-width="0.75"></line>
                                    <text x="696" y="{{ $tick['y'] - 3 }}" text-anchor="end" fill="#a1a1aa" font-size="8">{{ $tick['label'] }}</text>
                                @endforeach

                                @if ($chart['history'])
                                    <polyline points="{{ $chart['history'] }}" fill="none" stroke="#22d3ee" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></polyline>
                                @endif

                                @if ($chart['latest_marker'])
                                    <circle cx="{{ $chart['latest_marker']['x'] }}" cy="{{ $chart['latest_marker']['y'] }}" r="4.5" fill="#22d3ee" stroke="#0b0d10" stroke-width="1.5"></circle>
                                    <text x="{{ min($chart['latest_marker']['x'] + 7, 612) }}" y="{{ max($chart['latest_marker']['y'] - 7, 26) }}" fill="#67e8f9" font-size="8" font-weight="600">{{ __('ui.chart.latest') }} {{ $chart['latest_marker']['value'] }}</text>
                                @endif

                                @foreach ($chart['forecast_series'] as $series)
                                    <polyline points="{{ $series['polyline'] }}" fill="none" stroke="{{ $series['color'] }}" stroke-width="2.5" stroke-dasharray="8 7" stroke-linecap="round" stroke-linejoin="round"></polyline>

                                    @foreach ($series['points'] as $point)
                                        <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="3.5" fill="{{ $series['color'] }}" stroke="#0b0d10" stroke-width="1.25"></circle>
                                    @endforeach
                                @endforeach

                                @foreach ($chart['forecast_labels'] as $label)
                                    <text x="{{ $label['x'] }}" y="{{ $label['y'] }}" fill="{{ $label['color'] }}" font-size="8" font-weight="600">{{ $label['label'] }} {{ $label['value'] }}</text>
                                @endforeach

                                <line data-chart-guide class="hidden" y1="18" y2="242" stroke="#f8fafc" stroke-width="1" stroke-dasharray="4 5" opacity="0.72"></line>
                                <circle data-chart-marker class="hidden" r="5" fill="#f8fafc" stroke="#0b0d10" stroke-width="2"></circle>
                                <rect x="0" y="0" width="720" height="260" fill="transparent"></rect>
                            </svg>
                            <div data-chart-tooltip class="pointer-events-none absolute z-20 hidden max-w-72 rounded-md border border-white/15 bg-zinc-950/95 px-3 py-2 text-xs text-zinc-100 shadow-2xl shadow-black/40"></div>
                            <script type="application/json" data-chart-payload>{!! json_encode($chart['tooltip'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
                        </div>

                        <div class="grid border-x border-b border-white/10 bg-[#0b0d10] lg:grid-cols-[minmax(0,1fr)_minmax(0,1.35fr)]">
                            <section class="border-b border-white/10 p-3 lg:border-b-0 lg:border-r">
                                <div class="flex items-center justify-between gap-3">
                                    <h3 class="text-xs font-semibold uppercase text-zinc-400">{{ __('ui.market.chart_metrics') }}</h3>
                                    <span class="text-xs text-zinc-500">{{ __('ui.market.points', ['count' => $chart['tooltip']['point_count'] ?? 0]) }}</span>
                                </div>
                                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                    @foreach ($chart['summary_cards'] as $metric)
                                        <div class="border-b border-white/5 pb-2">
                                            <p class="text-xs text-zinc-500">{{ $metric['label'] }}</p>
                                            <p class="mt-0.5 truncate text-sm font-semibold text-white">{{ $metric['value'] }}</p>
                                            <p class="mt-0.5 truncate text-xs text-zinc-500">{{ $metric['detail'] }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </section>

                            <section class="p-3">
                                <div class="flex items-center justify-between gap-3">
                                    <h3 class="text-xs font-semibold uppercase text-zinc-400">{{ __('ui.market.analyzer_lanes') }}</h3>
                                    <span class="text-xs text-zinc-500">{{ __('ui.market.engines', ['count' => count($chart['forecast_series'])]) }}</span>
                                </div>
                                <div class="mt-3 grid gap-2 md:grid-cols-2">
                                    @forelse ($chart['forecast_series'] as $series)
                                        <div wire:key="chart-lane-{{ $series['label'] }}" class="border-b border-white/5 pb-2">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="flex min-w-0 items-center gap-2">
                                                    <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background: {{ $series['color'] }}"></span>
                                                    <span class="truncate text-sm font-semibold text-white">{{ $series['label'] }}</span>
                                                </span>
                                                <span class="text-xs text-zinc-500">{{ $series['point_count'] }} pts</span>
                                            </div>
                                            <div class="mt-1 grid grid-cols-2 gap-2 text-xs">
                                                <span class="truncate text-zinc-500">{{ __('ui.chart.first') }} <span class="font-semibold text-zinc-200">{{ $series['first_value'] }}</span></span>
                                                <span class="truncate text-zinc-500">{{ __('ui.market.last') }} <span class="font-semibold text-zinc-200">{{ $series['last_value'] }}</span></span>
                                                <span class="truncate text-zinc-500">{{ __('ui.chart.move') }} <span class="font-semibold text-zinc-200">{{ $series['delta'] }}</span></span>
                                                <span class="truncate text-zinc-500">{{ __('ui.common.mape') }} <span class="font-semibold text-zinc-200">{{ $series['mape'] }}</span></span>
                                                <span class="truncate text-zinc-500">{{ __('ui.common.checked') }} <span class="font-semibold text-zinc-200">{{ $series['compared'] }}</span></span>
                                                <span class="truncate text-zinc-500">{{ __('ui.common.direction') }} <span class="font-semibold text-zinc-200">{{ $series['direction_accuracy'] }}</span></span>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="py-4 text-sm text-zinc-500">{{ __('ui.market.no_analyzer_lanes') }}</div>
                                    @endforelse
                                </div>
                            </section>
                        </div>

                        <section class="border-x border-b border-white/10 bg-[#0b0d10]">
                            <div class="flex items-center justify-between gap-3 border-b border-white/10 px-3 py-2">
                                <h3 class="text-xs font-semibold uppercase text-zinc-400">{{ __('ui.market.chart_point_ledger') }}</h3>
                                <span class="text-xs text-zinc-500">{{ __('ui.market.visible_rows', ['count' => count($chart['point_ledger'])]) }}</span>
                            </div>
                            <div class="max-h-72 overflow-auto">
                                @forelse ($chart['point_ledger'] as $index => $row)
                                    <div wire:key="chart-ledger-{{ $index }}-{{ $row['series'] }}-{{ $row['time'] }}" class="grid gap-2 border-b border-white/5 px-3 py-2 text-xs lg:grid-cols-[7rem_9rem_minmax(0,1fr)_minmax(0,1fr)_8rem]">
                                        <span class="flex min-w-0 items-center gap-2">
                                            <span class="h-2 w-2 shrink-0 rounded-full" style="background: {{ $row['color'] }}"></span>
                                            <span class="truncate font-semibold text-zinc-100">{{ $row['series'] }}</span>
                                        </span>
                                        <span class="truncate text-zinc-500">{{ $row['time'] }}</span>
                                        <span class="truncate text-zinc-400">{{ $row['detail'] }}</span>
                                        <span class="truncate text-zinc-500">{{ $row['metrics'] }}</span>
                                        <span class="text-right font-semibold text-white">{{ $row['value'] }}</span>
                                    </div>
                                @empty
                                    <div class="px-3 py-6 text-sm text-zinc-500">{{ __('ui.market.no_chart_points') }}</div>
                                @endforelse
                            </div>
                        </section>
                    </div>
                </div>

                <section class="rounded-md border border-white/10 bg-[#111317]">
                    <div class="flex items-center justify-between border-b border-white/10 px-4 py-3">
                        <h2 class="text-sm font-semibold uppercase text-zinc-300">{{ __('ui.market.live_ticks') }}</h2>
                        <span class="text-xs text-zinc-500">{{ __('ui.market.updates', ['count' => $snapshots->count()]) }}</span>
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
                            <div class="px-4 py-10 text-sm text-zinc-500">{{ __('ui.market.no_live_ticks') }}</div>
                        @endforelse
                    </div>
                </section>
            </section>

            <aside class="grid min-w-0 gap-4 xl:col-span-2 xl:grid-cols-2 2xl:col-span-1 2xl:grid-cols-1 2xl:self-start">
                <section class="rounded-md border border-white/10 bg-[#111317]">
                    <div class="flex items-center justify-between border-b border-white/10 px-4 py-3">
                        <h2 class="text-sm font-semibold uppercase text-zinc-300">{{ __('ui.market.pinned_rates') }}</h2>
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
                                    {{ __('ui.market.drop') }}
                                </button>
                            </div>
                        @empty
                            <div class="px-4 py-10 text-sm text-zinc-500">{{ __('ui.market.no_pinned_rates') }}</div>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-md border border-white/10 bg-[#111317]">
                    <div class="flex items-center justify-between border-b border-white/10 px-4 py-3">
                        <h2 class="text-sm font-semibold uppercase text-zinc-300">{{ __('ui.market.prediction_stake') }}</h2>
                        <span class="text-xs text-zinc-500">{{ __('ui.market.saved', ['count' => $predictionStakes->count()]) }}</span>
                    </div>

                    <form wire:submit.prevent="placePredictionStake" class="space-y-3 p-4">
                        <div class="grid grid-cols-2 gap-2">
                            <label class="block">
                                <span class="text-xs text-zinc-500">{{ __('ui.market.target_time') }}</span>
                                <input
                                    type="datetime-local"
                                    wire:model.live="stakeTargetAt"
                                    class="mt-1 h-10 w-full rounded-md border border-white/10 bg-black/30 px-3 text-sm font-semibold text-white outline-none focus:border-amber-300"
                                >
                            </label>
                            <label class="block">
                                <span class="text-xs text-zinc-500">{{ __('ui.market.target_price') }}</span>
                                <input
                                    type="number"
                                    min="0"
                                    step="0.00000001"
                                    wire:model.live="stakeTargetPrice"
                                    class="mt-1 h-10 w-full rounded-md border border-white/10 bg-black/30 px-3 text-sm font-semibold text-white outline-none focus:border-amber-300"
                                    placeholder="{{ $board['selected']['price'] ?? '0.00' }}"
                                >
                            </label>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            @foreach ($stakeDirectionOptions as $value => $label)
                                <button
                                    type="button"
                                    wire:key="stake-direction-{{ $value }}"
                                    wire:click="$set('stakeDirection', '{{ $value }}')"
                                    class="h-10 rounded-md border text-sm font-medium {{ $stakeDirection === $value ? 'border-amber-300 bg-amber-300 text-zinc-950' : 'border-white/10 bg-white/[0.04] text-zinc-200 hover:bg-white/[0.08]' }}"
                                >
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>

                        <div class="grid grid-cols-[minmax(0,1fr)_5rem] gap-2">
                            <label class="block">
                                <span class="text-xs text-zinc-500">{{ __('ui.market.note') }}</span>
                                <input
                                    type="text"
                                    maxlength="160"
                                    wire:model.live.debounce.250ms="stakeNote"
                                    class="mt-1 h-10 w-full rounded-md border border-white/10 bg-black/30 px-3 text-sm text-white outline-none focus:border-amber-300"
                                    placeholder="breakout"
                                >
                            </label>
                            <label class="block">
                                <span class="text-xs text-zinc-500">{{ __('ui.market.confidence') }}</span>
                                <input
                                    type="number"
                                    min="1"
                                    max="100"
                                    wire:model.live="stakeConfidence"
                                    class="mt-1 h-10 w-full rounded-md border border-white/10 bg-black/30 px-3 text-sm font-semibold text-white outline-none focus:border-amber-300"
                                >
                            </label>
                        </div>

                        <button type="submit" class="h-11 w-full rounded-md bg-amber-300 text-sm font-semibold text-zinc-950 hover:bg-amber-200">
                            {{ __('ui.market.save_prediction_stake') }}
                        </button>
                    </form>

                    <div class="max-h-72 overflow-auto border-t border-white/10">
                        @forelse ($predictionStakes as $stake)
                            @php
                                $stakeStatusClass = match ($stake->status) {
                                    'won' => 'border-emerald-300/25 bg-emerald-300/10 text-emerald-100',
                                    'lost' => 'border-rose-300/25 bg-rose-300/10 text-rose-100',
                                    default => 'border-cyan-300/25 bg-cyan-300/10 text-cyan-100',
                                };
                            @endphp
                            <div wire:key="prediction-stake-{{ $stake->id }}" class="border-b border-white/5 px-4 py-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-white">
                                            {{ $stakeDirectionOptions[$stake->direction] ?? strtoupper($stake->direction) }}
                                            {{ number_format((float) $stake->target_price, (float) $stake->target_price >= 1 ? 2 : 8) }}
                                        </p>
                                        <p class="mt-1 text-xs text-zinc-500">
                                            {{ $stake->target_at->setTimezone(config('app.timezone'))->format('M d H:i') }} · {{ $stake->confidence }}%
                                        </p>
                                    </div>
                                    <span class="rounded-md border px-2 py-1 text-[0.7rem] font-semibold uppercase {{ $stakeStatusClass }}">
                                        {{ __("ui.status.{$stake->status}") }}
                                    </span>
                                </div>

                                <div class="mt-2 grid grid-cols-2 gap-2 text-xs">
                                    <div class="rounded-md bg-white/[0.035] px-2 py-1.5">
                                        <span class="block text-zinc-500">{{ __('ui.market.entry') }}</span>
                                        <span class="font-semibold text-zinc-100">{{ $stake->entry_price ? number_format((float) $stake->entry_price, (float) $stake->entry_price >= 1 ? 2 : 8) : __('ui.common.open') }}</span>
                                    </div>
                                    <div class="rounded-md bg-white/[0.035] px-2 py-1.5">
                                        <span class="block text-zinc-500">{{ __('ui.common.actual') }}</span>
                                        <span class="font-semibold text-zinc-100">{{ $stake->actual_price ? number_format((float) $stake->actual_price, (float) $stake->actual_price >= 1 ? 2 : 8) : __('ui.common.pending') }}</span>
                                    </div>
                                </div>

                                @if ($stake->absolute_percentage_error)
                                    <p class="mt-2 text-xs text-zinc-500">
                                        {{ __('ui.common.error') }} {{ number_format((float) $stake->absolute_percentage_error, 4) }}%
                                    </p>
                                @endif

                                @if ($stake->note)
                                    <p class="mt-2 truncate text-xs text-zinc-400">{{ $stake->note }}</p>
                                @endif
                            </div>
                        @empty
                            <div class="px-4 py-8 text-sm text-zinc-500">{{ __('ui.market.no_prediction_stakes_yet') }}</div>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-md border border-white/10 bg-[#111317]">
                    <div class="border-b border-white/10 px-4 py-3">
                        <h2 class="text-sm font-semibold uppercase text-zinc-300">{{ __('ui.market.forecast_desk') }}</h2>
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
                            {{ __('ui.market.run_forecast') }}
                        </button>

                        @if ($forecasts->isNotEmpty())
                            <div class="rounded-md border border-white/10 bg-white/[0.04] p-3">
                                <p class="text-xs text-zinc-500">{{ __('ui.market.latest_analyses') }}</p>
                                <p class="mt-1 text-sm font-semibold text-white">{{ __('ui.market.engines_points', ['engines' => $forecasts->count(), 'points' => $forecast?->horizon ?? 0]) }}</p>
                                <div class="mt-3 max-h-64 overflow-auto text-xs text-zinc-300">
                                    @foreach ($forecasts as $run)
                                        <div wire:key="forecast-run-summary-{{ $run->id }}" class="border-b border-white/5 py-2">
                                            <div class="flex items-center justify-between gap-3">
                                                <span class="font-semibold text-white">{{ $run->source }}</span>
                                                <span class="text-zinc-500">{{ $run->completed_at?->format('H:i:s') }}</span>
                                            </div>
                                            <div class="mt-1 flex justify-between gap-3">
                                                <span>{{ __('ui.market.checked_points', ['checked' => $run->evaluated_points, 'total' => $run->total_points]) }}</span>
                                                <span>{{ $run->mean_absolute_percentage_error ? number_format((float) $run->mean_absolute_percentage_error, 2).'%' : __('ui.common.pending') }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="rounded-md border border-white/10 bg-white/[0.04] p-3 text-sm text-zinc-500">
                                {{ __('ui.market.no_forecast') }}
                            </div>
                        @endif
                    </div>
                </section>
            </aside>
        </div>
    </section>
</main>
