# Crypto TimesFM Dashboard Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship a local realtime crypto dashboard with Binance market history, TimesFM-compatible forecasting, and forecast accuracy tracking.

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
- [x] Render market list, selected market stats, SVG chart, live market history, and forecast panel.
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

### Task 9: Realtime Performance

- [x] Add SQLite settings for faster local read/write concurrency.
- [x] Add Redis-compatible short-lived dashboard caching with file cache fallback.
- [x] Warm hot dashboard caches after ticker updates.
- [x] Add composite indexes for realtime dashboard and forecast-evaluation query patterns.
- [x] Use bulk upserts for repeated Binance JSON imports.
- [x] Add tests for indexes, cache hits, cache invalidation, warming, and duplicate-safe imports.

### Task 10: Interactive Charts

- [x] Add hover tooltips for market, forecast, and forecast-accuracy charts.
- [x] Show the nearest point marker and vertical guide line on mouse hover.
- [x] Include candle, live ticker, forecast, actual, and error details in chart payloads.
- [x] Append the latest ticker snapshot to the market chart so it updates every second between candle closes.
- [x] Preserve hover feedback after Livewire polling morphs the DOM.
- [x] Keep market chart scale labels, latest marker, analyzer endpoint labels, metrics, analyzer lanes, and point ledger visible without requiring hover.
- [x] Add market chart zoom controls, mouse-wheel zoom, drag panning, and Livewire-safe viewport restore.

### Task 11: Full HD Market Workspace

- [x] Widen the market and statistics dashboards for Full HD monitors.
- [x] Split the market dashboard into pair finder, chart workspace, pinned rates, live tick feed, and forecast desk.
- [x] Remove visible raw payload panels from the market interface.
- [x] Keep source payloads stored locally while showing trading-friendly fields on screen.

### Task 12: Request And Action Architecture

- [x] Move incoming dashboard and command parameters into validated request objects.
- [x] Move market history loading and forecast-button behavior out of Livewire and into actions.
- [x] Move command orchestration for syncs, backfills, forecast cycles, and watch loops into actions.
- [x] Add tests that verify invalid dashboard input is ignored and command payloads are validated before actions run.

### Task 13: Pinned Pair Dashboard

- [x] Add first-currency and second-currency filters to the left pair finder.
- [x] Show a live first-currency list from stored source data with direct pin controls for the best matching pair.
- [x] Add browser-session pinned rates that can be added and removed from the dashboard.
- [x] Keep pinned rates, live ticks, chart data, and forecast controls inside the same one-second Livewire update surface.
- [x] Rebuild the market screen using a restrained technical dashboard design.

### Task 14: Russian And English Interface

- [x] Make Russian the default public interface language.
- [x] Add a persistent RU / EN language switcher for the public Livewire shell.
- [x] Move dashboard, action message, chart label, analysis, and statistics copy into translation files.
- [x] Verify the dashboard can render in Russian by default and English from the current browser session.

### Task 15: Expanded Forecast Statistics

- [x] Add market context and expanded forecast accuracy totals to the statistics page.
- [x] Add engine-by-engine scoring with runs, evaluated points, pending points, MAPE, MAE, and direction accuracy.
- [x] Add best and worst evaluated forecast points, detailed evaluated rows, pending forecast targets, and full forecast-run windows.
- [x] Keep the statistics page Livewire-only, Russian-first, translated, and backed by action-prepared data.
