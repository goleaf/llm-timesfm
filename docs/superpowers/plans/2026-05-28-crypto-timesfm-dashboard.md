# Crypto TimesFM Dashboard Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship a local realtime crypto dashboard with Binance JSON history and TimesFM-compatible forecasting.

**Architecture:** Laravel commands ingest Binance data into SQLite through Eloquent actions. Livewire renders from local models with one-second polling. Forecasts are delegated to a Python bridge and stored back in Eloquent.

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
