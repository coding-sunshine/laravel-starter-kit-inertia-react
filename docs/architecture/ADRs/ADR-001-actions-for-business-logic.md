# ADR-001: Use Action classes for business logic

## Status

Accepted

## Context

Business logic can be placed in controllers, service classes, or dedicated single-purpose classes. We need a consistent pattern that keeps controllers thin, is easy to test, and can be reused from HTTP, jobs, commands, and API handlers.

## Decision

We use dedicated **Action** classes in `app/Actions/`, named by what they do (e.g. `CreateUser`, `StoreContactSubmission`). Each action has a single `handle()` method and dependencies injected via the constructor. Controllers (and other callers) invoke actions rather than embedding business logic. Complex multi-model operations use `DB::transaction()` inside the action.

## Consequences

- **Easier:** Reuse the same logic from web, API, jobs, and Artisan commands; test actions in isolation; keep controllers as thin coordinators.
- **Harder:** More files and indirection; new contributors must follow the convention. Mitigated by project guidelines and `php artisan make:action`.
