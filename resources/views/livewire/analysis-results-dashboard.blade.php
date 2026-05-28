@php
    $formatPrice = fn ($value) => $value === null
        ? __('ui.common.pending')
        : number_format((float) $value, (float) $value >= 1 ? 2 : 8);
    $formatPercent = fn ($value) => $value === null ? __('ui.common.pending') : number_format((float) $value, 2).'%';
@endphp

<main wire:poll.visible.1000ms="refreshResults" class="min-h-screen bg-[#0b0d10] text-zinc-100">
    <section class="mx-auto flex min-h-screen w-full max-w-[120rem] flex-col gap-5 px-4 py-5 sm:px-6 2xl:px-8">
        <header class="flex flex-col gap-4 border-b border-white/10 pb-4 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <p class="text-sm font-medium text-cyan-300">{{ __('ui.analysis.eyebrow') }}</p>
                <h1 class="mt-1 text-3xl font-semibold tracking-normal text-white sm:text-4xl">{{ __('ui.analysis.title') }}</h1>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('markets.show', ['symbol' => $selectedAsset?->symbol]) }}" class="h-10 rounded-md border border-white/10 px-3 py-2 text-sm font-semibold text-zinc-200 hover:bg-white/[0.06]">
                    {{ __('ui.common.markets') }}
                </a>
                <a href="{{ route('markets.stats', ['symbol' => $selectedAsset?->symbol]) }}" class="h-10 rounded-md border border-white/10 px-3 py-2 text-sm font-semibold text-zinc-200 hover:bg-white/[0.06]">
                    {{ __('ui.analysis.stats') }}
                </a>

                @foreach ($intervalOptions as $value => $label)
                    <button
                        type="button"
                        wire:key="analysis-interval-{{ $value }}"
                        wire:click="setInterval('{{ $value }}')"
                        class="h-10 min-w-12 rounded-md border px-3 text-sm font-medium {{ $interval === $value ? 'border-cyan-300 bg-cyan-300 text-zinc-950' : 'border-white/10 bg-white/[0.04] text-zinc-200 hover:bg-white/[0.08]' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </header>

        <div class="grid gap-5 lg:grid-cols-[21rem_minmax(0,1fr)] 2xl:grid-cols-[22rem_minmax(0,1fr)]">
            <aside class="overflow-hidden rounded-md border border-white/10 bg-[#111317]">
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
                            wire:key="analysis-asset-{{ $asset->symbol }}"
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
            </aside>

            <section class="flex min-w-0 flex-col gap-5">
                <div class="rounded-md border border-white/10 bg-[#111317]">
                    <div class="flex flex-col gap-4 border-b border-white/10 px-4 py-4 xl:flex-row xl:items-center xl:justify-between">
                        <div>
                            <h2 class="text-2xl font-semibold text-white">{{ $selectedAsset?->display_pair ?? __('ui.common.market') }}</h2>
                            <p class="mt-1 text-sm text-zinc-400">{{ __('ui.analysis.subtitle', ['interval' => $interval]) }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                            <div class="rounded-md border border-white/10 bg-white/[0.04] px-3 py-2">
                                <p class="text-xs text-zinc-400">{{ __('ui.analysis.runs') }}</p>
                                <p class="text-lg font-semibold text-white">{{ $totals['forecasts'] }}</p>
                            </div>
                            <div class="rounded-md border border-white/10 bg-white/[0.04] px-3 py-2">
                                <p class="text-xs text-zinc-400">{{ __('ui.common.checked') }}</p>
                                <p class="text-lg font-semibold text-white">{{ $totals['evaluated'] }}</p>
                            </div>
                            <div class="rounded-md border border-white/10 bg-white/[0.04] px-3 py-2">
                                <p class="text-xs text-zinc-400">{{ __('ui.analysis.pending') }}</p>
                                <p class="text-lg font-semibold text-amber-200">{{ $totals['pending'] }}</p>
                            </div>
                            <div class="rounded-md border border-white/10 bg-white/[0.04] px-3 py-2">
                                <p class="text-xs text-zinc-400">{{ __('ui.common.mape') }}</p>
                                <p class="text-lg font-semibold text-cyan-200">{{ $formatPercent($totals['mape']) }}</p>
                            </div>
                            <div class="rounded-md border border-white/10 bg-white/[0.04] px-3 py-2">
                                <p class="text-xs text-zinc-400">{{ __('ui.common.direction') }}</p>
                                <p class="text-lg font-semibold text-emerald-200">{{ $formatPercent($totals['direction_accuracy']) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-3 p-4 md:grid-cols-2 xl:grid-cols-4">
                        @forelse ($sourceMetrics as $metric)
                            <div wire:key="analysis-source-{{ $metric['source'] }}" class="rounded-md border border-white/10 bg-white/[0.035] p-3">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="truncate text-sm font-semibold text-white">{{ $metric['source'] }}</p>
                                    <span class="rounded-md bg-cyan-300/10 px-2 py-1 text-xs font-semibold text-cyan-100">{{ $metric['forecasts'] }}</span>
                                </div>
                                <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                                    <div>
                                        <span class="block text-zinc-500">{{ __('ui.common.checked') }}</span>
                                        <span class="font-semibold text-zinc-100">{{ $metric['evaluated'] }}</span>
                                    </div>
                                    <div>
                                        <span class="block text-zinc-500">{{ __('ui.analysis.pending') }}</span>
                                        <span class="font-semibold text-amber-100">{{ $metric['pending'] }}</span>
                                    </div>
                                    <div>
                                        <span class="block text-zinc-500">{{ __('ui.common.mape') }}</span>
                                        <span class="font-semibold text-cyan-100">{{ $formatPercent($metric['mape']) }}</span>
                                    </div>
                                    <div>
                                        <span class="block text-zinc-500">{{ __('ui.common.direction') }}</span>
                                        <span class="font-semibold text-emerald-100">{{ $formatPercent($metric['direction_accuracy']) }}</span>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-md border border-white/10 bg-white/[0.035] p-4 text-sm text-zinc-500">{{ __('ui.analysis.no_results') }}</div>
                        @endforelse
                    </div>
                </div>

                <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_26rem] 2xl:grid-cols-[minmax(0,1fr)_34rem]">
                    <section class="rounded-md border border-white/10 bg-[#111317]">
                        <div class="flex items-center justify-between border-b border-white/10 px-4 py-3">
                            <h2 class="text-sm font-semibold uppercase text-zinc-300">{{ __('ui.analysis.compared_points') }}</h2>
                            <span class="text-xs text-zinc-500">{{ $evaluatedPoints->count() }}</span>
                        </div>

                        <div class="max-h-[34rem] overflow-auto">
                            @forelse ($evaluatedPoints as $point)
                                <div wire:key="analysis-point-{{ $point->id }}" class="grid grid-cols-[6rem_minmax(0,1fr)_minmax(0,1fr)_5rem] gap-3 border-b border-white/5 px-4 py-3 text-sm">
                                    <span class="text-xs text-zinc-500">{{ $point->target_open_time->format('M d H:i') }}</span>
                                    <span class="min-w-0">
                                        <span class="block truncate font-semibold text-white">{{ $point->source }}</span>
                                        <span class="mt-1 block text-xs text-amber-100">{{ $formatPrice($point->predicted_price) }}</span>
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-xs text-zinc-500">{{ __('ui.common.actual') }}</span>
                                        <span class="mt-1 block truncate font-semibold text-cyan-100">{{ $formatPrice($point->actual_close_price) }}</span>
                                    </span>
                                    <span class="text-right text-xs font-semibold {{ $point->direction_correct ? 'text-emerald-300' : 'text-rose-300' }}">
                                        {{ $formatPercent($point->absolute_percentage_error) }}
                                    </span>
                                </div>
                            @empty
                                <div class="px-4 py-10 text-sm text-zinc-400">{{ __('ui.analysis.no_compared_points') }}</div>
                            @endforelse
                        </div>
                    </section>

                    <section class="rounded-md border border-white/10 bg-[#111317]">
                        <div class="flex items-center justify-between border-b border-white/10 px-4 py-3">
                            <h2 class="text-sm font-semibold uppercase text-zinc-300">{{ __('ui.analysis.analysis_runs') }}</h2>
                            <span class="text-xs text-zinc-500">{{ $forecasts->count() }}</span>
                        </div>

                        <div class="max-h-[34rem] overflow-auto">
                            @forelse ($forecasts as $forecast)
                                <div wire:key="analysis-run-{{ $forecast->id }}" class="border-b border-white/5 px-4 py-3">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="truncate text-sm font-semibold text-white">#{{ $forecast->id }} / {{ $forecast->source }}</p>
                                        <p class="text-xs text-zinc-500">{{ $forecast->completed_at?->format('M d H:i') }}</p>
                                    </div>
                                    <div class="mt-2 grid grid-cols-3 gap-2 text-xs">
                                        <span class="rounded-md bg-white/[0.04] px-2 py-1 text-zinc-300">{{ __('ui.common.mape') }} {{ $formatPercent($forecast->mean_absolute_percentage_error) }}</span>
                                        <span class="rounded-md bg-white/[0.04] px-2 py-1 text-zinc-300">{{ __('ui.common.direction') }} {{ $formatPercent($forecast->direction_accuracy) }}</span>
                                        <span class="rounded-md bg-white/[0.04] px-2 py-1 text-zinc-300">{{ $forecast->evaluated_points }}/{{ $forecast->total_points }}</span>
                                    </div>
                                    <p class="mt-2 text-xs text-zinc-500">
                                        {{ $forecast->target_starts_at?->format('M d H:i') }} - {{ $forecast->target_ends_at?->format('M d H:i') }}
                                    </p>
                                </div>
                            @empty
                                <div class="px-4 py-10 text-sm text-zinc-400">{{ __('ui.analysis.no_runs') }}</div>
                            @endforelse
                        </div>
                    </section>
                </div>
            </section>
        </div>
    </section>
</main>
