@php
    $formatPrice = fn ($value) => $value === null
        ? '0.00'
        : number_format((float) $value, (float) $value >= 1 ? 2 : 8);
    $formatPercent = fn ($value) => $value === null ? '0.00%' : number_format((float) $value, 2).'%';
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

        <div class="grid gap-5 lg:grid-cols-[21rem_minmax(0,1fr)] 2xl:grid-cols-[22rem_minmax(0,1fr)]">
            <section class="overflow-hidden rounded-md border border-white/10 bg-[#111317]">
                <div class="flex items-center justify-between border-b border-white/10 px-4 py-3">
                    <h2 class="text-sm font-semibold uppercase text-zinc-300">{{ __('ui.common.markets') }}</h2>
                    <span class="text-xs text-zinc-500">{{ $assets->count() }}</span>
                </div>

                <div class="max-h-[calc(100vh-11rem)] overflow-y-auto">
                    @forelse ($assets as $asset)
                        @php
                            $isSelected = $selectedAsset?->is($asset);
                            $snapshot = $asset->latestSnapshot;
                        @endphp

                        <button
                            type="button"
                            wire:key="stats-asset-{{ $asset->symbol }}"
                            wire:click="selectAsset('{{ $asset->symbol }}')"
                            class="grid w-full grid-cols-[minmax(0,1fr)_auto] gap-3 border-b border-white/5 px-4 py-3 text-left transition {{ $isSelected ? 'bg-cyan-400/12' : 'hover:bg-white/[0.04]' }}"
                        >
                            <span>
                                <span class="block text-sm font-semibold text-white">{{ $asset->display_pair }}</span>
                                <span class="mt-1 block text-xs text-zinc-400">{{ $asset->symbol }}</span>
                            </span>
                            <span class="text-right text-sm font-semibold text-zinc-200">
                                {{ $formatPrice($snapshot?->price) }}
                            </span>
                        </button>
                    @empty
                        <div class="px-4 py-10 text-sm text-zinc-400">{{ __('ui.common.no_markets_loaded') }}</div>
                    @endforelse
                </div>
            </section>

            <section class="flex min-w-0 flex-col gap-5">
                <div class="rounded-md border border-white/10 bg-[#111317]">
                    <div class="flex flex-col gap-4 border-b border-white/10 px-4 py-4 xl:flex-row xl:items-center xl:justify-between">
                        <div>
                            <h2 class="text-2xl font-semibold text-white">{{ $selectedAsset?->display_pair ?? __('ui.common.market') }}</h2>
                            <p class="mt-1 text-sm text-zinc-400">{{ __('ui.stats.subtitle', ['interval' => $interval, 'count' => $points->count()]) }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                            <div class="rounded-md border border-white/10 bg-white/[0.04] px-3 py-2">
                                <p class="text-xs text-zinc-400">{{ __('ui.stats.forecasts') }}</p>
                                <p class="text-lg font-semibold text-white">{{ $metrics['forecasts'] }}</p>
                            </div>
                            <div class="rounded-md border border-white/10 bg-white/[0.04] px-3 py-2">
                                <p class="text-xs text-zinc-400">{{ __('ui.stats.evaluated') }}</p>
                                <p class="text-lg font-semibold text-white">{{ $metrics['evaluated_points'] }}</p>
                            </div>
                            <div class="rounded-md border border-white/10 bg-white/[0.04] px-3 py-2">
                                <p class="text-xs text-zinc-400">{{ __('ui.analysis.pending') }}</p>
                                <p class="text-lg font-semibold text-amber-200">{{ $metrics['pending_points'] }}</p>
                            </div>
                            <div class="rounded-md border border-white/10 bg-white/[0.04] px-3 py-2">
                                <p class="text-xs text-zinc-400">{{ __('ui.common.mape') }}</p>
                                <p class="text-lg font-semibold text-cyan-200">{{ $formatPercent($metrics['mape']) }}</p>
                            </div>
                            <div class="rounded-md border border-white/10 bg-white/[0.04] px-3 py-2">
                                <p class="text-xs text-zinc-400">{{ __('ui.common.direction') }}</p>
                                <p class="text-lg font-semibold text-emerald-200">{{ $formatPercent($metrics['direction_accuracy']) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-4 p-4 xl:grid-cols-[minmax(0,1fr)_18rem] 2xl:grid-cols-[minmax(0,1fr)_24rem]">
                        <div data-interactive-chart class="relative h-[22rem] overflow-hidden rounded-md border border-white/10 bg-[#0b0d10] 2xl:h-[30rem]">
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

                        <div data-interactive-chart class="relative h-[22rem] overflow-hidden rounded-md border border-white/10 bg-[#0b0d10] 2xl:h-[30rem]">
                            <div class="border-b border-white/10 px-3 py-2">
                                <p class="text-sm font-semibold text-white">{{ __('ui.stats.error_percent') }}</p>
                                <p class="mt-1 text-xs text-zinc-400">{{ __('ui.stats.max', ['value' => $formatPercent($chart['error_max'])]) }}</p>
                            </div>
                            <svg viewBox="0 0 720 260" role="img" class="h-[18.5rem] w-full 2xl:h-[26.5rem]">
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
                    </div>
                </div>

                <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_22rem] 2xl:grid-cols-[minmax(0,1fr)_32rem]">
                    <section class="rounded-md border border-white/10 bg-[#111317]">
                        <div class="flex items-center justify-between border-b border-white/10 px-4 py-3">
                            <h2 class="text-sm font-semibold uppercase text-zinc-300">{{ __('ui.stats.recent_points') }}</h2>
                            <span class="text-xs text-zinc-500">{{ $points->count() }}</span>
                        </div>

                        <div class="max-h-96 overflow-auto">
                            @forelse ($points->reverse()->take(40) as $point)
                                <div wire:key="accuracy-point-{{ $point->id }}" class="grid grid-cols-[7rem_1fr_1fr_5rem] gap-3 border-b border-white/5 px-4 py-3 text-sm">
                                    <span class="text-zinc-400">{{ $point->target_open_time->format('H:i') }}</span>
                                    <span class="font-semibold text-amber-200">{{ $formatPrice($point->predicted_price) }}</span>
                                    <span class="font-semibold text-cyan-200">{{ $formatPrice($point->actual_close_price) }}</span>
                                    <span class="{{ $point->direction_correct ? 'text-emerald-300' : 'text-rose-300' }}">{{ $formatPercent($point->absolute_percentage_error) }}</span>
                                </div>
                            @empty
                                <div class="px-4 py-10 text-sm text-zinc-400">{{ __('ui.stats.no_points') }}</div>
                            @endforelse
                        </div>
                    </section>

                    <section class="rounded-md border border-white/10 bg-[#111317]">
                        <div class="border-b border-white/10 px-4 py-3">
                            <h2 class="text-sm font-semibold uppercase text-zinc-300">{{ __('ui.stats.forecast_runs') }}</h2>
                        </div>

                        <div class="max-h-96 overflow-auto">
                            @forelse ($forecasts as $forecast)
                                <div wire:key="forecast-run-{{ $forecast->id }}" class="border-b border-white/5 px-4 py-3">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-sm font-semibold text-white">#{{ $forecast->id }} / {{ $forecast->source }}</p>
                                        <p class="text-xs text-zinc-500">{{ $forecast->completed_at?->format('H:i:s') }}</p>
                                    </div>
                                    <div class="mt-2 grid grid-cols-3 gap-2 text-xs">
                                        <span class="rounded-md bg-white/[0.04] px-2 py-1 text-zinc-300">{{ __('ui.common.mape') }} {{ $formatPercent($forecast->mean_absolute_percentage_error) }}</span>
                                        <span class="rounded-md bg-white/[0.04] px-2 py-1 text-zinc-300">{{ __('ui.common.direction') }} {{ $formatPercent($forecast->direction_accuracy) }}</span>
                                        <span class="rounded-md bg-white/[0.04] px-2 py-1 text-zinc-300">{{ $forecast->evaluated_points }}/{{ $forecast->total_points }}</span>
                                    </div>
                                </div>
                            @empty
                                <div class="px-4 py-10 text-sm text-zinc-400">{{ __('ui.stats.no_runs') }}</div>
                            @endforelse
                        </div>
                    </section>
                </div>
            </section>
        </div>
    </section>
</main>
