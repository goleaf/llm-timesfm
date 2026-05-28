<?php

namespace App\Livewire;

use App\Actions\Crypto\BuildForecastAccuracySeriesAction;
use App\Actions\Crypto\ReadForecastStatsDashboardAction;
use App\Http\Requests\Crypto\ForecastStatsDashboardRequest;
use Illuminate\Contracts\View\View;
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
    public array $intervalOptions = [];

    public function mount(?string $symbol = null): void
    {
        $request = ForecastStatsDashboardRequest::fromRoute($symbol);

        $this->selectedSymbol = $request->symbol;
        $this->interval = $request->interval;
        $this->intervalOptions = ForecastStatsDashboardRequest::intervalOptions();
    }

    public function refreshStats(): void
    {
        //
    }

    public function selectAsset(string $symbol): void
    {
        $request = $this->dashboardRequest()->withSymbol($symbol);

        if (! $request) {
            return;
        }

        $this->selectedSymbol = $request->symbol;
    }

    public function setInterval(string $interval): void
    {
        $request = $this->dashboardRequest()->withInterval($interval);

        if (! $request) {
            return;
        }

        $this->interval = $request->interval;
    }

    public function render(BuildForecastAccuracySeriesAction $chartBuilder, ReadForecastStatsDashboardAction $reader): View
    {
        $request = $this->dashboardRequest();
        $dashboard = $reader->handle($request->symbol, $request->interval);
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

    private function dashboardRequest(): ForecastStatsDashboardRequest
    {
        return ForecastStatsDashboardRequest::fromState($this->selectedSymbol, $this->interval);
    }
}
