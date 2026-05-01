# Railway Bills calibration corpus

JSON fixtures derived from real RR documents (PDF redacted of PII). Each file
captures one rake's billing snapshot plus the operational facts needed to
reproduce a prediction. The calibration test asserts predicted-vs-billed
within ±10% across the corpus.

## File naming

`YYYY-MM-DD-<siding-slug>-<head>-<seq>.json`

## Schema

```json
{
  "siding_name": "Dumka Siding",
  "rake_number": "DMK-2026-04-001",
  "commodity_grade": "G2",
  "wagon_count": 58,
  "placement_time": "2026-04-01T08:00:00+05:30",
  "loading_end_time": "2026-04-01T16:00:00+05:30",
  "wagons": [
    { "wagon_number": "BOXNHL-12345", "cc_capacity_mt": 70.0, "net_weight_mt": 66.5 }
  ],
  "billed": {
    "DEM": 14150,
    "PLO": 0,
    "POL1": 0,
    "POLA": 0,
    "ENHC": 0
  },
  "source_rr_document": "redacted-2026-04-01-dmk-001.pdf",
  "notes": "Free-time 180 min; load took 8h; tier 1× rate.",
  "synthetic": false
}
```

If a sample is synthetic (numbers approximated, not from a real RR), set `"synthetic": true`. The calibration test will fail if all corpus samples are synthetic.

## How to add a sample

1. Pull a real RR document from the `rr_documents` table (or a redacted PDF).
2. Read off the placement, loading-end, wagon counts, weights, and billed amounts per head.
3. Save as a new file matching the schema above.
4. Run `composer test:calibration` to confirm predicted is within ±10% of billed for every head present.

## Why ±10%?

Tighter band invites flakes from rounding inside the railway billing system.
Looser band lets real systematic bias hide. ±10% is the working threshold —
revisit per umbrella spec §3 once the corpus has ≥30 samples.
