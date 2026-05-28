<?php

namespace App\Livewire;

use App\Actions\Crypto\BuildForecastAccuracySeriesAction;
use App\Models\CryptoAsset;
use App\Models\CryptoForecast;
use App\Models\CryptoForecastPoint;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ForecastStatsDashboard extends Component
{
    public string $selectedSymbol = 'BTCUSDT';

    public string $interval = '1m';

    /**
     * @var array<string, string>
     */
    public array $intervalOptions = [
        '1m' => '1m',
        '5m' => '5m',
        '15m' => '15m',
        '1h' => '1h',
    ];

    public function mount(?string $symbol = null): void
    {
        $this->selectedSymbol = strtoupper($symbol ?: $this->selectedSymbol);
    }

    public function refreshStats(): void
    {
        //
    }

    public function selectAsset(string $symbol): void
    {
        $this->selectedSymbol = strtoupper($symbol);
    }

    public function setInterval(string $interval): void
    {
        if (! array_key_exists($interval, $this->intervalOptions)) {
            return;
        }

        $this->interval = $interval;
    }

    public function render(BuildForecastAccuracySeriesAction $chartBuilder): View
    {
        $assets = CryptoAsset::query()->dashboardList((int) config('crypto.binance.market_limit', 20))->get();
        $selectedAsset = $this->selectedAssetQuery()->first() ?: $assets->first();

        if ($selectedAsset && $this->selectedSymbol !== $selectedAsset->symbol) {
            $this->selectedSymbol = $selectedAsset->symbol;
        }

        $forecasts = $selectedAsset
            ? CryptoForecast::query()
                ->forAsset($selectedAsset)
                ->forInterval($this->interval)
                ->completed()
                ->orderByDesc('completed_at')
                ->limit(12)
                ->get()
            : collect();

        $points = $selectedAsset
            ? CryptoForecastPoint::query()
                ->forAsset($selectedAsset)
                ->forInterval($this->interval)
                ->evaluated()
                ->orderByDesc('target_open_time')
                ->limit(160)
                ->get()
                ->sortBy('target_open_time')
                ->values()
            : collect();

        $pendingPoints = $selectedAsset
            ? CryptoForecastPoint::query()
                ->forAsset($selectedAsset)
                ->forInterval($this->interval)
                ->pendingEvaluation()
                ->count()
            : 0;

        return view('livewire.forecast-stats-dashboard', [
            'assets' => $assets,
            'selectedAsset' => $selectedAsset,
            'forecasts' => $forecasts,
            'points' => $points,
            'pendingPoints' => $pendingPoints,
            'chart' => $chartBuilder->handle($points),
            'metrics' => [
                'forecasts' => $forecasts->count(),
                'evaluated_points' => $forecasts->sum('evaluated_points'),
                'pending_points' => $pendingPoints,
                'mape' => $forecasts->whereNotNull('mean_absolute_percentage_error')->avg(
                    fn (CryptoForecast $forecast): float => (float) $forecast->mean_absolute_percentage_error,
                ),
                'mae' => $forecasts->whereNotNull('mean_absolute_error')->avg(
                    fn (CryptoForecast $forecast): float => (float) $forecast->mean_absolute_error,
                ),
                'direction_accuracy' => $forecasts->whereNotNull('direction_accuracy')->avg(
                    fn (CryptoForecast $forecast): float => (float) $forecast->direction_accuracy,
                ),
            ],
        ]);
    }

    private function selectedAssetQuery(): Builder
    {
        return CryptoAsset::query()
            ->forSymbol($this->selectedSymbol)
            ->withLatestSnapshot();
    }
}
