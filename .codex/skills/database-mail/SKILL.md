---
name: database-mail
description: "Database-backed email templates with martinpetricko/laravel-database-mail. Activates when adding events that should send emails from DB templates; creating or editing mail templates; or when the user mentions database mail, email templates, event-triggered emails, or TriggersDatabaseMail."
license: MIT
metadata:
  author: project
---

# Database Mail (email templates)

## When to apply

Activate when adding a new event that should send an email from a database-defined template, or when creating/editing mail templates or the user mentions database mail / email templates.

## Rules

1. **New event:** Implement `TriggersDatabaseMail`, use `CanTriggerDatabaseMail`; define `getName()`, `getDescription()`, `getRecipients()`, and optionally `getAttachments()`. Register the event in `config/database-mail.php` under `'events'`.
2. **Recipients:** `Recipient('Label', fn (Event $e) => [$e->user])`. Event public properties are available in template Blade (e.g. `$user`).
3. **Templates:** Create via seeders or Filament plugin; set event, subject, body, recipients keys, attachments keys, is_active.

## Documentation

- **docs/developer/backend/database-mail.md** — full guide
- **docs/developer/backend/README.md** — Database Mail bullet
