# Livewire-Only Public Automation Plan

> Superpowers workflow note: execute this plan task-by-task, keep tests green, update the changelog, then commit and push.

**Goal:** Keep the project fully public, automated, and Livewire-only.

**Repository:** `https://github.com/goleaf/llm-timesfm`

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
- [x] Keep all Markdown documentation synchronized after each prompt.
- [x] Push the public repository on `main`.
- [x] Keep realtime Livewire screens backed by short-lived cache actions and cache warming.
- [x] Keep database growth safe with composite indexes and duplicate-safe bulk imports.

## Guard Rails

- Architecture tests must fail if login, registration, user tables, Volt, public controllers, or non-Livewire public Blade screens return.
- The changelog must stay readable for a project owner, not written as a code diff.
- Automation must stay scheduler-driven so the project can run unattended.
- Performance changes must keep data access in actions/models, never in Blade, and must include tests for cache or index behavior.
