# Crypto TimesFM Dashboard Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship a local realtime crypto dashboard with Binance JSON history, TimesFM-compatible forecasting, and forecast accuracy tracking.

**Architecture:** Laravel commands ingest Binance data into SQLite through Eloquent actions. Full-page Livewire components render from local models with polling. Forecasts are delegated to a Python bridge, stored as forecast runs and forecast points, then compared against later candles.

**Tech Stack:** Laravel 13, Livewire 4, Blade, Vite, SCSS, Tailwind CSS 4, SQLite, Binance Spot public API, Python TimesFM.

---

### Task 1: Laravel Baseline

- [x] Scaffold Laravel 13 with SQLite, Pest, Vite, Tailwind CSS 4, and no auth starter.
- [x] Install Livewire 4.
- [x] Fix the generated local `APP_URL`.
- [x] Switch the Vite stylesheet entry to SCSS.

### Task 2: Market Storage

- [x] Add migrations for crypto assets, ticker snapshots, candles, and forecasts.
- [x] Add Eloquent models with fillable fields, casts, relationships, and scoped queries.
- [x] Add model factories for crypto tests.

### Task 3: Binance Ingestion

- [x] Add `FetchBinanceTickersAction`.
- [x] Add `FetchBinanceKlinesAction`.
- [x] Add commands for one-shot sync, continuous one-second polling, and history backfill.
- [x] Add scheduler entry for one-second ticker sync.

### Task 4: Livewire Dashboard

- [x] Add a full-page `MarketsDashboard` component.
- [x] Add the `/markets/{symbol?}` route group.
- [x] Render market list, selected market stats, SVG chart, JSON snapshot history, and forecast panel.
- [x] Keep Blade query-free; all data is passed from the component.

### Task 5: TimesFM Bridge

- [x] Add a Python bridge that uses TimesFM when installed.
- [x] Add baseline fallback for local runs without TimesFM weights.
- [x] Add `RunTimesFmForecastAction` and command.

### Task 6: Verification

- [x] Add failing feature tests before production code.
- [x] Run migrations.
- [x] Run focused crypto tests.
- [x] Run full test suite.
- [x] Run Vite build.
- [x] Start local dev server and verify the dashboard route.

### Task 7: Forecast Accuracy

- [x] Store forecast points with their target candle time.
- [x] Compare mature forecast points with real candle closes.
- [x] Track mean error, percentage error, and direction accuracy.
- [x] Add a real-time statistics screen for forecast quality.

### Task 8: Public Automation Rules

- [x] Keep the project open with no user accounts or login system.
- [x] Keep public screens on full-page Livewire components.
- [x] Forbid Volt and standard public controllers.
- [x] Keep every completed prompt documented, committed, and pushed to the public repository.
