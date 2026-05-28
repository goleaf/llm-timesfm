# Changelog

This file is the human-readable history of the project. It must be updated after every completed work session before committing and pushing.

## 2026-05-28

- Added faster local database settings, Redis-compatible dashboard caching, automatic cache warming, bulk market-data writes, and extra performance tests so the real-time screens can keep updating smoothly as stored market history grows.
- Refreshed the project documentation so every Markdown file now points to the public repository, describes the Livewire-only rule, explains the automated market pipeline, and records the required commit-and-push workflow.
- Converted the project into an open public crypto forecasting dashboard with no login, registration, user accounts, password reset, or private access flow.
- Removed the default Laravel connection systems that were not needed for this public project, including database-backed sessions, cache storage, queue jobs, mail settings, cloud storage settings, and user tables.
- Added automated market ingestion for popular Binance spot pairs, including full ticker snapshots, market metadata, short candle history, forecast storage, and forecast accuracy tracking.
- Added a real-time market screen that shows selected crypto pairs, current market data, JSON history, charted candle history, and forecast output without reloading the page.
- Added a real-time statistics screen that compares stored forecasts against actual market candles and shows accuracy metrics with charts.
- Added scheduler automation so the project can keep market snapshots, missing candle history, new forecasts, and forecast evaluations updated by itself.
- Connected the project to Laravel Herd with HTTPS at `https://llm-timesfm.test`.
- Added project rules that require Livewire-only public pages, forbid Volt, forbid application controllers for public screens, and require a changelog update plus git push after every completed prompt.
- Added architecture checks that fail if login routes, registration routes, application controllers, Volt usage, traditional Blade pages, or database-backed connection subsystems are reintroduced.
