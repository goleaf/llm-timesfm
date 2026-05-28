# Crypto TimesFM Dashboard Design

## Goal

Build a Laravel 13 and Livewire 4 dashboard that polls popular Binance Spot USDT markets, stores one-second ticker snapshots locally, shows JSON history for the selected market, and runs TimesFM-compatible forecasts over stored candle history.

## Architecture

Laravel owns the UI, database, scheduler, commands, and orchestration. Binance data enters through Eloquent-backed actions, not from Blade templates. Livewire polls the local database every second while `crypto:watch-tickers` or the scheduler refreshes snapshots from Binance.

TimesFM runs outside PHP through `python/timesfm_forecast.py`. The Laravel action sends close-price history as JSON over stdin and stores the returned forecast payload. When the Python environment or model is not installed, the bridge returns a baseline last-value forecast so the UI remains usable on an 8 GB M1 laptop.

## Data Flow

1. `crypto:watch-tickers` fetches configured Binance symbols from `/api/v3/ticker/24hr`.
2. `FetchBinanceTickersAction` upserts `crypto_assets` and `crypto_price_snapshots`.
3. Selecting a market loads recent `/api/v3/klines` data through `FetchBinanceKlinesAction`.
4. `MarketsDashboard` reads eager-loaded Eloquent data and renders the market list, SVG chart, JSON snapshots, and forecast results.
5. `RunTimesFmForecastAction` reads candles, calls the Python bridge, and stores `crypto_forecasts`.

## Constraints

- No raw SQL.
- No queries in Blade.
- No model aggregates inside loops.
- SQLite is the first local database.
- Binance is the first data source because it supports public market JSON and one-second update patterns.
- TimesFM dependency installation is optional because the current laptop has limited free disk space.

