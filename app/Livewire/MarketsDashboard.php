<?php

namespace App\Livewire;

use App\Actions\Crypto\BuildMarketSeriesAction;
use App\Actions\Crypto\FillMissingCryptoCandlesAction;
use App\Actions\Crypto\ReadMarketsDashboardAction;
use App\Actions\Crypto\RunTimesFmForecastAction;
use App\Models\CryptoAsset;
use App\Models\CryptoCandle;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
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
    public array $intervalOptions = [
        '1m' => '1m',
        '5m' => '5m',
        '15m' => '15m',
        '1h' => '1h',
        '1d' => '1d',
    ];

    /**
     * @var array<string, string>
     */
    public array $forecastOptions = [
        '15m' => '15m',
        '1h' => '1h',
        '4h' => '4h',
        '24h' => '24h',
    ];

    public function mount(?string $symbol = null): void
    {
        $this->selectedSymbol = strtoupper($symbol ?: $this->selectedSymbol);
    }

    public function refreshMarket(): void
    {
        $this->notice = null;
    }

    public function selectAsset(string $symbol): void
    {
        $this->selectedSymbol = strtoupper($symbol);
        $this->notice = null;
        $this->loadHistoryIfMissing();
    }

    public function setInterval(string $interval): void
    {
        if (! array_key_exists($interval, $this->intervalOptions)) {
            return;
        }

        $this->interval = $interval;
        $this->loadHistoryIfMissing();
    }

    public function setForecastPeriod(string $period): void
    {
        if (! array_key_exists($period, $this->forecastOptions)) {
            return;
        }

        $this->forecastPeriod = $period;
    }

    public function loadHistory(): void
    {
        $asset = $this->selectedAssetQuery()->first();

        if (! $asset) {
            $this->notice = 'Market data is not loaded yet.';

            return;
        }

        app(FillMissingCryptoCandlesAction::class)->handle(
            [$asset->symbol],
            [$this->interval],
            (int) config('crypto.binance.history_limit'),
        );

        $this->notice = "History loaded for {$asset->symbol}.";
    }

    public function runForecast(): void
    {
        $settings = config("crypto.forecasting.periods.{$this->forecastPeriod}");
        $asset = $this->selectedAssetQuery()->first();

        if (! $asset || ! is_array($settings)) {
            $this->notice = 'Forecast is not available for this selection.';

            return;
        }

        try {
            app(FillMissingCryptoCandlesAction::class)->handle(
                [$asset->symbol],
                [(string) $settings['interval']],
                (int) $settings['context'],
            );

            $forecast = app(RunTimesFmForecastAction::class)->handle(
                $asset,
                (string) $settings['interval'],
                (int) $settings['horizon'],
                (int) $settings['context'],
            );

            $this->interval = (string) $settings['interval'];
            $this->notice = "Forecast #{$forecast->getKey()} stored.";
        } catch (Throwable $exception) {
            $this->notice = $exception->getMessage();
        }
    }

    public function render(BuildMarketSeriesAction $chartBuilder, ReadMarketsDashboardAction $reader): View
    {
        $dashboard = $reader->handle($this->selectedSymbol, $this->interval);
        $selectedAsset = $dashboard['selectedAsset'];

        if ($selectedAsset && $this->selectedSymbol !== $selectedAsset->symbol) {
            $this->selectedSymbol = $selectedAsset->symbol;
        }

        return view('livewire.markets-dashboard', [
            'assets' => $dashboard['assets'],
            'selectedAsset' => $selectedAsset,
            'candles' => $dashboard['candles'],
            'snapshots' => $dashboard['snapshots'],
            'forecast' => $dashboard['forecast'],
            'chart' => $chartBuilder->handle($dashboard['candles'], $dashboard['forecast']),
        ]);
    }

    private function loadHistoryIfMissing(): void
    {
        $asset = $this->selectedAssetQuery()->first();

        if (! $asset) {
            return;
        }

        $exists = CryptoCandle::query()
            ->forAsset($asset)
            ->forInterval($this->interval)
            ->exists();

        if ($exists) {
            return;
        }

        try {
            app(FillMissingCryptoCandlesAction::class)->handle(
                [$asset->symbol],
                [$this->interval],
                (int) config('crypto.binance.history_limit'),
            );
        } catch (Throwable $exception) {
            $this->notice = $exception->getMessage();
        }
    }

    private function selectedAssetQuery(): Builder
    {
        return CryptoAsset::query()
            ->forSymbol($this->selectedSymbol)
            ->withLatestSnapshot();
    }
}
