<?php

namespace App\Livewire;

use App\Actions\Crypto\BuildMarketSeriesAction;
use App\Actions\Crypto\BuildSnapshotHistoryRowsAction;
use App\Actions\Crypto\EnsureMarketHistoryAction;
use App\Actions\Crypto\LoadMarketHistoryAction;
use App\Actions\Crypto\ReadMarketsDashboardAction;
use App\Actions\Crypto\RunMarketForecastAction;
use App\Http\Requests\Crypto\MarketsDashboardRequest;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('components.layouts.app')]
class MarketsDashboard extends Component
{
    public string $selectedSymbol = 'BTCUSDT';

    public string $interval = '1m';

    public string $forecastPeriod = '1h';

    public ?string $notice = null;

    /**
     * @var array<string, string>
     */
    public array $intervalOptions = [];

    /**
     * @var array<string, string>
     */
    public array $forecastOptions = [];

    public function mount(?string $symbol = null): void
    {
        $request = MarketsDashboardRequest::fromRoute($symbol);

        $this->selectedSymbol = $request->symbol;
        $this->interval = $request->interval;
        $this->forecastPeriod = $request->forecastPeriod;
        $this->intervalOptions = MarketsDashboardRequest::intervalOptions();
        $this->forecastOptions = MarketsDashboardRequest::forecastOptions();
    }

    public function refreshMarket(): void
    {
        $this->notice = null;
    }

    public function selectAsset(string $symbol): void
    {
        $request = $this->dashboardRequest()->withSymbol($symbol);

        if (! $request) {
            return;
        }

        $this->selectedSymbol = $request->symbol;
        $this->notice = null;
        $this->loadHistoryIfMissing($request);
    }

    public function setInterval(string $interval): void
    {
        $request = $this->dashboardRequest()->withInterval($interval);

        if (! $request) {
            return;
        }

        $this->interval = $request->interval;
        $this->loadHistoryIfMissing($request);
    }

    public function setForecastPeriod(string $period): void
    {
        $request = $this->dashboardRequest()->withForecastPeriod($period);

        if (! $request) {
            return;
        }

        $this->forecastPeriod = $request->forecastPeriod;
    }

    public function loadHistory(): void
    {
        try {
            $this->notice = app(LoadMarketHistoryAction::class)->handle($this->dashboardRequest());
        } catch (Throwable $exception) {
            $this->notice = $exception->getMessage();
        }
    }

    public function runForecast(): void
    {
        try {
            $result = app(RunMarketForecastAction::class)->handle($this->dashboardRequest());

            $this->interval = $result['interval'];
            $this->notice = $result['message'];
        } catch (Throwable $exception) {
            $this->notice = $exception->getMessage();
        }
    }

    public function render(
        BuildMarketSeriesAction $chartBuilder,
        BuildSnapshotHistoryRowsAction $snapshotRows,
        ReadMarketsDashboardAction $reader,
    ): View {
        $request = $this->dashboardRequest();
        $dashboard = $reader->handle($request->symbol, $request->interval);
        $selectedAsset = $dashboard['selectedAsset'];

        if ($selectedAsset && $this->selectedSymbol !== $selectedAsset->symbol) {
            $this->selectedSymbol = $selectedAsset->symbol;
        }

        return view('livewire.markets-dashboard', [
            'assets' => $dashboard['assets'],
            'selectedAsset' => $selectedAsset,
            'candles' => $dashboard['candles'],
            'snapshots' => $dashboard['snapshots'],
            'snapshotRows' => $snapshotRows->handle($dashboard['snapshots']),
            'forecast' => $dashboard['forecast'],
            'chart' => $chartBuilder->handle($dashboard['candles'], $dashboard['forecast'], $selectedAsset?->latestSnapshot),
        ]);
    }

    private function dashboardRequest(): MarketsDashboardRequest
    {
        return MarketsDashboardRequest::fromState($this->selectedSymbol, $this->interval, $this->forecastPeriod);
    }

    private function loadHistoryIfMissing(MarketsDashboardRequest $request): void
    {
        try {
            app(EnsureMarketHistoryAction::class)->handle($request);
        } catch (Throwable $exception) {
            $this->notice = $exception->getMessage();
        }
    }
}
