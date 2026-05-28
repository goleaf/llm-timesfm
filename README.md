# LLM TimesFM Crypto

Open public Laravel 13 and Livewire 4 dashboard for real-time crypto market history, TimesFM-compatible forecasts, and forecast accuracy tracking.

Public repository: `https://github.com/goleaf/llm-timesfm`

## What It Does

- Reads public Binance Spot JSON data.
- Stores market snapshots, short candle history, forecasts, and forecast accuracy locally.
- Shows real-time Livewire screens without page reloads.
- Shows interactive SVG charts with hover tooltips for candle, live price, forecast, and forecast-accuracy details.
- Uses a wide Full HD dashboard layout with a dedicated market list, chart workspace, and structured JSON inspector column.
- Runs scheduled automation for market updates, missing candle backfill, forecast creation, and forecast evaluation.
- Keeps the project open: no login, no user accounts, no private panels.
- Keeps public pages as full-page Livewire components. Volt and standard controllers are intentionally not used.

## Local URLs

- Market dashboard: `https://llm-timesfm.test/markets`
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
- new short-range forecasts are created every five minutes
- the market chart includes the latest ticker snapshot as a live point, so it can update every second between candle closes

## Market Inspector

- Ticker JSON history is shown as structured snapshot cards instead of a narrow raw JSON block.
- Each snapshot includes price, order book, volume, exchange event, raw field, and raw JSON sections.
- The first recent snapshot opens by default so the latest market payload is visible immediately on wide screens.

## Performance

- SQLite runs with WAL mode, a busy timeout, and normal sync settings for faster local reads while the scheduler writes.
- Hot market, history, forecast, and statistics reads are cached with short real-time TTLs.
- Redis cache is supported when available; file cache remains the default for out-of-box Herd use.
- Dashboard caches are warmed automatically after ticker sync for the most active configured symbols.
- Crypto tables include composite indexes for dashboard lists, latest snapshots, candle windows, forecast runs, and forecast-point evaluation.
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
- No authentication or user account system.
- Update `CHANGELOG.md`, commit, and push after every completed prompt.
- Keep all Markdown files synchronized with current project behavior.
