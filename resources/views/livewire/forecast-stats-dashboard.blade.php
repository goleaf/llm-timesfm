@php
    $formatPrice = fn ($value) => $value === null
        ? __('ui.common.na')
        : number_format((float) $value, abs((float) $value) >= 1 ? 2 : 8);
    $formatPercent = fn ($value) => $value === null ? __('ui.common.na') : number_format((float) $value, 2).'%';
    $formatInteger = fn ($value) => $value === null ? __('ui.common.na') : number_format((int) $value);
    $formatDateTime = fn ($value) => $value instanceof \DateTimeInterface
        ? $value->format('Y-m-d H:i:s')
        : ($value ?: __('ui.common.na'));
    $formatTimeWindow = fn ($start, $end) => $start instanceof \DateTimeInterface && $end instanceof \DateTimeInterface
        ? $start->format('m-d H:i').' - '.$end->format('m-d H:i')
        : __('ui.common.na');
    $directionLabel = fn ($value) => $value === null ? __('ui.common.na') : ($value ? __('ui.common.correct') : __('ui.common.wrong'));
    $directionClass = fn ($value) => $value === null
        ? 'text-zinc-400'
        : ($value ? 'text-emerald-300' : 'text-rose-300');
    $snapshot = $selectedAsset?->latestSnapshot;
@endphp

<main wire:poll.visible.1000ms="refreshStats" class="min-h-screen bg-[#0b0d10]">
    <section class="mx-auto flex min-h-screen w-full max-w-[120rem] flex-col gap-5 px-4 py-5 sm:px-6 2xl:px-8">
        <header class="flex flex-col gap-4 border-b border-white/10 pb-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-medium text-cyan-300">{{ __('ui.stats.eyebrow') }}</p>
                <h1 class="mt-1 text-3xl font-semibold tracking-normal text-white sm:text-4xl">{{ __('ui.stats.title') }}</h1>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('markets.show', ['symbol' => $selectedAsset?->symbol]) }}" class="h-10 rounded-md border border-white/10 px-3 py-2 text-sm font-semibold text-zinc-200 hover:bg-white/[0.06]">
                    {{ __('ui.common.markets') }}
                </a>

                @forelse ($intervalOptions as $value => $label)
                    <button
                        type="button"
                        wire:key="stats-interval-{{ $value }}"
                        wire:click="setInterval('{{ $value }}')"
                        class="h-10 min-w-12 rounded-md border px-3 text-sm font-medium {{ $interval === $value ? 'border-cyan-300 bg-cyan-300 text-zinc-950' : 'border-white/10 bg-white/[0.04] text-zinc-200 hover:bg-white/[0.08]' }}"
                    >
                        {{ $label }}
                    </button>
                @empty
                    <span class="text-sm text-zinc-500">{{ __('ui.common.no_intervals') }}</span>
                @endforelse
            </div>
        </header>

        <div class="grid gap-5 lg:grid-cols-[22rem_minmax(0,1fr)] 2xl:grid-cols-[23rem_minmax(0,1fr)]">
            <section class="overflow-hidden rounded-md border border-white/10 bg-[#111317]">
                <div class="flex items-center justify-between border-b border-white/10 px-4 py-3">
                    <h2 class="text-sm font-semibold uppercase text-zinc-300">{{ __('ui.common.markets') }}</h2>
                    <span class="text-xs text-zinc-500">{{ $assets->count() }}</span>
                </div>

                <div class="max-h-[calc(100vh-11rem)] overflow-y-auto">
                    @forelse ($assets as $asset)
                        @php
                            $isSelected = $selectedAsset?->is($asset);
                            $assetSnapshot = $asset->latestSnapshot;
                        @endphp

                        <button
                            type="button"
                            wire:key="stats-asset-{{ $asset->symbol }}"
                            wire:click="selectAsset('{{ $asset->symbol }}')"
                            class="grid w-full grid-cols-[minmax(0,1fr)_auto] gap-3 border-b border-white/5 px-4 py-3 text-left transition {{ $isSelected ? 'bg-cyan-400/12' : 'hover:bg-white/[0.04]' }}"
                        >
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-semibold text-white">{{ $asset->display_pair }}</span>
                                <span class="mt-1 block truncate text-xs text-zinc-400">{{ $asset->symbol }} · #{{ $asset->rank }}</span>
                                <span class="mt-1 block truncate text-xs text-zinc-500">{{ $formatDateTime($assetSnapshot?->source_event_at) }}</span>
                            </span>
                            <span class="min-w-24 text-right">
                                <span class="block truncate text-sm font-semibold text-zinc-100">{{ $formatPrice($assetSnapshot?->price) }}</span>
                                <span class="mt-1 block truncate text-xs {{ (float) ($assetSnapshot?->price_change_percent ?? 0) >= 0 ? 'text-emerald-300' : 'text-rose-300' }}">
                                    {{ $formatPercent($assetSnapshot?->price_change_percent) }}
                                </span>
                            </span>
                        </button>
                    @empty
                        <div class="px-4 py-10 text-sm text-zinc-400">{{ __('ui.common.no_markets_loaded') }}</div>
                    @endforelse
                </div>
            </section>

            <section class="flex min-w-0 flex-col gap-5">
                <div class="rounded-md border border-white/10 bg-[#111317]">
                    <div class="grid gap-4 border-b border-white/10 px-4 py-4 xl:grid-cols-[minmax(0,1fr)_minmax(28rem,44rem)] xl:items-start">
                        <div>
                            <h2 class="text-2xl font-semibold text-white">{{ $selectedAsset?->display_pair ?? __('ui.common.market') }}</h2>
                            <p class="mt-1 text-sm text-zinc-400">{{ __('ui.stats.subtitle', ['interval' => $interval, 'count' => $points->count()]) }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-2 md:grid-cols-4">
                            <div class="border-b border-white/10 pb-2">
                                <p class="text-xs text-zinc-500">{{ __('ui.stats.base_asset') }}</p>
                                <p class="mt-1 truncate text-sm font-semibold text-white">{{ $selectedAsset?->base_asset ?? __('ui.common.na') }}</p>
                            </div>
                            <div class="border-b border-white/10 pb-2">
                                <p class="text-xs text-zinc-500">{{ __('ui.stats.quote_asset') }}</p>
                                <p class="mt-1 truncate text-sm font-semibold text-white">{{ $selectedAsset?->quote_asset ?? __('ui.common.na') }}</p>
                            </div>
                            <div class="border-b border-white/10 pb-2">
                                <p class="text-xs text-zinc-500">{{ __('ui.stats.rank') }}</p>
                                <p class="mt-1 truncate text-sm font-semibold text-white">{{ $selectedAsset?->rank ?? __('ui.common.na') }}</p>
                            </div>
                            <div class="border-b border-white/10 pb-2">
                                <p class="text-xs text-zinc-500">{{ __('ui.stats.updated_at') }}</p>
                                <p class="mt-1 truncate text-sm font-semibold text-white">{{ $formatDateTime($snapshot?->source_event_at) }}</p>
                            </div>
                            <div class="border-b border-white/10 pb-2">
                                <p class="text-xs text-zinc-500">{{ __('ui.stats.last_price') }}</p>
                                <p class="mt-1 truncate text-sm font-semibold text-white">{{ $formatPrice($snapshot?->price) }}</p>
                            </div>
                            <div class="border-b border-white/10 pb-2">
                                <p class="text-xs text-zinc-500">{{ __('ui.market.range') }}</p>
                                <p class="mt-1 truncate text-sm font-semibold text-white">{{ $formatPrice($snapshot?->low_price) }} - {{ $formatPrice($snapshot?->high_price) }}</p>
                            </div>
                            <div class="border-b border-white/10 pb-2">
                                <p class="text-xs text-zinc-500">{{ __('ui.stats.quote_volume') }}</p>
                                <p class="mt-1 truncate text-sm font-semibold text-white">{{ $formatInteger($snapshot?->quote_volume) }}</p>
                            </div>
                            <div class="border-b border-white/10 pb-2">
                                <p class="text-xs text-zinc-500">{{ __('ui.stats.trade_count') }}</p>
                                <p class="mt-1 truncate text-sm font-semibold text-white">{{ $formatInteger($snapshot?->trade_count) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 border-b border-white/10 p-4 md:grid-cols-4 2xl:grid-cols-10">
                        <div class="rounded-md border border-white/10 bg-white/[0.04] px-3 py-2">
                            <p class="text-xs text-zinc-400">{{ __('ui.stats.forecasts') }}</p>
                            <p class="text-lg font-semibold text-white">{{ $metrics['forecasts'] }}</p>
                        </div>
                        <div class="rounded-md border border-white/10 bg-white/[0.04] px-3 py-2">
                            <p class="text-xs text-zinc-400">{{ __('ui.stats.sources') }}</p>
                            <p class="text-lg font-semibold text-white">{{ $metrics['source_count'] }}</p>
                        </div>
                        <div class="rounded-md border border-white/10 bg-white/[0.04] px-3 py-2">
                            <p class="text-xs text-zinc-400">{{ __('ui.stats.evaluated') }}</p>
                            <p class="text-lg font-semibold text-white">{{ $metrics['evaluated_points'] }}</p>
                        </div>
                        <div class="rounded-md border border-white/10 bg-white/[0.04] px-3 py-2">
                            <p class="text-xs text-zinc-400">{{ __('ui.stats.pending_points') }}</p>
                            <p class="text-lg font-semibold text-amber-200">{{ $metrics['pending_points'] }}</p>
                        </div>
                        <div class="rounded-md border border-white/10 bg-white/[0.04] px-3 py-2">
                            <p class="text-xs text-zinc-400">{{ __('ui.stats.coverage') }}</p>
                            <p class="text-lg font-semibold text-cyan-200">{{ $formatPercent($metrics['coverage']) }}</p>
                        </div>
                        <div class="rounded-md border border-white/10 bg-white/[0.04] px-3 py-2">
                            <p class="text-xs text-zinc-400">{{ __('ui.common.mape') }}</p>
                            <p class="text-lg font-semibold text-cyan-200">{{ $formatPercent($metrics['mape']) }}</p>
                        </div>
                        <div class="rounded-md border border-white/10 bg-white/[0.04] px-3 py-2">
                            <p class="text-xs text-zinc-400">{{ __('ui.stats.mae') }}</p>
                            <p class="text-lg font-semibold text-white">{{ $formatPrice($metrics['mae']) }}</p>
                        </div>
                        <div class="rounded-md border border-white/10 bg-white/[0.04] px-3 py-2">
                            <p class="text-xs text-zinc-400">{{ __('ui.common.direction') }}</p>
                            <p class="text-lg font-semibold text-emerald-200">{{ $formatPercent($metrics['direction_accuracy']) }}</p>
                        </div>
                        <div class="rounded-md border border-white/10 bg-white/[0.04] px-3 py-2">
                            <p class="text-xs text-zinc-400">{{ __('ui.stats.best_mape') }}</p>
                            <p class="text-lg font-semibold text-emerald-200">{{ $formatPercent($metrics['best_mape']) }}</p>
                        </div>
                        <div class="rounded-md border border-white/10 bg-white/[0.04] px-3 py-2">
                            <p class="text-xs text-zinc-400">{{ __('ui.stats.worst_mape') }}</p>
                            <p class="text-lg font-semibold text-rose-200">{{ $formatPercent($metrics['worst_mape']) }}</p>
                        </div>
                    </div>

                    <div class="grid gap-4 p-4 xl:grid-cols-[minmax(0,1fr)_20rem] 2xl:grid-cols-[minmax(0,1fr)_26rem]">
                        <div data-interactive-chart class="relative h-[24rem] overflow-hidden rounded-md border border-white/10 bg-[#0b0d10] 2xl:h-[34rem]">
                            <svg viewBox="0 0 720 260" role="img" class="h-full w-full">
                                <rect width="720" height="260" fill="#09090b"></rect>
                                <line x1="18" y1="242" x2="702" y2="242" stroke="#3f3f46" stroke-width="1"></line>
                                <line x1="18" y1="18" x2="18" y2="242" stroke="#3f3f46" stroke-width="1"></line>

                                @if ($chart['predicted'])
                                    <polyline points="{{ $chart['predicted'] }}" fill="none" stroke="#fbbf24" stroke-width="3" stroke-dasharray="8 7" stroke-linecap="round" stroke-linejoin="round"></polyline>
                                @endif

                                @if ($chart['actual'])
                                    <polyline points="{{ $chart['actual'] }}" fill="none" stroke="#22d3ee" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></polyline>
                                @endif

                                <line data-chart-guide class="hidden" y1="18" y2="242" stroke="#f8fafc" stroke-width="1" stroke-dasharray="4 5" opacity="0.72"></line>
                                <circle data-chart-marker class="hidden" r="5" fill="#f8fafc" stroke="#09090b" stroke-width="2"></circle>
                                <rect x="0" y="0" width="720" height="260" fill="transparent"></rect>
                            </svg>
                            <div data-chart-tooltip class="pointer-events-none absolute z-20 hidden max-w-72 rounded-md border border-white/15 bg-zinc-950/95 px-3 py-2 text-xs text-zinc-100 shadow-2xl shadow-black/40"></div>
                            <script type="application/json" data-chart-payload>{!! json_encode($chart['tooltip'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
                        </div>

                        <div class="grid gap-4">
                            <div data-interactive-chart class="relative h-[18rem] overflow-hidden rounded-md border border-white/10 bg-[#0b0d10] 2xl:h-[23rem]">
                                <div class="border-b border-white/10 px-3 py-2">
                                    <p class="text-sm font-semibold text-white">{{ __('ui.stats.error_percent') }}</p>
                                    <p class="mt-1 text-xs text-zinc-400">{{ __('ui.stats.max', ['value' => $formatPercent($chart['error_max'])]) }}</p>
                                </div>
                                <svg viewBox="0 0 720 260" role="img" class="h-[14.5rem] w-full 2xl:h-[19.5rem]">
                                    <rect width="720" height="260" fill="#09090b"></rect>
                                    <line x1="18" y1="242" x2="702" y2="242" stroke="#3f3f46" stroke-width="1"></line>

                                    @if ($chart['error'])
                                        <polyline points="{{ $chart['error'] }}" fill="none" stroke="#fb7185" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></polyline>
                                    @endif

                                    <line data-chart-guide class="hidden" y1="18" y2="242" stroke="#f8fafc" stroke-width="1" stroke-dasharray="4 5" opacity="0.72"></line>
                                    <circle data-chart-marker class="hidden" r="5" fill="#f8fafc" stroke="#09090b" stroke-width="2"></circle>
                                    <rect x="0" y="0" width="720" height="260" fill="transparent"></rect>
                                </svg>
                                <div data-chart-tooltip class="pointer-events-none absolute z-20 hidden max-w-72 rounded-md border border-white/15 bg-zinc-950/95 px-3 py-2 text-xs text-zinc-100 shadow-2xl shadow-black/40"></div>
                                <script type="application/json" data-chart-payload>{!! json_encode($chart['error_tooltip'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
                            </div>

                            <div class="grid gap-2 rounded-md border border-white/10 bg-[#0b0d10] p-3 text-sm">
                                <div class="flex justify-between gap-3 border-b border-white/5 pb-2">
                                    <span class="text-zinc-500">{{ __('ui.chart.visible_high') }}</span>
                                    <span class="font-semibold text-white">{{ $formatPrice($chart['max']) }}</span>
                                </div>
                                <div class="flex justify-between gap-3 border-b border-white/5 pb-2">
                                    <span class="text-zinc-500">{{ __('ui.chart.visible_low') }}</span>
                                    <span class="font-semibold text-white">{{ $formatPrice($chart['min']) }}</span>
                                </div>
                                <div class="flex justify-between gap-3 border-b border-white/5 pb-2">
                                    <span class="text-zinc-500">{{ __('ui.stats.last_evaluation') }}</span>
                                    <span class="text-right font-semibold text-white">{{ $formatDateTime($metrics['last_evaluated_at']) }}</span>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <span class="text-zinc-500">{{ __('ui.stats.latest_completed') }}</span>
                                    <span class="text-right font-semibold text-white">{{ $formatDateTime($metrics['latest_completed_at']) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <section class="rounded-md border border-white/10 bg-[#111317]">
                    <div class="flex items-center justify-between border-b border-white/10 px-4 py-3">
                        <h2 class="text-sm font-semibold uppercase text-zinc-300">{{ __('ui.stats.engine_breakdown') }}</h2>
                        <span class="text-xs text-zinc-500">{{ $engineRows->count() }}</span>
                    </div>

                    <div class="overflow-x-auto">
                        <div class="min-w-[58rem]">
                            <div class="grid grid-cols-[10rem_5rem_6rem_6rem_7rem_8rem_8rem_10rem] gap-3 border-b border-white/10 px-4 py-2 text-xs font-semibold uppercase text-zinc-500">
                                <span>{{ __('ui.stats.source') }}</span>
                                <span>{{ __('ui.stats.runs') }}</span>
                                <span>{{ __('ui.stats.evaluated') }}</span>
                                <span>{{ __('ui.stats.pending_points') }}</span>
                                <span>{{ __('ui.common.mape') }}</span>
                                <span>{{ __('ui.stats.mae') }}</span>
                                <span>{{ __('ui.common.direction') }}</span>
                                <span>{{ __('ui.stats.next_pending') }}</span>
                            </div>

                            @forelse ($engineRows as $row)
                                <div wire:key="engine-row-{{ $row['source'] }}" class="grid grid-cols-[10rem_5rem_6rem_6rem_7rem_8rem_8rem_10rem] gap-3 border-b border-white/5 px-4 py-3 text-sm">
                                    <span class="truncate font-semibold text-white">{{ $row['source'] }}</span>
                                    <span class="text-zinc-300">{{ $row['runs'] }}</span>
                                    <span class="text-zinc-300">{{ $row['evaluated_points'] }}</span>
                                    <span class="text-amber-200">{{ $row['pending_points'] }}</span>
                                    <span class="text-cyan-200">{{ $formatPercent($row['mape']) }}</span>
                                    <span class="text-zinc-300">{{ $formatPrice($row['mae']) }}</span>
                                    <span class="text-emerald-200">{{ $formatPercent($row['direction_accuracy']) }}</span>
                                    <span class="truncate text-zinc-400">{{ $formatDateTime($row['next_pending_at']) }}</span>
                                </div>
                            @empty
                                <div class="px-4 py-10 text-sm text-zinc-400">{{ __('ui.stats.no_engine_rows') }}</div>
                            @endforelse
                        </div>
                    </div>
                </section>

                <div class="grid gap-5 xl:grid-cols-2">
                    @foreach ([['key' => 'best', 'title' => __('ui.stats.best_points'), 'rows' => $bestPoints], ['key' => 'worst', 'title' => __('ui.stats.worst_points'), 'rows' => $worstPoints]] as $panel)
                        <section class="rounded-md border border-white/10 bg-[#111317]">
                            <div class="flex items-center justify-between border-b border-white/10 px-4 py-3">
                                <h2 class="text-sm font-semibold uppercase text-zinc-300">{{ $panel['title'] }}</h2>
                                <span class="text-xs text-zinc-500">{{ $panel['rows']->count() }}</span>
                            </div>

                            <div class="max-h-96 overflow-auto">
                                @forelse ($panel['rows'] as $point)
                                    <div wire:key="ranked-point-{{ $panel['key'] }}-{{ $point->id }}" class="grid grid-cols-[7rem_6rem_minmax(0,1fr)_6rem] gap-3 border-b border-white/5 px-4 py-3 text-sm">
                                        <span class="truncate text-zinc-400">{{ $point->target_open_time->format('m-d H:i') }}</span>
                                        <span class="truncate font-semibold text-white">{{ $point->source }}</span>
                                        <span class="truncate text-zinc-300">{{ $formatPrice($point->predicted_price) }} / {{ $formatPrice($point->actual_close_price) }}</span>
                                        <span class="{{ $directionClass($point->direction_correct) }}">{{ $formatPercent($point->absolute_percentage_error) }}</span>
                                    </div>
                                @empty
                                    <div class="px-4 py-10 text-sm text-zinc-400">{{ __('ui.stats.no_ranked_points') }}</div>
                                @endforelse
                            </div>
                        </section>
                    @endforeach
                </div>

                <section class="rounded-md border border-white/10 bg-[#111317]">
                    <div class="flex items-center justify-between border-b border-white/10 px-4 py-3">
                        <h2 class="text-sm font-semibold uppercase text-zinc-300">{{ __('ui.stats.detailed_points') }}</h2>
                        <span class="text-xs text-zinc-500">{{ $points->count() }}</span>
                    </div>

                    <div class="overflow-x-auto">
                        <div class="min-w-[82rem]">
                            <div class="grid grid-cols-[8rem_8rem_6rem_9rem_9rem_9rem_9rem_7rem_7rem_8rem] gap-3 border-b border-white/10 px-4 py-2 text-xs font-semibold uppercase text-zinc-500">
                                <span>{{ __('ui.stats.target') }}</span>
                                <span>{{ __('ui.stats.source') }}</span>
                                <span>{{ __('ui.stats.run') }}</span>
                                <span>{{ __('ui.stats.base_price') }}</span>
                                <span>{{ __('ui.stats.predicted') }}</span>
                                <span>{{ __('ui.stats.actual') }}</span>
                                <span>{{ __('ui.stats.absolute_error') }}</span>
                                <span>{{ __('ui.stats.percent_error') }}</span>
                                <span>{{ __('ui.stats.direction_result') }}</span>
                                <span>{{ __('ui.stats.completed') }}</span>
                            </div>

                            @forelse ($points->reverse()->take(160) as $point)
                                <div wire:key="accuracy-point-detail-{{ $point->id }}" class="grid grid-cols-[8rem_8rem_6rem_9rem_9rem_9rem_9rem_7rem_7rem_8rem] gap-3 border-b border-white/5 px-4 py-3 text-sm">
                                    <span class="truncate text-zinc-400">{{ $point->target_open_time->format('m-d H:i') }}</span>
                                    <span class="truncate font-semibold text-white">{{ $point->source }}</span>
                                    <span class="truncate text-zinc-400">#{{ $point->crypto_forecast_id }} / {{ $point->step }}</span>
                                    <span class="truncate text-zinc-300">{{ $formatPrice($point->base_price) }}</span>
                                    <span class="truncate font-semibold text-amber-200">{{ $formatPrice($point->predicted_price) }}</span>
                                    <span class="truncate font-semibold text-cyan-200">{{ $formatPrice($point->actual_close_price) }}</span>
                                    <span class="truncate text-zinc-300">{{ $formatPrice($point->absolute_error) }}</span>
                                    <span class="{{ $directionClass($point->direction_correct) }}">{{ $formatPercent($point->absolute_percentage_error) }}</span>
                                    <span class="{{ $directionClass($point->direction_correct) }}">{{ $directionLabel($point->direction_correct) }}</span>
                                    <span class="truncate text-zinc-500">{{ $formatDateTime($point->evaluated_at) }}</span>
                                </div>
                            @empty
                                <div class="px-4 py-10 text-sm text-zinc-400">{{ __('ui.stats.no_points') }}</div>
                            @endforelse
                        </div>
                    </div>
                </section>

                <section class="rounded-md border border-white/10 bg-[#111317]">
                    <div class="flex items-center justify-between border-b border-white/10 px-4 py-3">
                        <h2 class="text-sm font-semibold uppercase text-zinc-300">{{ __('ui.stats.pending_table') }}</h2>
                        <span class="text-xs text-zinc-500">{{ $pendingPoints }}</span>
                    </div>

                    <div class="overflow-x-auto">
                        <div class="min-w-[78rem]">
                            <div class="grid grid-cols-[8rem_8rem_6rem_9rem_9rem_18rem_8rem_8rem] gap-3 border-b border-white/10 px-4 py-2 text-xs font-semibold uppercase text-zinc-500">
                                <span>{{ __('ui.stats.target') }}</span>
                                <span>{{ __('ui.stats.source') }}</span>
                                <span>{{ __('ui.stats.run') }}</span>
                                <span>{{ __('ui.stats.base_price') }}</span>
                                <span>{{ __('ui.stats.predicted') }}</span>
                                <span>{{ __('ui.stats.quantiles') }}</span>
                                <span>{{ __('ui.stats.step') }}</span>
                                <span>{{ __('ui.stats.created') }}</span>
                            </div>

                            @forelse ($pendingPointRows->take(160) as $point)
                                <div wire:key="pending-point-detail-{{ $point->id }}" class="grid grid-cols-[8rem_8rem_6rem_9rem_9rem_18rem_8rem_8rem] gap-3 border-b border-white/5 px-4 py-3 text-sm">
                                    <span class="truncate text-zinc-400">{{ $point->target_open_time->format('m-d H:i') }}</span>
                                    <span class="truncate font-semibold text-white">{{ $point->source }}</span>
                                    <span class="truncate text-zinc-400">#{{ $point->crypto_forecast_id }}</span>
                                    <span class="truncate text-zinc-300">{{ $formatPrice($point->base_price) }}</span>
                                    <span class="truncate font-semibold text-amber-200">{{ $formatPrice($point->predicted_price) }}</span>
                                    <span class="truncate text-zinc-300">{{ $formatPrice($point->quantile_low) }} / {{ $formatPrice($point->quantile_median) }} / {{ $formatPrice($point->quantile_high) }}</span>
                                    <span class="truncate text-zinc-400">{{ $point->step }}</span>
                                    <span class="truncate text-zinc-500">{{ $formatDateTime($point->created_at) }}</span>
                                </div>
                            @empty
                                <div class="px-4 py-10 text-sm text-zinc-400">{{ __('ui.stats.no_pending_points') }}</div>
                            @endforelse
                        </div>
                    </div>
                </section>

                <section class="rounded-md border border-white/10 bg-[#111317]">
                    <div class="flex items-center justify-between border-b border-white/10 px-4 py-3">
                        <h2 class="text-sm font-semibold uppercase text-zinc-300">{{ __('ui.stats.run_details') }}</h2>
                        <span class="text-xs text-zinc-500">{{ $forecasts->count() }}</span>
                    </div>

                    <div class="overflow-x-auto">
                        <div class="min-w-[86rem]">
                            <div class="grid grid-cols-[6rem_8rem_7rem_10rem_10rem_12rem_12rem_10rem_8rem_8rem] gap-3 border-b border-white/10 px-4 py-2 text-xs font-semibold uppercase text-zinc-500">
                                <span>{{ __('ui.stats.run') }}</span>
                                <span>{{ __('ui.stats.source') }}</span>
                                <span>{{ __('ui.common.status') }}</span>
                                <span>{{ __('ui.stats.completed') }}</span>
                                <span>{{ __('ui.stats.context_horizon') }}</span>
                                <span>{{ __('ui.stats.input_window') }}</span>
                                <span>{{ __('ui.stats.target_window') }}</span>
                                <span>{{ __('ui.stats.run_points') }}</span>
                                <span>{{ __('ui.common.mape') }}</span>
                                <span>{{ __('ui.common.direction') }}</span>
                            </div>

                            @forelse ($forecasts as $forecast)
                                <div wire:key="forecast-run-detail-{{ $forecast->id }}" class="grid grid-cols-[6rem_8rem_7rem_10rem_10rem_12rem_12rem_10rem_8rem_8rem] gap-3 border-b border-white/5 px-4 py-3 text-sm">
                                    <span class="truncate font-semibold text-white">#{{ $forecast->id }}</span>
                                    <span class="truncate text-zinc-200">{{ $forecast->source }}</span>
                                    <span class="truncate text-zinc-400">{{ $forecast->status }}</span>
                                    <span class="truncate text-zinc-500">{{ $formatDateTime($forecast->completed_at) }}</span>
                                    <span class="truncate text-zinc-300">{{ $forecast->context_points }} / {{ $forecast->horizon }}</span>
                                    <span class="truncate text-zinc-400">{{ $formatTimeWindow($forecast->input_starts_at, $forecast->input_ends_at) }}</span>
                                    <span class="truncate text-zinc-400">{{ $formatTimeWindow($forecast->target_starts_at, $forecast->target_ends_at) }}</span>
                                    <span class="truncate text-zinc-300">{{ $forecast->evaluated_points }}/{{ $forecast->total_points }}</span>
                                    <span class="truncate text-cyan-200">{{ $formatPercent($forecast->mean_absolute_percentage_error) }}</span>
                                    <span class="truncate text-emerald-200">{{ $formatPercent($forecast->direction_accuracy) }}</span>
                                </div>
                            @empty
                                <div class="px-4 py-10 text-sm text-zinc-400">{{ __('ui.stats.no_runs') }}</div>
                            @endforelse
                        </div>
                    </div>
                </section>
            </section>
        </div>
    </section>
</main>
