<?php

namespace App\Livewire;

use App\Actions\Crypto\BuildMarketBoardAction;
use App\Actions\Crypto\BuildMarketSeriesAction;
use App\Actions\Crypto\CreatePredictionStakeAction;
use App\Actions\Crypto\EnsureMarketHistoryAction;
use App\Actions\Crypto\EvaluatePredictionStakesAction;
use App\Actions\Crypto\LoadMarketHistoryAction;
use App\Actions\Crypto\ReadMarketsDashboardAction;
use App\Actions\Crypto\RunMarketForecastAction;
use App\Http\Requests\Crypto\MarketsDashboardRequest;
use App\Http\Requests\Crypto\StorePredictionStakeRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
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

    public string $baseSearch = '';

    public string $quoteSearch = '';

    public string $stakeTargetAt = '';

    public string $stakeTargetPrice = '';

    public string $stakeDirection = 'above';

    public int $stakeConfidence = 60;

    public string $stakeNote = '';

    /**
     * @var array<int, string>
     */
    public array $pinnedSymbols = [];

    /**
     * @var array<string, string>
     */
    public array $intervalOptions = [];

    /**
     * @var array<string, string>
     */
    public array $forecastOptions = [];

    /**
     * @var array<string, string>
     */
    public array $stakeDirectionOptions = [];

    public function mount(?string $symbol = null): void
    {
        $request = MarketsDashboardRequest::fromRoute($symbol);

        $this->selectedSymbol = $request->symbol;
        $this->interval = $request->interval;
        $this->forecastPeriod = $request->forecastPeriod;
        $this->intervalOptions = MarketsDashboardRequest::intervalOptions();
        $this->forecastOptions = MarketsDashboardRequest::forecastOptions();
        $this->pinnedSymbols = $this->storedPinnedSymbols();
        $this->stakeDirectionOptions = StorePredictionStakeRequest::directionOptions();
        $this->resetStakeTargetTime();
    }

    public function refreshMarket(): void
    {
        try {
            app(EvaluatePredictionStakesAction::class)->handle(50);
        } catch (Throwable $exception) {
            $this->notice = $exception->getMessage();
        }
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
        $this->resetStakeTargetTime();
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

    public function setBaseSearch(string $baseAsset): void
    {
        $this->baseSearch = strtoupper(trim($baseAsset));
    }

    public function setQuoteSearch(string $quoteAsset): void
    {
        $this->quoteSearch = strtoupper(trim($quoteAsset));
    }

    public function clearPairSearch(): void
    {
        $this->baseSearch = '';
        $this->quoteSearch = '';
    }

    public function pinAsset(string $symbol): void
    {
        if (! $this->dashboardRequest()->withSymbol($symbol)) {
            return;
        }

        $this->pinnedSymbols = $this->normalizePinnedSymbols([
            ...$this->pinnedSymbols,
            strtoupper($symbol),
        ]);
        $this->persistPinnedSymbols();
    }

    public function unpinAsset(string $symbol): void
    {
        $symbol = strtoupper($symbol);
        $this->pinnedSymbols = $this->normalizePinnedSymbols(
            array_values(array_filter(
                $this->pinnedSymbols,
                fn (string $pinnedSymbol): bool => $pinnedSymbol !== $symbol,
            )),
        );
        $this->persistPinnedSymbols();
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

    public function placePredictionStake(): void
    {
        try {
            $stake = app(CreatePredictionStakeAction::class)->handle(
                StorePredictionStakeRequest::fromState(
                    $this->selectedSymbol,
                    $this->interval,
                    $this->stakeTargetAt,
                    $this->stakeTargetPrice,
                    $this->stakeDirection,
                    $this->stakeConfidence,
                    $this->stakeNote,
                ),
            );

            $this->stakeTargetPrice = '';
            $this->stakeNote = '';
            $this->resetStakeTargetTime();
            $this->notice = __('ui.market.prediction_stake_saved', [
                'time' => $stake->target_at
                    ->setTimezone(config('app.timezone'))
                    ->format('Y-m-d H:i'),
            ]);
        } catch (Throwable $exception) {
            $this->notice = $this->exceptionMessage($exception);
        }
    }

    public function render(
        BuildMarketBoardAction $boardBuilder,
        BuildMarketSeriesAction $chartBuilder,
        ReadMarketsDashboardAction $reader,
    ): View {
        $request = $this->dashboardRequest();
        $dashboard = $reader->handle($request->symbol, $request->interval);
        $selectedAsset = $dashboard['selectedAsset'];
        $board = $boardBuilder->handle(
            $dashboard['assets'],
            $this->pinnedSymbols,
            $this->baseSearch,
            $this->quoteSearch,
            $selectedAsset,
        );

        if ($selectedAsset && $this->selectedSymbol !== $selectedAsset->symbol) {
            $this->selectedSymbol = $selectedAsset->symbol;
        }

        return view('livewire.markets-dashboard', [
            'assets' => $dashboard['assets'],
            'board' => $board,
            'selectedAsset' => $selectedAsset,
            'candles' => $dashboard['candles'],
            'snapshots' => $dashboard['snapshots'],
            'forecast' => $dashboard['forecast'],
            'forecasts' => $dashboard['forecasts'],
            'predictionStakes' => $dashboard['predictionStakes'],
            'chart' => $chartBuilder->handle($dashboard['candles'], $dashboard['forecasts'], $selectedAsset?->latestSnapshot),
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

    /**
     * @return array<int, string>
     */
    private function storedPinnedSymbols(): array
    {
        $stored = session('crypto.pinned_symbols');

        return $this->normalizePinnedSymbols(is_array($stored) && $stored !== []
            ? $stored
            : array_slice(config('crypto.binance.symbols', []), 0, 2));
    }

    private function persistPinnedSymbols(): void
    {
        session(['crypto.pinned_symbols' => $this->pinnedSymbols]);
    }

    /**
     * @param  array<int, mixed>  $symbols
     * @return array<int, string>
     */
    private function normalizePinnedSymbols(array $symbols): array
    {
        return collect($symbols)
            ->map(fn (mixed $symbol): string => strtoupper(trim((string) $symbol)))
            ->filter(fn (string $symbol): bool => preg_match('/^[A-Z0-9]{2,20}$/', $symbol) === 1)
            ->unique()
            ->take(12)
            ->values()
            ->all();
    }

    private function resetStakeTargetTime(): void
    {
        $this->stakeTargetAt = StorePredictionStakeRequest::defaultTargetAt($this->interval);
    }

    private function exceptionMessage(Throwable $exception): string
    {
        if ($exception instanceof ValidationException) {
            return (string) collect($exception->errors())->flatten()->first($exception->getMessage());
        }

        return $exception->getMessage();
    }
}
