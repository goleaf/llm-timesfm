<?php

namespace App\Livewire;

use App\Actions\Crypto\EvaluateForecastAccuracyAction;
use App\Actions\Crypto\ReadAnalysisResultsDashboardAction;
use App\Http\Requests\Crypto\ForecastStatsDashboardRequest;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class AnalysisResultsDashboard extends Component
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

    public function refreshResults(): void
    {
        app(EvaluateForecastAccuracyAction::class)->handle(1000);
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

    public function render(ReadAnalysisResultsDashboardAction $reader): View
    {
        $request = $this->dashboardRequest();
        $dashboard = $reader->handle($request->symbol, $request->interval);
        $selectedAsset = $dashboard['selectedAsset'];

        if ($selectedAsset && $this->selectedSymbol !== $selectedAsset->symbol) {
            $this->selectedSymbol = $selectedAsset->symbol;
        }

        return view('livewire.analysis-results-dashboard', [
            'assets' => $dashboard['assets'],
            'selectedAsset' => $selectedAsset,
            'forecasts' => $dashboard['forecasts'],
            'evaluatedPoints' => $dashboard['evaluatedPoints'],
            'pendingPoints' => $dashboard['pendingPoints'],
            'sourceMetrics' => $dashboard['sourceMetrics'],
            'totals' => $dashboard['totals'],
        ]);
    }

    private function dashboardRequest(): ForecastStatsDashboardRequest
    {
        return ForecastStatsDashboardRequest::fromState($this->selectedSymbol, $this->interval);
    }
}
