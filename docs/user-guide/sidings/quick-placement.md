# Quick Placement (siding attendants)

If you work at a siding — most importantly **Pakur** — and your role is **siding_in_charge** or **siding_operator**, you can quickly stamp the time rakes are placed and released without filling out a long form.

## Why this matters

Placement and release times drive the **demurrage clock** at the siding. At Pakur these times were not previously captured, leaving a gap of around **7,758 historical rakes** with no placement timestamp — and demurrage we may have absorbed without knowing. Tapping the buttons here closes that gap going forward.

## Workflow

1. From the main menu, open **Sidings → your siding → Quick Placement**.
   The URL is `/sidings/{siding}/quick-placement`.
2. The screen shows the **50 most recent active rakes** for your siding.
3. When a rake physically arrives at the siding, tap **Placed** on its row. The server stamps the placement time automatically — you do not have to type it.
4. When loading is finished and the rake is released, tap **Released** on the same row. The server stamps the release time.
5. Each row shows the current state (no time / placed / released) so you can confirm the action took.

That's the whole flow — two taps per rake.

## Tips

- The list refreshes after each tap; if you don't see your rake, scroll or check that you're on the right siding.
- If you tap **Placed** by mistake, ask an admin — operators cannot un-stamp times themselves (this is by design to keep the audit trail clean).
- Use this on the phone you already carry around the yard. The page is designed to be touch-friendly.

## Related

- **Historical backfill (admins only):** rakes that arrived before this feature can be back-filled from a CSV with
  `php artisan pakur:backfill-placement --file=<csv>`. The CSV must contain rake identifiers and the historical placement/release timestamps. Operators do not need to run this command.
