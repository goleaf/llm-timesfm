# Livewire-Only Public Automation Plan

> Superpowers workflow note: execute this plan task-by-task, keep tests green, update the changelog, then commit and push.

**Goal:** Keep the project fully public, automated, and Livewire-only.

## Tasks

- [x] Remove default app controllers.
- [x] Forbid traditional Blade pages outside Livewire views and the Livewire layout shell.
- [x] Forbid Volt usage.
- [x] Remove authentication, user accounts, password reset, database sessions, database cache, and database queue dependencies.
- [x] Keep all public routes on full-page Livewire components.
- [x] Add architecture tests that protect these rules.
- [x] Add permanent project rules to `AGENTS.md`.
- [x] Add a human-readable changelog process.
- [x] Require verification, commit, and push at the end of each completed prompt.
