# LLM TimesFM Crypto

Open public Laravel 13 and Livewire 4 dashboard for real-time crypto market history, automatic technical analysis forecasts, TimesFM-compatible forecasts, manual prediction stakes, and forecast accuracy tracking.

Public repository: `https://github.com/goleaf/llm-timesfm`

## What It Does

- Reads public Binance Spot JSON data.
- Stores market snapshots, short candle history, automatic analysis forecasts, and forecast accuracy locally.
- Runs trend, moving average, EMA, momentum, and TimesFM analysis when available.
- Stores manual prediction stakes for a chosen target time and resolves them against real candle data.
- Shows real-time Livewire screens without page reloads.
- Shows interactive SVG charts with zoom controls, wheel zoom, drag panning, always-visible scale labels, latest markers, analyzer labels, metric panels, point ledgers, and hover tooltips for deeper candle, live price, forecast, and forecast-accuracy details.
- Uses a wide Full HD dashboard layout with a source-driven first-currency list, pair finder, pinned rates, chart workspace, visible analysis points, live tick feed, prediction stake panel, and forecast desk.
- Runs scheduled automation for market updates, missing candle backfill, forecast creation, and forecast evaluation.
- Keeps the project open: no login, no user accounts, no private panels.
- Keeps public pages as full-page Livewire components. Volt and standard controllers are intentionally not used.
- Keeps input handling and workflow orchestration separated: request objects validate incoming symbols, intervals, limits, and forecast periods, while action classes perform market loading, forecasting, syncing, and cache warming.

## Local URLs

- Market dashboard: `https://llm-timesfm.test/markets`
- Analysis scoreboard: `https://llm-timesfm.test/markets/analyses/BTCUSDT`
- Forecast statistics: `https://llm-timesfm.test/markets/stats/BTCUSDT`

## Automation

Run the full automated loop:

```bash
composer automation
```

For local frontend development with Herd:

```bash
composer dev
```

## Data Pipeline

The scheduled automation keeps the local SQLite database fresh:

- market snapshots update every second
- Binance market metadata refreshes daily
- short candle gaps are filled every minute
- stored forecasts are evaluated every minute when real candles are available
- manual prediction stakes are resolved once their target candle has closed
- new short-range analysis forecasts are created automatically every minute for the most active markets
- the market chart includes the latest ticker snapshot as a live point, so it can update every second between candle closes
- the market chart displays analyzer forecast points, endpoint labels, chart metrics, analyzer lanes, and a point ledger so predicted points are visible before real candles arrive

## Market Dashboard

- Pair Finder searches by first and second currency, and the First currency field shows a live list from stored source data with direct pin controls for the best matching pair.
- Pinned rates can be added or removed directly from the dashboard and are remembered for the current browser session.
- Automatic analysis engines draw their forecast points on the market graph and are scored later against real candles.
- The chart keeps scale values, latest price, analyzer endpoints, engine metrics, and the visible point ledger on screen while hover tooltips and zoom controls remain available.
- Prediction Stake saves a user-entered target time, target price, direction, confidence, and note for the selected market.
- The visible interface shows market rows, live ticks, charts, pinned rates, and forecasts; raw payload blocks are not shown on the screen.

## Analysis Results

- The analysis scoreboard compares every stored analyzer by checked points, pending points, average error, and direction accuracy.
- Evaluated analysis points show predicted price, actual candle close, and error percentage.
- Analysis runs stay separated by engine so weak engines are visible instead of being mixed into one number.

## Performance

- SQLite runs with WAL mode, a busy timeout, and normal sync settings for faster local reads while the scheduler writes.
- Hot market, history, forecast, analysis, and statistics reads are cached with short real-time TTLs.
- Redis cache is supported when available; file cache remains the default for out-of-box Herd use.
- Dashboard caches are warmed automatically after ticker sync for the most active configured symbols.
- Crypto tables include composite indexes for dashboard lists, latest snapshots, candle windows, forecast runs, forecast-point evaluation, and prediction stake resolution.
- Binance ticker, metadata, and candle imports use bulk upserts so repeated JSON updates do not create duplicates.

Warm the hot dashboard cache manually:

```bash
php artisan crypto:warm-dashboard-cache --limit=3
```

## Verification

```bash
./vendor/bin/pint --dirty --test
php artisan test
npm run build
```

## Project Rules

See `AGENTS.md` and `docs/CODEX_WORKFLOW.md`.

Core rules:

- Livewire-only public pages.
- Never use Volt.
- No application controllers for public pages.
- No traditional Blade pages.
- Validate incoming Livewire route, Livewire action, and Artisan command data through request objects before running workflows.
- Keep workflow logic in actions; Livewire components and Artisan commands should stay thin.
- No authentication or user account system.
- Update `CHANGELOG.md`, commit, and push after every completed prompt.
- Keep all Markdown files synchronized with current project behavior.
