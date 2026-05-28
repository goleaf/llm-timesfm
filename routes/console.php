<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('crypto:sync-tickers')
    ->everySecond()
    ->withoutOverlapping();

Schedule::command('crypto:sync-metadata')
    ->daily()
    ->withoutOverlapping();

Schedule::command('crypto:fill-missing-candles --interval=1m --interval=5m')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('crypto:evaluate-forecasts')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('crypto:evaluate-prediction-stakes')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('crypto:forecast-cycle 15m --limit=5 --fresh-minutes=2')
    ->everyMinute()
    ->withoutOverlapping();
