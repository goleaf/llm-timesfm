<?php

namespace App\Providers;

use App\Models\CryptoAsset;
use App\Models\CryptoCandle;
use App\Models\CryptoForecast;
use App\Models\CryptoForecastPoint;
use App\Models\CryptoPredictionStake;
use App\Models\CryptoPriceSnapshot;
use App\Observers\CryptoCacheObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        CryptoAsset::observe(CryptoCacheObserver::class);
        CryptoCandle::observe(CryptoCacheObserver::class);
        CryptoForecast::observe(CryptoCacheObserver::class);
        CryptoForecastPoint::observe(CryptoCacheObserver::class);
        CryptoPriceSnapshot::observe(CryptoCacheObserver::class);
        CryptoPredictionStake::observe(CryptoCacheObserver::class);
    }
}
