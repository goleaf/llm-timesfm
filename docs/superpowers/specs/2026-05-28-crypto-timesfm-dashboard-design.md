# Crypto TimesFM Dashboard Design

## Goal

Build a Laravel 13 and Livewire 4 dashboard that polls popular Binance Spot USDT markets, stores one-second ticker snapshots locally, shows JSON history for the selected market, runs TimesFM-compatible forecasts over stored candle history, and measures forecast accuracy after real candles arrive.

## Architecture

Laravel owns the UI, database, scheduler, commands, and orchestration. Binance data enters through Eloquent-backed actions, not from Blade templates. Full-page Livewire components poll the local database while the scheduler refreshes snapshots, fills missing short candles, creates forecasts, and evaluates forecast accuracy.

TimesFM runs outside PHP through `python/timesfm_forecast.py`. The Laravel action sends close-price history as JSON over stdin and stores the returned forecast payload. When the Python environment or model is not installed, the bridge returns a baseline last-value forecast so the UI remains usable on an 8 GB M1 laptop.

The project is public and Livewire-only. It intentionally has no authentication system, user account model, private panel, standard public controller, traditional public Blade page, or Volt component.

## Data Flow

1. The scheduler fetches configured Binance symbols from `/api/v3/ticker/24hr`.
2. The metadata sync stores Binance exchange information, precision, filters, and trading status.
3. Missing short candles are detected by local candle timestamps and only missing ranges are requested.
4. The market dashboard reads eager-loaded local data and renders the market list, SVG chart, JSON snapshots, and forecast results.
5. The forecast cycle reads candles, calls the Python bridge, and stores forecast runs plus forecast points.
6. The evaluator compares forecast points with actual candle closes when the target candles exist.
7. The statistics dashboard shows evaluated forecast quality with live-updating charts.

## Constraints

- No raw SQL.
- No queries in Blade.
- No model aggregates inside loops.
- SQLite is the first local database.
- Binance is the first data source because it supports public market JSON and one-second update patterns.
- TimesFM can run through the local Python environment, with baseline fallback when disabled.
- No auth, users, private panels, Volt, public controllers, or traditional public Blade pages.
- Every completed prompt must update the changelog, keep Markdown current, pass checks, commit, and push.
