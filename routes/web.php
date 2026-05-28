<?php

use App\Livewire\AnalysisResultsDashboard;
use App\Livewire\ForecastStatsDashboard;
use App\Livewire\MarketsDashboard;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function (): void {
    Route::redirect('/', '/markets')->name('home');

    Route::prefix('markets')->name('markets.')->group(function (): void {
        Route::get('/analyses/{symbol?}', AnalysisResultsDashboard::class)->name('analyses');
        Route::get('/stats/{symbol?}', ForecastStatsDashboard::class)->name('stats');
        Route::get('/{symbol?}', MarketsDashboard::class)->name('show');
    });
});
