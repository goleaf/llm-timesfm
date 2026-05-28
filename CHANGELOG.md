# Changelog

This file is the human-readable history of the project. It must be updated after every completed work session before committing and pushing.

## 2026-05-28

- Expanded the market chart so important values are visible without hovering: price scale, latest marker, analyzer endpoint labels, chart metrics, analyzer lanes, and a live point ledger now stay on screen while the hover tooltip remains available.
- Added a live first-currency list inside the market finder so available currencies come from stored source data, show their matching pairs, and can pin the best matching rate directly from the field.
- Connected automatic real-data analysis engines for trend, moving average, EMA, momentum, and TimesFM when available; the market chart now shows each analyzer's forecast points, and a new analysis scoreboard compares every stored analysis with real candle data.
- Added manual prediction stakes on the market dashboard so a user can choose a market, target time, expected price, direction, confidence, and note, then let the system resolve the stake against stored candle data.
- Removed browser console noise on the market dashboard by adding a real site icon and making live dashboard cache reads recover automatically from stale values that no longer match the expected data shape.
- Rebuilt the market screen as a trading dashboard with a left-side pair finder, first and second currency filters, editable pinned rates, a live tick feed, one-second updates, and no visible raw payload panels.
- Reworked the application architecture so screen input and command options are validated before workflows run, while market loading, forecast runs, syncing, backfills, and watch loops now live in dedicated workflow actions instead of being mixed into the user interface or command layer.
- Reworked the market dashboard for Full HD monitors with a much wider workspace, a taller chart, and a dedicated live market workspace.
- Added detailed interactive chart hover behavior so market and forecast-statistics graphs show the nearest point, a marker, a guide line, and full point details; the market graph now includes the latest live ticker point so it can refresh every second between candle closes.
- Added faster local database settings, Redis-compatible dashboard caching, automatic cache warming, bulk market-data writes, and extra performance tests so the real-time screens can keep updating smoothly as stored market history grows.
- Refreshed the project documentation so every Markdown file now points to the public repository, describes the Livewire-only rule, explains the automated market pipeline, and records the required commit-and-push workflow.
- Converted the project into an open public crypto forecasting dashboard with no login, registration, user accounts, password reset, or private access flow.
- Removed the default Laravel connection systems that were not needed for this public project, including database-backed sessions, cache storage, queue jobs, mail settings, cloud storage settings, and user tables.
- Added automated market ingestion for popular Binance spot pairs, including full ticker snapshots, market metadata, short candle history, forecast storage, and forecast accuracy tracking.
- Added a real-time market screen that shows selected crypto pairs, current market data, live tick history, charted candle history, and forecast output without reloading the page.
- Added a real-time statistics screen that compares stored forecasts against actual market candles and shows accuracy metrics with charts.
- Added scheduler automation so the project can keep market snapshots, missing candle history, new forecasts, and forecast evaluations updated by itself.
- Connected the project to Laravel Herd with HTTPS at `https://llm-timesfm.test`.
- Added project rules that require Livewire-only public pages, forbid Volt, forbid application controllers for public screens, and require a changelog update plus git push after every completed prompt.
- Added architecture checks that fail if login routes, registration routes, application controllers, Volt usage, traditional Blade pages, or database-backed connection subsystems are reintroduced.
