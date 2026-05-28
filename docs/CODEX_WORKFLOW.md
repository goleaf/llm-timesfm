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
