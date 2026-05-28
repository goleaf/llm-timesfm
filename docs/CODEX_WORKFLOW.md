# Codex Workflow

Public repository: `https://github.com/goleaf/llm-timesfm`

## Required Project Style

This project is built as a Livewire-only public application.

- Use full-page Livewire components for every public screen.
- Do not create application controllers for public pages.
- Do not create traditional Blade pages.
- Do not use Volt.
- Keep the application open; do not add auth, accounts, roles, private panels, or password reset.
- Keep automation in Artisan commands and Laravel Scheduler.
- Keep all Markdown files aligned with the actual project state.
- Keep real-time reads behind short-lived cache actions instead of rebuilding the same dashboard queries in Livewire.
- Keep import writes batched where possible so repeated public JSON updates do not become per-row query loops.
- Keep chart interactivity data-driven: Livewire renders chart payload JSON, and browser JavaScript only handles pointer interaction.

## End Of Prompt Checklist

Before finishing a prompt:

1. Update `CHANGELOG.md` with a plain-language summary.
2. Update all relevant Markdown files.
3. Run `./vendor/bin/pint --dirty --test`.
4. Run `php artisan test`.
5. Run `npm run build`.
6. Review `git status --short`.
7. Commit using Conventional Commits.
8. Push to `origin/main`.
9. Report what was pushed and what passed.

## Human Changelog Style

Write for a project owner, not for a compiler.

- Good: "Added a statistics screen that shows whether forecasts were accurate."
- Avoid: "Added App\\Livewire\\ForecastStatsDashboard and crypto_forecast_points."
- Good: "Removed user accounts and login because the project is public."
- Avoid: "Deleted config/auth.php."

## Current Public Screens

- Market dashboard: `https://llm-timesfm.test/markets`
- Forecast statistics: `https://llm-timesfm.test/markets/stats/BTCUSDT`

Both screens use interactive SVG charts. Hovering a chart shows the nearest stored point, guide line, marker, timestamp, values, volumes, forecast data, or error statistics depending on the chart.

## Current Performance Defaults

- SQLite uses WAL mode, a busy timeout, and normal sync for local read/write concurrency.
- Cache defaults to file storage for Herd, with Redis available through the configured cache store.
- The scheduler warms the hottest dashboard cache entries after ticker updates.
- `php artisan crypto:warm-dashboard-cache --limit=3` can be run manually after large imports.
- New query patterns should get a matching Eloquent scope, short cache TTL, and migration-backed composite index.
