# LLM TimesFM Crypto

Open public Laravel 13 and Livewire 4 dashboard for real-time crypto market history, TimesFM-compatible forecasts, and forecast accuracy tracking.

Public repository: `https://github.com/goleaf/llm-timesfm`

## What It Does

- Reads public Binance Spot JSON data.
- Stores market snapshots, short candle history, forecasts, and forecast accuracy locally.
- Shows real-time Livewire screens without page reloads.
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
