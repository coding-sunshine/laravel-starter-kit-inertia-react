# Legacy database dump – what was seeded and what remains

## What was seeded (via `FleetFullSeeder::seedFromLegacyDump()`)

Data from your old database dump (`dump.txt`) was mapped into the current app and is created **only through the seeder** (no raw SQL). All of it is created in the same organization (Fleet Demo / first org).

| Legacy data | How it was used in the app |
|-------------|----------------------------|
| **Companies** | Turned into **Locations** (type `depot`): Caldicot, Commercial Motors - Man, Coryton, Cruselys, Ebor Trucks, GCA, ICL - Hebburn, ICL - West thurrock, ICL - Widnes, Industrial Chemicals, Lynch Transport, MAN - Gateshead, Pelican, Pullman - Ellesmere Port, Purfleet Commercials, Rema Tip Top, Renault - West Thurrock, S&B Commercials (18 locations). |
| **Vehicle types** | Make/model and type: MAN TGX, MAN Rigid TGM, MAN T480, Mercedes Sprinter, DAF Rigid → used for **Vehicles** (truck/van, diesel). |
| **Vehicles** | 40 vehicles with **registrations** from the dump (EU15ZPR, EU15ZPT, EX68BXG, … through LX70MLY). Status “Roadworthy” → `active`, “Archived” → `disposed`. Odometer and MOT expiry taken where present. Live tracking positions are backfilled so they show on the dashboard map. |
| **Defects** | A set of **Defects** with original defect numbers and dates: 9 defects tied to legacy vehicles 1 and 22 (EU15ZPR and EY66MZP), plus 15 extra defects across the first 15 legacy vehicles so defect lists look full. Status “Resolved” and report/resolution dates preserved where available. |
| **Service schedules** | **Service schedules** (MOT, annual service, next service, PMI) created for the first 25 legacy vehicles so maintenance/compliance views have data. |

So after running the seeder, the user sees: many more vehicles and locations (with real-looking names from the old app), defects with real defect numbers and dates, and service schedules – all without running the raw dump SQL.

---

## What data from the dump was **not** imported (and why)

### 1. **Users / roles / permissions**

- **Dump tables:** `users`, `roles`, `permission_role`, `role_user`, `permissions`, `group_users`, `groups`
- **Why not:** The current app has its own auth (Laravel + Pennant + organization membership). User IDs and roles in the dump do not match. Importing would require a full user/role migration and password handling.
- **What you’d need:** A dedicated “legacy user import” that creates users (with safe default passwords or invite flow), maps old roles to current permissions/roles, and attaches users to the right organization(s).

### 2. **Vehicle assignment (driver ↔ vehicle)**

- **Dump table:** `vehicle_assignment` (vehicle_id, user/division/region, from_date, to_date)
- **Why not:** The app has **Driver** (fleet driver) and **DriverVehicleAssignment**, not “user_id on vehicle”. The dump’s “nominated_driver” and assignment table are user-based.
- **What you’d need:** Either (a) create **Driver** records from legacy users and then create **DriverVehicleAssignment** from `vehicle_assignment`, or (b) add a feature “link user to driver” and map assignments accordingly.

### 3. **Checks / defect_master / asset checks**

- **Dump tables:** `checks`, `asset_checks`, `asset_check_structure_master`, `defect_master`, `asset_question_pages`, etc.
- **Why not:** The current app has **VehicleCheck** and **VehicleCheckTemplate**, but no equivalent to “check structure”, “question pages”, or “defect_master” (predefined defect types). Defects in the app are standalone records, not tied to a check or defect_master id.
- **What you’d need:** Feature “predefined defect library” (like defect_master) and/or “check templates with question flow” plus an import that creates templates and defect library from the dump.

### 4. **Assets (trailers) and asset-specific data**

- **Dump tables:** `assets`, `asset_categories`, `asset_defects`, `asset_maintenance_histories`, `asset_assignment`, `asset_profiles`, etc.
- **Why not:** The app has **Trailer** and vehicle-centric flows. The dump has a full “asset” (trailer) model with categories, profiles, check structures, and asset-specific maintenance/defects.
- **What you’d need:** Either (a) extend **Trailer** (and related) to mirror asset fields and then map `assets` → Trailers + asset_defects → Defects (with entity_type trailer), or (b) add a full “Asset” module (categories, profiles, assignments) and then import.

### 5. **Telematics / journeys**

- **Dump tables:** `telematics_journeys`, `telematics_journey_details`, `telematics_data`, `trakm8_*`, `user_telematics_journeys`, etc.
- **Why not:** The app has **Trip** and **TripWaypoint**, but no 1:1 mapping to the dump’s journey/detail/telematics schema (different providers and fields).
- **What you’d need:** A “telematics import” that maps legacy journey/detail rows into **Trip** and **TripWaypoint** (and optionally raw telematics if you add a matching table).

### 6. **Vehicle maintenance history (exact rows)**

- **Dump table:** `vehicle_maintenance_history` (vehicle_id, event type, due date, completion, etc.)
- **Why not:** We only created **ServiceSchedule** (recurring) and did not create one-off **WorkOrder** or “maintenance history” records for each row in the dump.
- **What you’d need:** Either (a) map each completed row to a **WorkOrder** (or a dedicated maintenance history table if you add one), or (b) a “maintenance history” feature + import job.

### 7. **Locations (dump)**

- **Dump table:** `locations` (address1, address2, town_city, postcode, lat/long)
- **Why not:** The dump’s `locations` table had **no data** (empty INSERT). We used **companies** as location names instead.
- **What you’d need:** If you get a dump with `locations` populated, add an import that creates **Location** from those rows (with address, city, postcode, lat/lng).

### 8. **Reports / report downloads / column management**

- **Dump tables:** `reports`, `report_downloads`, `report_dataset`, `report_columns`, `column_management`, etc.
- **Why not:** The app has **Report** and **ReportExecution** with a different structure. Column and dataset definitions don’t match.
- **What you’d need:** A “report definition import” that maps old report/dataset/column config into the current Report (and related) model, or a one-off script per report type.

### 9. **Alerts / notifications (old schema)**

- **Dump tables:** `alerts`, `alert_notifications`, `alert_notification_days`, `user_notifications`
- **Why not:** The app has **Alert** (fleet alerts) but a different shape and no “alert notification day/slot” or user_notifications table.
- **What you’d need:** Extend Alert (and notification preferences) to support the old structure, or an import that only maps what fits (e.g. alert type + severity + created_at).

### 10. **Incidents / messages / P11D / surveys / templates**

- **Dump tables:** `incidents`, `incident_history`, `messages`, `message_recipients`, `p11d_report`, `survey_master`, `templates`, `template_users`, etc.
- **Why not:** The app has **Incident** (and related) but not the same fields or history table; no message/P11D/survey/template tables.
- **What you’d need:** Add matching features (or tables) and then an import for each (e.g. incident history, messaging, P11D, surveys, template assignments).

### 11. **Zone / geofence**

- **Dump tables:** `zones`, `zone_vehicle`, `zone_alerts`
- **Why not:** The app has **Geofence** but no “zone_vehicle” or “zone_alerts” in the same form.
- **What you’d need:** Add zone–vehicle and zone–alert linking (if desired) and import from dump.

### 12. **Misc (settings, migrations, jobs, media, etc.)**

- **Dump tables:** `settings`, `migrations`, `jobs`, `failed_jobs`, `media`, `notifications`, `password_resets`, etc.
- **Why not:** App-specific or framework tables; not fleet domain data.
- **What you’d need:** Generally not worth importing; only if you have a concrete need (e.g. copy specific `settings` keys into config or DB).

---

## Summary

- **Seeded via seeder only:** Locations (from companies), 40 vehicles (from dump registrations + types), defects (with real numbers/dates where possible), service schedules, and live positions for the map.
- **Not imported:** Users/roles, vehicle assignment (until driver mapping exists), checks/defect_master/assets, full telematics journey rows, exact vehicle_maintenance_history rows, reports/columns, alerts/notifications (old schema), incidents/messages/P11D/surveys/templates, zones, and app/framework tables.

To bring over more of the dump you need either (a) new/updated features (e.g. defect library, asset module, report builder, notification slots) and then import scripts, or (b) one-off import jobs that map old tables into existing models where the schema is close enough.
