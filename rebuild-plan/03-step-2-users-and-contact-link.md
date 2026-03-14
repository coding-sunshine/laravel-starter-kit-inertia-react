# Step 2: Users and User–Contact Link

## Goal

Add **users.contact_id** and migrate existing users so each user links to one Contact (person). Import users from MySQL and set contact_id using the lead_id→contact_id map from Step 1. Ensure **each v3 subscriber becomes an org owner in v4**: create one organization per subscriber with `owner_id` = that user and add the user to `organization_user` for that org (so they have their own org and can manage team under it). Optional: evaluate **genealabs/laravel-governor** for ownership-based scoping (see 00-kit-package-alignment.md P3).

## Suggested implementation order

1. **Migration**: Add `contact_id` (nullable, FK → contacts) to users table.
2. **Model**: Update kit User model — fillable `contact_id`, relationship `contact()`.
3. **Import command**: Implement `fusion:import-users` (or link pass) using Step 1 map; ensure users exist in new DB (by email or prior import) then set contact_id.
4. **Verification**: Implement `fusion:verify-import-users`.

No new models to generate via make:model:full; this step only extends the existing User.

## Starter Kit References

- **Migrations**: `database/migrations/` — add column to existing `users` table (new migration in new app).
- **User model**: Kit’s User in `app/Models/User.php` — add contact_id, relationship contact().
- **Actions**: `docs/developer/backend/actions/README.md` — e.g. CreateUser, UpdateUser; extend if user creation from contact is needed.
- **Seeders**: `database/seeders/` — UserFactory; ensure new users can have contact_id when created from contact.

## Deliverables

1. **Migration**
   - Add to **users**: `contact_id` (nullable, FK → contacts, nullOnDelete). Index contact_id.

2. **Model**
   - **User**: Add fillable `contact_id`, relationship `belongsTo(Contact::class)`. Optional: inverse on Contact `hasOne(User::class)`.

3. **Policies / authorization**
   - If needed, ensure user can only edit contact linked to self when appropriate (e.g. profile contact). Document in permissions.

4. **Import command**
   - Extend or create `php artisan fusion:import-users`. Source: MySQL `users`. Target: PostgreSQL `users` (already created by kit; this step only updates/links). For each legacy user: (1) ensure user exists in new DB (by email or by importing users in this command); (2) read legacy `users.lead_id`; (3) resolve to new contact_id via Step 1 map; (4) set `user.contact_id = contact_id`. If new app creates users on first login (e.g. OAuth), document that import can run after Step 1 and match by email to set contact_id.
   - **Organizations for subscribers:** For each imported user that has the **subscriber** role (or that you classify as a v3 subscriber), create exactly **one organization** for them. Full logic:

     ```php
     // In fusion:import-users — org creation block (runs per subscriber)
     $orgName = $user->contact?->company_name          // prefer business name from contact
              ?? $user->contact?->full_name . "'s Org"  // fallback: "John Smith's Org"
              ?? $user->name . "'s Org";                // last resort

     // IDEMPOTENT — skip if org already exists for this owner
     $org = Organization::firstOrCreate(
         ['owner_id' => $user->id],
         [
             'name'       => $orgName,
             'slug'       => Str::slug($orgName),
             'created_at' => $user->created_at,
         ]
     );

     // Add user as member of their own org (idempotent)
     $org->users()->syncWithoutDetaching([
         $user->id => ['is_default' => true, 'role' => 'owner'],
     ]);
     ```

     **Key rules enforced by import:**
     - One org per subscriber — `firstOrCreate` on `owner_id` prevents duplicates on re-run.
     - Org name sourced from `contact.company_name` (business name) first so it matches what the subscriber called their business in v3.
     - `is_default = true` so that when the subscriber logs in, their org is auto-selected without needing to choose.
     - After import, every CRM data row (contacts, projects, lots, reservations, tasks) seeded in subsequent steps must carry the correct `organization_id` pointing at this org.
     - **Non-subscriber users** (admins, agents, bdm users): do NOT auto-create an org; they are added to the superadmin org or remain org-less until manually assigned.

     **Policy reminder (enforced in Step 0):** After import, the `OrganizationPolicy::create()` gate ensures subscribers can never create a second org themselves. The import is the only code path that bulk-creates orgs; Step 23 signup is the other.

## DB Design (this step)

- **users.contact_id** added. All other tables unchanged.

## Data Import

- **Source**: MySQL `users` (id, email, lead_id, …).
- **Target**: PostgreSQL `users.contact_id`.
- **Mapping**: Use lead_id→contact_id map from Step 1. For each user in new DB (matched by email or by prior user import), set contact_id = map[user.lead_id].
- **Script**: Same or separate command, e.g. `fusion:import-users` or `fusion:import-users-and-link-contacts`. Ensure users are created first if not already (e.g. from registration or a previous user import); then run link pass.

## API Keys Management (Confirmed from Live v3 Site)

> **Source**: Live audit of `/api-keys` — subscribers create programmatic API keys; admin approves/revokes.

### Implementation — Laravel Sanctum (already in kit)

Use `laravel/sanctum` personal access tokens with an approval layer added via migration.

**Migration** (additional columns on `personal_access_tokens`):
```sql
ALTER TABLE personal_access_tokens
  ADD COLUMN is_approved BOOLEAN DEFAULT FALSE,
  ADD COLUMN approved_by BIGINT UNSIGNED NULL REFERENCES users(id),
  ADD COLUMN approved_at TIMESTAMP NULL,
  ADD COLUMN description TEXT NULL,
  ADD COLUMN status ENUM('pending_approval', 'active', 'revoked') DEFAULT 'pending_approval';
```

Or create a separate `api_key_requests` table if you prefer not to modify Sanctum's table directly:
```
api_key_requests: id, user_id (FK → users), token_id (FK → personal_access_tokens, nullable),
  name, description, scopes (json), status (pending_approval/active/revoked),
  approved_by (FK → users, nullable), approved_at (timestamp), created_at
```

**Subscriber flow** (`/settings/api-keys`):
1. Subscriber enters name + description → POST creates a Sanctum token with `status=pending_approval`
2. Token is NOT returned yet (security: shown only once upon approval)
3. Badge shows "Pending Approval"

**Admin flow** (`/admin/api-keys`):
1. DataTable of all pending + active + revoked keys
2. Approve → status = active; generate and display token once to the requester (email via laravel-database-mail `ApiKeyApprovedEvent`)
3. Revoke → call `$token->delete()` on Sanctum token; status = revoked

**Subscriber `/settings/api-keys` page**:
- List own keys: name, scopes, last_used_at, status badge
- Create button → slide-over (name + description + scope checkboxes)
- Token shown ONCE in a copy-to-clipboard modal after admin approval (delivered via broadcast or email)
- Revoke own key button

**Admin `/admin/api-keys` page**:
- DataTable: user, key name, description, status badge, created_at, last_used_at, approved_by, actions (Approve / Revoke)
- Quick Views: Pending Approval | Active | Revoked

**Scopes** (suggested): `contacts:read`, `contacts:write`, `projects:read`, `lots:read`, `reservations:read`, `sales:read`, `tasks:read`

**Feature flag**: Gate behind `ApiAccessFeature` (Pennant class — add to Step 0 feature flag list alongside AiToolsFeature etc.).

---

## AI Enhancements

- None required. *Optional (e.g. suggest best contact when linking user):* **13-ai-native-features-by-section.md** § Step 2.

## Verification (verifiable results, data fully imported)

- After link pass, verify: **users** = 1,483; every user that had lead_id in MySQL has contact_id set in PostgreSQL; no broken FK (users.contact_id → contacts.id). Implement `php artisan fusion:verify-import-users`. See **11-verification-per-step.md** § Step 2. Baseline counts: **10-mysql-usage-and-skip-guide.md**.

## Human-in-the-loop (end of step)

**STOP after this step. Do not proceed to Step 4 until the human has completed the checklist below.**

Human must:
- [ ] Confirm users.contact_id migration ran.
- [ ] Confirm user→contact link pass (e.g. `fusion:import-users` or link command) completed.
- [ ] Confirm each v3 subscriber has their own organization (organizations.owner_id = that user) and is in organization_user for that org (subscriber = org owner in v4).
- [ ] Confirm verification PASS: users = 1,483, all expected users have contact_id set, no broken FKs.
- [ ] Optionally spot-check a few users (e.g. in Filament) to see linked contact and subscriber org ownership.
- [ ] Approve proceeding to Step 4 (reservations, sales, commissions).

## Acceptance Criteria

- [ ] users.contact_id migration runs.
- [ ] User model has contact() relationship.
- [ ] Import links every legacy user to the correct contact (by lead_id → contact_id); users without a lead_id have contact_id null.
- [ ] Each v3 subscriber has one organization with owner_id = that user (org owner in v4); user is in organization_user for that org.
- [ ] Verification confirms users = 1,483 and all expected users have contact_id set.
