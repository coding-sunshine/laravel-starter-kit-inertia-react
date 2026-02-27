# Phase 1 – Foundation & Core Fleet: Completion Status & Testing Guide

## Is Phase 1 totally complete?

**Substantially yes** for the doc’s “Done when” criteria. A few items are only partially done (see table below). You can run through all 10 resources and do full CRUD for **Locations**; for the other nine, backend CRUD works and Index/Create/Edit/Show pages load, but Create/Edit **forms are placeholders** (no full field set yet).

| Requirement | Status | Notes |
|-------------|--------|--------|
| **10 migrations** | ✅ Complete | All run; tables exist. |
| **Enums** | ✅ Complete | All listed enums in `app/Enums/Fleet/`. |
| **Models** | ✅ Complete | All 10 with `organization_id`, userstamps, soft deletes, main relationships. Vehicle does **not** yet use `HasMedia`, `HasTags`, `HasCategories` (can be added in a later step). |
| **Policies** | ✅ Complete | All 10; org-scoped. |
| **Form requests** | ⚠️ Partial | Only **Locations** have `StoreLocationRequest` + `UpdateLocationRequest`. Other 9 use inline validation in controllers. |
| **Controllers** | ✅ Complete | All 10 with index, show, create, store, edit, update, destroy; Inertia + auth. |
| **Routes** | ✅ Complete | Under `tenant` + `auth`; prefix `fleet`; resource routes for all 10. |
| **Inertia pages** | ⚠️ Partial | **Locations**: full Index + Create/Edit/Show with forms. **Other 9**: Index with table + pagination, and Create/Edit/Show **placeholders** (no full form fields). |
| **Done when** | ✅ Met | Migrations run; user can CRUD (Locations fully in UI; others via backend or by adding forms later); lists are org-scoped with filters/pagination; no errors when opening Index/Create/Edit/Show. |

---

## What success looks like

1. **Migrations**  
   - No errors when running `php artisan migrate`.  
   - Tables exist: `locations`, `cost_centers`, `drivers`, `trailers`, `vehicles`, `geofences`, `garages`, `fuel_stations`, `ev_charging_stations`, `operator_licences`.

2. **Auth & tenant**  
   - You are logged in and have selected an organization (tenant).  
   - Fleet routes are only available when an organization is selected.

3. **Locations (full CRUD in UI)**  
   - **Index:** List of locations (table), filters, pagination, “New location”, Edit/Delete per row.  
   - **Create:** Form (name, type, address, etc.) → submit → redirect to index with success; new record in DB.  
   - **Show:** One location’s details.  
   - **Edit:** Form pre-filled → submit → redirect to show/index; record updated.  
   - **Delete:** Delete button → confirm → record soft-deleted, redirect to index.

4. **Other 9 resources**  
   - **Index:** List (table), “New”, Edit/Delete links; no errors.  
   - **Create/Edit/Show:** Pages open without errors; for Create/Edit you see a placeholder (e.g. “New cost center” + Back). Full forms can be added later.

5. **Org scoping**  
   - Data you create is for the **current organization** only.  
   - Switching organization and opening the same fleet list shows only that org’s data (or empty).

6. **No console/Inertia errors**  
   - No “Page not found” or 500 when opening any Fleet Index/Create/Edit/Show.

---

## How to test everything

### 1. Prerequisites

- App URL: e.g. `http://laravel-starter-kit-inertia-react.test` (or your Herd/local URL).
- DB migrated: `php artisan migrate` (already done).
- Frontend built: `npm run build` (or run `npm run dev` and use that).
- Logged-in user that belongs to at least one organization.

### 2. Log in and select an organization

1. Open the app and log in (e.g. `admin@example.com` / `password` if you use the seed).
2. Ensure an organization is selected (e.g. org switcher in the UI).  
   If you don’t select one, Fleet routes may redirect or show nothing.

### 3. Smoke test: open every Index page

Visit each URL (replace the host if needed). Each should load without 500 or “Page not found”:

| Resource | URL |
|----------|-----|
| Locations | `/fleet/locations` |
| Cost centers | `/fleet/cost-centers` |
| Drivers | `/fleet/drivers` |
| Trailers | `/fleet/trailers` |
| Vehicles | `/fleet/vehicles` |
| Geofences | `/fleet/geofences` |
| Garages | `/fleet/garages` |
| Fuel stations | `/fleet/fuel-stations` |
| EV charging stations | `/fleet/ev-charging-stations` |
| Operator licences | `/fleet/operator-licences` |

You should see either an empty state (“No … yet”) or a table; no black screen or console errors.

### 4. Full CRUD test: Locations

1. **Create**  
   - Go to `/fleet/locations` → “New location”.  
   - Fill: Name, Type (e.g. Depot), Address (required), City, Postcode, optionally contact/notes, Active.  
   - Submit → redirect to index and see the new row.

2. **Read (list)**  
   - Index shows the new location in the table.

3. **Read (one)**  
   - Click the location name → Show page with details.

4. **Update**  
   - From Show or Index, click “Edit”.  
   - Change name or address → Submit → see updated data on Show/index.

5. **Delete**  
   - From Index, use Delete (trash) → confirm → row disappears (soft delete).

### 5. Optional: test other resources via Tinker (backend CRUD)

Backend supports full CRUD for all 10 resources. Example for one more (e.g. Driver):

```bash
php artisan tinker
```

```php
$orgId = \App\Services\TenantContext::id() ?? \App\Models\Organization::first()->id;
\App\Services\TenantContext::set(\App\Models\Organization::find($orgId));

\App\Models\Fleet\Driver::create([
    'organization_id' => $orgId,
    'first_name' => 'Test',
    'last_name' => 'Driver',
    'license_number' => 'DRV001',
    'license_expiry_date' => now()->addYears(2),
    'license_status' => 'valid',
    'status' => 'active',
]);
```

Then open `/fleet/drivers` and you should see that driver in the list. Same idea works for cost centers, trailers, vehicles, etc., to confirm backend and Index pages.

### 6. Checklist summary

- [ ] All 10 fleet Index pages load.
- [ ] Locations: Create → new row on index.
- [ ] Locations: Show opens from index.
- [ ] Locations: Edit → change → see update.
- [ ] Locations: Delete → row removed from list.
- [ ] No “Page not found” or 500 on any Fleet Index/Create/Edit/Show.
- [ ] Data is scoped to the current organization (create in org A, switch to org B, list is different or empty).

When all of the above pass, **Phase 1 is complete** for the “Done when” criteria. You can proceed to **Step 2** (`02-phase-2-drivers-assignment.md`).

To get to “totally” complete per the doc (full forms and Store/Update requests for every resource), you’d add:

- Store/Update form request classes for the other 9 resources.
- Full Create/Edit Inertia forms (all fields, dropdowns for FKs) for Cost Centers, Drivers, Trailers, Vehicles, Geofences, Garages, Fuel Stations, EV Charging Stations, Operator Licences.
- Optionally: `HasMedia`, `HasTags`, `HasCategories` on the Vehicle model when you’re ready to use them.
