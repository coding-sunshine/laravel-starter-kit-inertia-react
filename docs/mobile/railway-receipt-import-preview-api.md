# API: Railway Receipt import preview — mobile integration

**Endpoint:** `POST /api/v1/railway-receipts/import-preview`  
**Purpose:** Upload an RR **PDF** once, get a JSON **preview** (FNR, matched rake, siding metadata). **Nothing is saved** until the follow-up upload.

**Next step after success:** `POST /api/v1/railway-receipts/upload` with the **same PDF**, `rake_id` from the preview, plus optional `diverrt_destination_id` for diversion legs.

---

## Quick reference

| Item | Value |
|------|--------|
| **HTTP method** | **`POST`** only (GET/HEAD will return **405** if the URL is mistaken for `GET .../railway-receipts/{id}`) |
| **Path** | **`/api/v1/railway-receipts/import-preview`** |
| **Base URL** | `{BASE_URL}/api/v1/railway-receipts/import-preview` |
| **Authentication** | **`Authorization: Bearer {token}`** (Laravel Sanctum) |
| **Request encoding** | **`multipart/form-data`** |
| **Form field name** | **`pdf`** (exact name; file input) |
| **File rules** | Required, PDF MIME, max **10 MB** (10,240 KiB) |

> **URL pitfall:** Must include **`/api`** and **`/v1`**.  
> Example wrong: `POST https://app.test/railway-receipts/import-preview` → hits **web** routes or wrong resource; you may see **405** (“POST not supported”).  
> Correct: `POST https://app.test/api/v1/railway-receipts/import-preview`.

---

## Headers (recommended)

| Header | Required | Value / note |
|--------|----------|----------------|
| `Authorization` | Yes | `Bearer {access_token}` |
| `Accept` | Strongly recommended | `application/json` — JSON or problem+json responses |
| `Content-Type` | Automatic | Client sets `multipart/form-data` + boundary when attaching `pdf` |

No CSRF header/cookie is required for Bearer token API calls.

---

## Success: `200 OK`

**Content-Type:** `application/json`

Body is a **flat JSON object** (not wrapped in `{ "status", "message", "data" }`).

| Field | Type | Nullable | Description |
|-------|------|----------|-------------|
| `fnr_from_rr` | string | no | FNR read from the PDF (trimmed). |
| `fnr_from_indent` | string \| null | yes | Matched e-demand (`Indent`) FNR. |
| `to_station_code` | string \| null | yes | Parsed “To” station from PDF when available. |
| `rake_destination_code` | string \| null | yes | Rake destination code. |
| `rake_destination` | string \| null | yes | Rake destination label. |
| `siding_code` | string \| null | yes | Rake siding code. |
| `siding_name` | string \| null | yes | Rake siding name. |
| **`rake_id`** | **integer** | **no** | **Use this in** `POST .../upload`. |
| `rake_number` | string \| null | yes | Display. |
| `rake_serial_number` | string \| null | yes | Display. |

**Example**

```json
{
  "fnr_from_rr": "123456789012",
  "fnr_from_indent": "123456789012",
  "to_station_code": "BTPC",
  "rake_destination_code": "BTPC",
  "rake_destination": "Example Plant",
  "siding_code": "PKUR",
  "siding_name": "Pakur",
  "rake_id": 42,
  "rake_number": "7",
  "rake_serial_number": "SERIAL-A"
}
```

---

## Error responses — all scenarios

Use this matrix to implement UI and logging.

| HTTP | When | Typical body |
|------|------|----------------|
| **401** | Missing/invalid/expired Bearer token | **`application/problem+json`**: `errors[]` with `status`, `title`, `detail` (RFC7807-style — parse `detail` for user text) |
| **404** | User is authenticated but **API access** feature is **off** for them (`ApiAccessFeature`) | JSON or problem format; treat as “endpoint not available for this account” |
| **403** | User lacks **`sections.railway_receipts.upload`** | Framework JSON / message (no single stable shape) |
| **403** | Resolved rake’s **siding** not in user’s **accessible sidings** | Same — **do not** confuse with 401 |
| **422** | **`pdf`** missing, not a file, wrong type, or > 10 MB | Laravel validation: **`message`** + **`errors`** object (e.g. `errors.pdf[]`) |
| **422** | PDF parse / business rules below | **`{ "message": "<string>" }`** — show `message` to user |
| **429** | Too many requests (rate limit on `v1` group) | Often **`message`**; check **`Retry-After`** header if present |
| **500** | Unexpected server error inside preview | **`{ "message": "Failed to process Railway Receipt preview. Please ensure the PDF is valid and try again." }`** |
| **405** | Wrong HTTP method or wrong path so another route matches | “Method not supported” — fix URL to **`/api/v1/...`** and use **POST** |

---

## `422` — multipart validation (field `pdf`)

Rules from the server:

| Validation failure | User-facing message(s) |
|--------------------|-------------------------|
| Missing `pdf` | `A PDF file is required.` |
| Not a valid upload | `The PDF must be a valid file.` |
| Not PDF | `The file must be a PDF.` |
| Larger than 10 MB | `The PDF must not exceed 10 MB.` |

**Example shape (illustrative)**

```json
{
  "message": "The pdf field must be a file of type: pdf. (and 1 more error)",
  "errors": {
    "pdf": [
      "The file must be a PDF."
    ]
  }
}
```

Exact `message` wording can vary; rely on **`errors.pdf`** when present.

---

## `422` — business / parser (`{ "message": "..." }`)

After `pdf` passes multipart validation, the server parses the PDF and resolves the rake. Failures return **`422`** with a single **`message`** string.

| Scenario | `message` |
|----------|-----------|
| FNR empty after parsing | `FNR could not be read from this Railway Receipt PDF.` |
| No e-demand with this FNR | `No e-demand found for this RR FNR.` |
| E-demand exists, no linked rake | `No rake is linked to this e-demand.` |
| PDF text extraction failed (e.g. corrupt file / server tool) | `Could not extract text from PDF. Ensure the file is a valid PDF and pdftotext is installed.` |
| No extractable text | `PDF appears to be empty or could not extract any text.` |
| RR structure not recognized | `This does not appear to be a Railway Receipt PDF. RR number could not be found.` **or** `This does not appear to be a Railway Receipt PDF.` |
| **Non-diverted** rake already has an RR document | `Railway Receipt has already been uploaded for this rake.` |
| **Diverted** rake: **primary** RR already exists (`diverrt_destination_id` null) | `The primary Railway Receipt for this rake has already been uploaded.` |

**Diversion note:** Preview never sends `diverrt_destination_id`. It only validates the **default / primary** slot match to import **without** a diversion id. Per-leg diversion conflicts are enforced on **`POST /api/v1/railway-receipts/upload`** when you pass `diverrt_destination_id`.

---

## `401` — reference

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

Mobile apps should read **`errors[0].detail`** (fallback: title) for alerts.

---

## `500` — server error

Fixed response body:

```json
{
  "message": "Failed to process Railway Receipt preview. Please ensure the PDF is valid and try again."
}
```

---

## Rate limiting

- **60 requests per minute** per throttle key for the `api/v1` group (Laravel `throttle:60,1`).
- On **429**, back off and optionally respect **`Retry-After`**.

---

## Processing order (mental model)

1. Throttle  
2. Authenticate (**401** if not)  
3. **API access** feature (**404** if disabled)  
4. Validate **`pdf`** multipart (**422** with `errors`)  
5. Permission **`sections.railway_receipts.upload`** (**403**)  
6. Parse PDF + resolve FNR → indent → rake (**422** `message`)  
7. Siding access for user (**403**)  
8. Slot free for default/primary RR (**422** `message` if taken)  
9. Return **200** JSON preview  

---

## Example: cURL

Replace `TOKEN` and host.

```bash
curl -sS -X POST "https://YOUR-HOST/api/v1/railway-receipts/import-preview" \
  -H "Authorization: Bearer TOKEN" \
  -H "Accept: application/json" \
  -F "pdf=@/path/to/rr.pdf"
```

---

## Example: Kotlin / OkHttp (conceptual)

- Use `multipart/form-data`.
- Part name must be **`pdf`**.
- Attach file with `application/pdf` content type when possible.

---

## After preview succeeds

Call **`POST /api/v1/railway-receipts/upload`** with:

- `pdf` — same file  
- `rake_id` — from **`rake_id`** in preview  
- optional `siding_id`, `power_plant_id`, `diverrt_destination_id` per product rules  

Separate mobile doc for upload can be added later; see `StoreApiRailwayReceiptUploadRequest` in the codebase.

---

## Canonical copy for developers

An equivalent reference also lives at:  
`docs/developer/api-reference/railway-receipt-import-preview-api.md`

---

_Document version aligned with backend controllers and actions in this repository._
