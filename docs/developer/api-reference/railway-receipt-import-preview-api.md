# Railway Receipt import preview API (mobile)

> **Mobile handoff copy:** [`docs/mobile/railway-receipt-import-preview-api.md`](../../mobile/railway-receipt-import-preview-api.md) — same specification, expanded for integration teams.

This document describes **`POST /api/v1/railway-receipts/import-preview`** for mobile and other API clients.

It parses an uploaded RR **PDF**, reads **FNR** from the document, matches it to an **e-demand (Indent)** and **Rake**, checks that the authenticated user may access that rake’s **siding** and that the **default RR upload slot** is still available, then returns a **preview** payload.

**This endpoint does not create or update any RR document.** After a successful preview, commit the upload with **`POST /api/v1/railway-receipts/upload`** (same PDF again, plus `rake_id`, and optionally `diverrt_destination_id` for diversion legs).

---

## Endpoint

| Item | Value |
|------|--------|
| **Method** | `POST` |
| **Path** | `/api/v1/railway-receipts/import-preview` |
| **Full URL** | `{BASE_URL}/api/v1/railway-receipts/import-preview`<br>e.g. `https://your-app.test/api/v1/railway-receipts/import-preview` |
| **Auth** | Required: **Bearer token** (Laravel Sanctum personal access token) |
| **Content type** | `multipart/form-data` |
| **Body field** | `pdf` — the RR file (**required**); must be a PDF ≤ **10 MB** (10,240 KB) |

> **Important:** The absolute path **must** include the `/api` prefix and `/v1` version segment as shown above. Requests to `{BASE_URL}/railway-receipts/import-preview` (without `/api/v1`) are **not** this route.

---

## Recommended request headers

| Header | Required | Purpose |
|--------|----------|---------|
| `Authorization` | Yes | `Bearer {token}` |
| `Accept` | Strongly recommended | `application/json` — ensures JSON / problem+json responses (aligned with Laravel’s `expectsJson()` behavior) |
| `Content-Type` | Set by client | `multipart/form-data` with boundary (typical multipart upload) |

No CSRF cookie is needed for Bearer-only API calls.

---

## Middleware and rate limiting

Executed in approximately this order:

1. Global **API middleware** (`bootstrap/app.php` API stack — includes tenant context middleware where applicable).
2. **`throttle:60,1`** — applied to all `routes/api.php` `v1` routes: **maximum 60 requests per minute per key** (per IP/token behavior depends on Laravel throttle configuration). Failure → **`429 Too Many Requests`**.
3. **`auth:sanctum`** — user must be authenticated. Failure → **`401 Unauthorized`** (see below).
4. **`feature:api_access`** — for the authenticated user, the **`ApiAccessFeature`** Pennant flag must be **active**. Failure → **`404 Not Found`** (see below).
5. **`StoreApiRailwayReceiptImportPreviewRequest` validation** for `pdf` — failure → **`422 Unprocessable Entity`** with Laravel validation shape (see below).
6. **Application permission:** user must have **`sections.railway_receipts.upload`**. Failure → **`403 Forbidden`**.
7. **Preview logic** (`PreviewRailwayReceiptImport`) — may return **`422`** (`message` string) or **`403`** (siding not allowed).

---

## Success response

**HTTP status:** **`200 OK`**

**Content-Type:** `application/json`

**Body (JSON object):**

| Field | Type | Nullable | Description |
|-------|------|----------|-------------|
| `fnr_from_rr` | string | no | Normalized FNR read from the PDF. |
| `fnr_from_indent` | string \| null | yes | `fnr_number` on the matched Indent (e-demand). Often matches `fnr_from_rr` when aligned. |
| `to_station_code` | string \| null | yes | Parsed “To” station code from the PDF (if extractor found one). |
| `rake_destination_code` | string \| null | yes | Destination code stored on the resolved **Rake**. |
| `rake_destination` | string \| null | yes | Destination name / label on the **Rake**. |
| `siding_code` | string \| null | yes | Matched rake’s siding code. |
| `siding_name` | string \| null | yes | Matched rake’s siding name. |
| `rake_id` | integer | no | Resolved rake primary key — **pass this** to **`POST /api/v1/railway-receipts/upload`** along with the same PDF. |
| `rake_number` | string \| null | yes | Display rake number. |
| `rake_serial_number` | string \| null | yes | Display serial number. |

Example (trimmed illustration only):

```json
{
  "fnr_from_rr": "123456789012",
  "fnr_from_indent": "123456789012",
  "to_station_code": "BTPC",
  "rake_destination_code": "BTPC",
  "rake_destination": "Example Destination",
  "siding_code": "PKUR",
  "siding_name": "Pakur",
  "rake_id": 42,
  "rake_number": "7",
  "rake_serial_number": "SERIAL-A"
}
```

---

## Error responses overview

| Status | Typical cause |
|--------|----------------|
| **401** | Missing or invalid Bearer token |
| **404** | API access feature disabled for the user |
| **403** | Missing `sections.railway_receipts.upload`; or rake’s siding not in user’s accessible sidings |
| **422** | Invalid `multipart`/`pdf`; or PDF parse / business rules (see tables below) |
| **429** | Rate limit exceeded |
| **500** | Unexpected server error during processing |

Responses are JSON except where noted (**401** may use **`application/problem+json`** per app tests).

---

## 401 Unauthorized

Occurs when **`auth:sanctum`** does not authenticate the request (no token, revoked token, wrong guard, etc.).

**Observed behavior in automated tests:**

- **`Content-Type`:** `application/problem+json`
- **Body:** RFC 7807 problem details structure, roughly:

```json
{
  "errors": [
    {
      "status": "401",
      "title": "...",
      "detail": "..."
    }
  ]
}
```

Implementations should rely on **`status` / `detail`** semantics rather than a single `message` field for **401**.

---

## 404 Not Found (API disabled)

Occurs when **`feature:api_access`** runs **after** authentication and the **`ApiAccessFeature`** is **inactive** for that user (Pennant; also respects globally disabled modules in `config/feature-flags.php`).

**Semantics:** intentionally **404** — the route behaves as though it does not exist for API-disabled users.

**Body:** Depends on Laravel’s JSON exception rendering — often a simple **`message`** or problem JSON.

---

## 403 Forbidden

### A) Missing RR upload permission

After auth, **`RailwayReceiptImportPreviewController`** enforces **`sections.railway_receipts.upload`** (with organization-aware checks when tenancy context applies, same pattern as other RR APIs).

Super-admins may satisfy this via **`bypass-permissions`**.

### B) Siding access

If the resolved **Rake** has a **`siding_id`** that is **not** in the user’s allowed siding set (**`accessibleSidings()`**, or every siding for super-admins), the application aborts **`403`** from the preview Action.

### Response shape

Depends on Laravel’s handler for aborted HTTP exceptions. Clients should treat any **`403`** as “cannot preview for this rake with this identity,” not retry with the same credentials without changing assignments/permissions.

---

## 422 Unprocessable Entity

Two families: **multipart validation** vs **parsed PDF / domain rules**.

### 1) Multipart validation (`pdf` field)

Validated by **`StoreApiRailwayReceiptImportPreviewRequest`**:

| Rule | Typical `message` (user-facing strings from the app) |
|------|-------------------------------------------------------|
| `pdf` missing | `"A PDF file is required."` |
| invalid upload | `"The PDF must be a valid file."` |
| not PDF mime | `"The file must be a PDF."` |
| file too large (> 10 MB) | `"The PDF must not exceed 10 MB."` |

**Laravel default JSON shape** often includes **`message`** summarizing failures and **`errors`** keyed by field (`errors.pdf`).

### 2) Domain / parsing errors (`InvalidArgumentException`)

Caught in **`RailwayReceiptImportPreviewController`** and returned as:

```json
{ "message": "<exact English string from backend>" }
```

Known **`message`** values from current code paths:

| Scenario | Typical `message` |
|----------|---------------------|
| FNR blank after trimming | `"FNR could not be read from this Railway Receipt PDF."` |
| No Indent with matching `fnr_number` | `"No e-demand found for this RR FNR."` |
| Indent exists but **`rake`** is null | `"No rake is linked to this e-demand."` |
| PDF text extraction failed | `"Could not extract text from PDF. Ensure the file is a valid PDF and pdftotext is installed."` |
| Extracted PDF text empty | `"PDF appears to be empty or could not extract any text."` |
| Document does not look like an RR PDF | `"This does not appear to be a Railway Receipt PDF. RR number could not be found."` or `"This does not appear to be a Railway Receipt PDF."` |
| **Non-diverted** rake already has **any** `rr_documents` row for `rake_id` | `"Railway Receipt has already been uploaded for this rake."` |
| **Diverted** rake: primary RR already exists (**`diverrt_destination_id` IS NULL**) | `"The primary Railway Receipt for this rake has already been uploaded."` |

> **Preview vs diversion legs:** Preview always runs **without** a `diverrt_destination_id`. It only enforces the **default / primary slot** rules (mirror of upload without diversion). Checking “this diversion leg slot is taken” stays on **`POST /api/v1/railway-receipts/upload`** when the client sends `diverrt_destination_id`.

Other **`InvalidArgumentException`** messages might be added upstream in parsing or domain code; mobile clients should always **display `message`** to the user and **log the full HTTP body** when debugging.

---

## 429 Too Many Requests

From **`throttle:60,1`** on the `v1` route group — too many attempts in the window.

**Body:** Depends on Laravel / framework defaults (often **`message`** and optional **`Retry-After`** header).

---

## 500 Internal Server Error

**When:** Unexpected **`Throwable`** after validation (not `InvalidArgumentException`).

**Returned JSON:**

```json
{
  "message": "Failed to process Railway Receipt preview. Please ensure the PDF is valid and try again."
}
```

Server logs will contain detailed exception data; **`report($e)`** is invoked.

---

## Client flow checklist (mobile)

1. Obtain Sanctum **Bearer** token (`POST /api/v1/auth/login` or issued PAT).
2. Ensure **`ApiAccessFeature`** is **on** for that user — otherwise **`404`**.
3. **`POST`** `multipart/form-data` with **`pdf`** to **`/api/v1/railway-receipts/import-preview`** + **`Authorization: Bearer ...`** + **`Accept: application/json`**.
4. On **`200`**, persist **`rake_id`** and optionally show **`fnr_from_rr`**, siding, rake number.
5. On confirm, **`POST`** **`/api/v1/railway-receipts/upload`** with **`pdf`** + **`rake_id`** (+ **`diverrt_destination_id`** for diversion uploads per product rules).

---

## Related code (maintainers)

- Route name: **`api.v1.railway-receipts.import-preview`**
- Files: [`routes/api.php`](../../../routes/api.php), [`RailwayReceiptImportPreviewController`](../../../app/Http/Controllers/Api/V1/RailwayReceiptImportPreviewController.php), [`StoreApiRailwayReceiptImportPreviewRequest`](../../../app/Http/Requests/Api/StoreApiRailwayReceiptImportPreviewRequest.php), [`PreviewRailwayReceiptImport`](../../../app/Actions/PreviewRailwayReceiptImport.php), [`ResolveRakeForRrImportPreview`](../../../app/Actions/ResolveRakeForRrImportPreview.php), [`RrParserService`](../../../app/Services/Railway/RrParserService.php), [`RrImportService`](../../../app/Services/Railway/RrImportService.php)

---

_Last updated to match backend behavior at documentation authoring time._
