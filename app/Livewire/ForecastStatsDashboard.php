<?php

namespace App\Livewire;

use App\Actions\Crypto\BuildForecastAccuracySeriesAction;
use App\Actions\Crypto\ReadForecastStatsDashboardAction;
use App\Models\CryptoAsset;
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

    public function render(BuildForecastAccuracySeriesAction $chartBuilder, ReadForecastStatsDashboardAction $reader): View
    {
        $dashboard = $reader->handle($this->selectedSymbol, $this->interval);
        $selectedAsset = $dashboard['selectedAsset'];

        if ($selectedAsset && $this->selectedSymbol !== $selectedAsset->symbol) {
            $this->selectedSymbol = $selectedAsset->symbol;
        }

        return view('livewire.forecast-stats-dashboard', [
            'assets' => $dashboard['assets'],
            'selectedAsset' => $selectedAsset,
            'forecasts' => $dashboard['forecasts'],
            'points' => $dashboard['points'],
            'pendingPoints' => $dashboard['pendingPoints'],
            'chart' => $chartBuilder->handle($dashboard['points']),
            'metrics' => $dashboard['metrics'],
        ]);
    }

    private function selectedAssetQuery(): Builder
    {
        return CryptoAsset::query()
            ->forSymbol($this->selectedSymbol)
            ->withLatestSnapshot();
    }
}
