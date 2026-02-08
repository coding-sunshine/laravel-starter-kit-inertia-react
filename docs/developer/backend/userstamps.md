# Userstamps

**Userstamps** record which user created or last updated a record via `created_by` and `updated_by` columns, using the **wildside/userstamps** package (namespace: `Mattiverse\Userstamps`).

## Usage

1. **Migration**: Add nullable `created_by` and `updated_by` foreign keys to `users` (e.g. `$table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();` and same for `updated_by`).
2. **Model**: Use the trait and set the userstamps columns:

   ```php
   use Mattiverse\Userstamps\Traits\Userstamps;

   class YourModel extends Model
   {
       use Userstamps;

       // Trait uses 'created_by' and 'updated_by' by default.
   }
   ```

3. **Auth**: When a user is authenticated, the trait fills `created_by` on create and `updated_by` on create/update. When no user is logged in, the columns remain `null`.

## Current use

- **ContactSubmission**: `created_by` and `updated_by` on `contact_submissions`; model uses `Userstamps` trait.

## References

- [wildside/userstamps](https://github.com/wildside/userstamps) — trait and configuration.
