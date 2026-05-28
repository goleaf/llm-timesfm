# Crypto TimesFM Dashboard Design

## Goal

Build a Laravel 13 and Livewire 4 dashboard that polls popular Binance Spot USDT markets, stores one-second ticker snapshots locally, shows live market history for the selected market, runs TimesFM-compatible forecasts over stored candle history, and measures forecast accuracy after real candles arrive.

## Architecture

Laravel owns the UI, database, scheduler, commands, and orchestration. Binance data enters through Eloquent-backed actions, not from Blade templates. Full-page Livewire components poll the local database while the scheduler refreshes snapshots, fills missing short candles, creates forecasts, and evaluates forecast accuracy.

Incoming route symbols, Livewire action values, and Artisan command options are normalized through request objects before workflows run. Livewire components and commands are intentionally thin: they collect input, build a request object, call an action, and render or print the result. Market loading, forecast runs, syncs, backfills, forecast cycles, and watch loops belong in action classes.

TimesFM runs outside PHP through `python/timesfm_forecast.py`. The Laravel action sends close-price history as JSON over stdin and stores the returned forecast payload. When the Python environment or model is not installed, the bridge returns a baseline last-value forecast so the UI remains usable on an 8 GB M1 laptop.

The project is public and Livewire-only. It intentionally has no authentication system, user account model, private panel, standard public controller, traditional public Blade page, or Volt component.

The realtime read path is cached through short-lived Laravel cache entries. SQLite remains the local source of truth and runs with WAL-oriented settings, while Redis can be used as a faster cache store when available. Scheduler-driven ticker updates refresh market data and warm the most common dashboard reads so Livewire polling does not repeat the same database work for every viewer.

Charts are rendered as SVG from server-built point payloads. Each payload contains the visible polyline coordinates, scale ticks, summary metrics, analyzer lanes, point ledger rows, and the hover rows for every point. Browser JavaScript reads the hover payload, finds the nearest point under the mouse, shows a marker, guide line, data tooltip, and manages client-side SVG zoom with wheel input, controls, drag panning, and viewport restoration after Livewire morphs. The market chart adds the latest ticker snapshot as a live point so the graph can move every second even when the candle interval has not closed yet.

The market screen targets Full HD workstations. It uses a wide shell with a left pair finder, source-driven first-currency list with pin controls, central chart workspace, live tick feed, right-side pinned rates, and forecast desk. The visible interface is market-focused and does not show raw payload blocks.

The left market list includes compact movement charts for visible pairs. Those mini charts are built from action-prepared latest snapshot fields and rendered as small SVGs beside the existing live price, update time, and change values.

The Live Ticks block uses prepared tick rows from stored snapshot data. It must display stable, useful market fields such as latest price, tick movement, 24-hour range, compact quote volume, and trade count, and it must not render missing bid or ask fields as zeroes.

The forecast statistics screen is a full diagnostic surface, not a small summary. It shows market context, source counts, coverage, MAPE, MAE, direction accuracy, engine breakdowns, ranked best and worst points, evaluated point details, pending target rows, and forecast-run windows from preloaded action data.

All public dashboard screens share a workbench visual layer. The market, analysis, and statistics pages use the same sticky header treatment, dense panel surfaces, chart framing, custom scroll regions, hoverable data rows, and wide monitor spacing so the application reads as one operational tool.

The public interface is Russian-first with a persistent RU / EN language switcher. User-facing dashboard copy, action messages, chart labels, analysis text, and statistics text are translated through language files so the same Livewire screens can render in Russian or English.

## Data Flow

1. The scheduler fetches configured Binance symbols from `/api/v3/ticker/24hr`.
2. The metadata sync stores Binance exchange information, precision, filters, and trading status.
3. Missing short candles are detected by local candle timestamps and only missing ranges are requested.
4. The market dashboard reads eager-loaded local data and renders the pair finder, pinned rates, SVG chart, live ticks, and forecast results.
5. The forecast cycle reads candles, calls the Python bridge, and stores forecast runs plus forecast points.
6. The evaluator compares forecast points with actual candle closes when the target candles exist.
7. The statistics dashboard shows evaluated forecast quality with live-updating charts.
8. Cache warming prepares the most common market and statistics reads after ticker updates.
9. Chart hover payloads expose detailed candle, live ticker, forecast, actual, and error values without querying from Blade.
10. The market board builder converts eager-loaded assets into pair finder rows, first-currency options, pinned rate rows, and selected-market summary fields before they reach the Livewire view.
11. Request objects validate symbols, intervals, forecast periods, limits, and watch windows before the related action is called.
12. Locale middleware applies the selected session language before Livewire renders dashboards, actions build messages, and chart payload builders create labels.
13. The forecast statistics reader prepares engine rows, ranked points, pending points, and run details before the Livewire view renders.

## Constraints

- No raw SQL.
- No queries in Blade.
- No model aggregates inside loops.
- SQLite is the first local database.
- Binance is the first data source because it supports public market JSON and one-second update patterns.
- TimesFM can run through the local Python environment, with baseline fallback when disabled.
- No auth, users, private panels, Volt, public controllers, or traditional public Blade pages.
- Livewire components and Artisan commands should not own workflow logic; they should hand validated request data to actions.
- The public market interface should not expose raw payload panels; keep source payloads in storage and show trading-friendly market fields.
- Realtime reads should use cache actions with short TTLs and explicit invalidation after market writes.
- Growing market tables need composite indexes that match actual Eloquent filters and sort order.
- Full HD layout should stay wide and data-dense; do not compress the dashboard back into a narrow centered column.
- Reuse the shared workbench visual layer for public dashboard pages; avoid one-off narrow shells or unrelated page styling.
- Russian remains the default public language, and every new public UI label needs both Russian and English translations.
- Every completed prompt must update the changelog, keep Markdown current, pass checks, commit, and push.
