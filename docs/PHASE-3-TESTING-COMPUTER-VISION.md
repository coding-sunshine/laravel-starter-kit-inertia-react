# Phase 3: Testing the complete computer vision flow

This guide walks you through testing the full Phase 3 flow: **damage/claims analysis** on Defects, Incidents, and Insurance Claims (photo upload → AI analysis → results on show page).

---

## 1. Prerequisites

### 1.1 Environment

- **`.env`** must have a vision-capable AI provider configured. The app uses **OpenRouter** for images:
  - `OPENROUTER_API_KEY=` set to a valid key.
  - OpenRouter supports vision models (e.g. `openai/gpt-4o`, `openai/gpt-4o-mini`). Ensure your account/model supports image input.

### 1.2 Queue worker (required)

Damage analysis runs in a **queued job**. If no worker is running, the job stays queued and results never appear.

In a **separate terminal**, run:

```bash
cd /path/to/laravel-starter-kit-inertia-react
php artisan queue:work
```

Leave it running while testing. Use `QUEUE_CONNECTION=sync` in `.env` only for quick local debugging (jobs run in the same request; no worker needed).

### 1.3 Fleet data

You need at least:

- **Organization** (your user’s default org).
- **Vehicle(s)** and **Driver(s)** (for Defect/Incident).
- **Insurance policy** (for Insurance Claim).

If you use `FleetFullSeeder`, ensure it has been run so vehicles, drivers, and policies exist.

---

## 2. Test flow: Defect

1. **Log in** and go to **Fleet → Defects**.

2. **Create a defect with a photo**
   - Click **Create** (or go to `/fleet/defects/create`).
   - Fill required fields (vehicle, defect number, title, etc.).
   - In **Photos**, attach **at least one image** (e.g. a vehicle/damage photo).
   - Submit. You are redirected to the defects list.

3. **Open the defect**
   - Open the defect you just created (Defects list → click the defect).

4. **Trigger analysis (if not auto-run)**
   - On create with photos, the app already dispatches the job. If the queue worker is running, wait a few seconds and **refresh** the defect show page.
   - Or click **“Analyze with AI”** in the Photos section to queue analysis again. Then refresh after a few seconds.

5. **Check the result**
   - An **“AI damage analysis”** card should appear with:
     - Summary (primary finding)
     - Severity (cosmetic / functional / safety_critical)
     - Parts affected, cost range, confidence
   - Defect **description** and **severity** may be auto-filled if they were empty.

---

## 3. Test flow: Incident

1. Go to **Fleet → Incidents**.

2. **Create an incident with a photo**
   - Create a new incident and attach at least one **image** in the photos/documents upload.
   - Submit.

3. **Open the incident**
   - Open the incident from the list.

4. **Trigger or wait for analysis**
   - As with defects, analysis is queued on create when photos are present. With the queue worker running, refresh after a few seconds.
   - Or use **“Analyze with AI”** (shown when there is at least one image in media), then refresh.

5. **Check the result**
   - **“AI damage analysis”** card appears with summary, severity, parts affected, cost range.

---

## 4. Test flow: Insurance Claim

1. Go to **Fleet → Insurance Claims**.

2. **Create a claim with a photo**
   - Create a new claim (incident + policy + claim number, etc.).
   - In **Photos (optional, for AI damage analysis)**, attach at least one image.
   - Submit.

3. **Open the claim**
   - Open the claim from the list.

4. **Trigger or wait for analysis**
   - Analysis is queued on create when photos are present. Refresh after the worker runs.
   - Or click **“Analyze with AI”** in the Photos section, then refresh.

5. **Check the result**
   - **“AI damage / claims analysis”** card appears (same structure: summary, severity, parts, cost range). Stored as `analysis_type` = `claims_processing`, `entity_type` = `insurance_claim`.

---

## 5. Verify in the database (optional)

- Table: `ai_analysis_results`.
- For defects: `entity_type = 'defect'`, `analysis_type = 'damage_detection'`.
- For incidents: `entity_type = 'incident'`, `analysis_type = 'damage_detection'`.
- For claims: `entity_type = 'insurance_claim'`, `analysis_type = 'claims_processing'`.

You can also use **Fleet → AI Analysis Results** in the app to see recent analyses.

---

## 6. Troubleshooting

| Symptom | What to check |
|--------|----------------|
| **“Analyze with AI” does nothing / no result** | Queue worker must be running: `php artisan queue:work`. Check `storage/logs/laravel.log` for job errors. |
| **No photo to analyze (422)** | The entity has no media in the `photos` collection. Add a photo on create or edit, then run analysis. |
| **Job fails (vision / API error)** | Ensure `OPENROUTER_API_KEY` is set and the model used for images supports vision (e.g. GPT-4o). Check logs for the exact error. |
| **Analysis card never appears** | After clicking “Analyze with AI”, wait 5–10 seconds and **refresh** the show page. The endpoint returns immediately and the job runs in the background. |
| **Create/Edit with photos fails** | Ensure form uses `forceFormData: true` and (for Edit) `_method: 'PUT'` so files are sent correctly. |

---

## 7. Quick one-liner (with queue worker)

1. Terminal 1: `php artisan queue:work`
2. Browser: create a Defect with one photo → open it → wait a few seconds → refresh → confirm “AI damage analysis” card.

Repeat for Incident and Insurance Claim to cover the full Phase 3 flow.
