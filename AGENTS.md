# AGENTS.md - LLM TimesFM Crypto

## Project Mode

This is an open, public, fully automated Laravel project.

- No authentication, login, registration, users, password reset, private panels, or role systems.
- No application controllers for public pages.
- Public pages must be full-page Livewire components.
- Do not use Volt, ever.
- Do not add traditional Blade pages rendered from controllers or `Route::view`.
- Blade files are allowed only as Livewire component views and the single Livewire layout shell.
- Keep Binance and TimesFM integrations public-source only, configured through `.env` and `config/crypto.php`.
- Automation must run through Artisan commands and Laravel Scheduler, not queue workers.
- Use Eloquent models and actions for data access. Do not query in Blade.

## Required End-Of-Prompt Workflow

Every completed Codex task must do these steps before the final response:

1. Update `CHANGELOG.md` in normal human language.
2. Run formatting and verification:
   - `./vendor/bin/pint --dirty --test`
   - `php artisan test`
   - `npm run build`
3. Inspect `git status --short`.
4. Commit with a Conventional Commit message.
5. Push the commit to the public GitHub repository.
6. In the final response, report the commit hash, pushed branch, and the useful verification results.

If pushing is blocked by authentication, network, or repository permissions, state the exact blocker and leave the local commit ready.

## Changelog Rules

- `CHANGELOG.md` is the permanent human-readable project history.
- Write what changed for a user or operator, not filenames or implementation trivia.
- Do not paste code, class names, package internals, or raw stack traces.
- Keep entries grouped by date, newest first.
- Every task that changes behavior, workflow, UI, automation, or project rules must add an entry.

## GitHub Rules

- The repository must stay public.
- Push after each completed prompt when verification passes.
- Do not commit `.env`, local databases, local Python environments, `vendor`, `node_modules`, or build output.
- Keep commit messages in Conventional Commits format.
