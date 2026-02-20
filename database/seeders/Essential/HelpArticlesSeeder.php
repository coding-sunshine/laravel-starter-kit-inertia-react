<?php

declare(strict_types=1);

namespace Database\Seeders\Essential;

use App\Models\HelpArticle;
use Illuminate\Database\Seeder;

final class HelpArticlesSeeder extends Seeder
{
    public function run(): void
    {
        $articles = [
            [
                'title' => 'Getting Started with RRMCS',
                'slug' => 'getting-started-with-rrmcs',
                'category' => 'getting-started',
                'is_featured' => true,
                'excerpt' => 'A quick overview of the Railway Rake Management Control System and how it replaces your Excel/paper workflows.',
                'content' => <<<'MD'
## What you used to do

You managed rake operations across multiple Excel sheets — one for indent tracking, another for rake status with a manual stopwatch, a filing cabinet for Railway Receipts, and a penalty worksheet you only updated after the RR arrived (often 24+ hours late). Reconciliation meant hours of vlookup between sheets.

## How it works now

RRMCS brings every step into one connected system:

- **Dashboard** — live summary of rakes, penalties, alerts, and coal stock across all your sidings.
- **Indents** — digital demand register replacing the paper indent book.
- **Rakes** — real-time state tracking with automatic demurrage countdown (no more stopwatch).
- **Railway Receipts** — upload the RR PDF and the system parses FNR, freight, charges, and the wagon table automatically.
- **Penalties** — auto-calculated the moment free time expires, not days later.
- **Reconciliation** — one-click matching at five points (mine → siding → rake → weighment → RR → power plant).
- **Alerts** — proactive notifications at 60/30/0 minutes before demurrage starts.
- **Reports** — generate daily/monthly reports with one click instead of assembling them manually.

## Key tips

- Start on the **Dashboard** to see what needs attention right now.
- Use the **AI chat** (bottom-right) to ask questions like "how do I avoid demurrage?" — it knows your data.
- Hover over any underlined term (like FNR or MT) for a quick definition.
MD,
            ],
            [
                'title' => 'Rake Tracking: From Excel Stopwatch to Live Dashboard',
                'slug' => 'rake-tracking-from-excel-to-live-dashboard',
                'category' => 'rakes',
                'is_featured' => false,
                'excerpt' => 'How the Rakes page replaces your Excel stopwatch and manual status tracking.',
                'content' => <<<'MD'
## What you used to do

You tracked each rake in an Excel row — noting placement time, starting a phone stopwatch for the 3-hour loading window, and manually calculating whether demurrage would apply. If you missed the alarm, you discovered the penalty only when the Railway Receipt arrived days later.

## How it works now

The **Rakes** page shows every rake with its current state, loading window, and live demurrage countdown. Alerts fire automatically at 60 minutes (amber), 30 minutes (red), and 0 minutes (critical) before free time expires. Penalty amounts are calculated in real time.

## Key tips

- Click any rake row to see full detail: TXR, weighment slips, guard inspection, and penalty breakdown.
- The demurrage countdown updates automatically — no need to refresh.
- Overload detection happens during weighment, 24+ hours before the RR would have revealed it.
MD,
            ],
            [
                'title' => 'Managing Indents: Digital Demand Register',
                'slug' => 'managing-indents-digital-demand-register',
                'category' => 'indents',
                'is_featured' => false,
                'excerpt' => 'Replace your paper indent register with digital indent management.',
                'content' => <<<'MD'
## What you used to do

Indent requests were recorded in a physical register or an Excel sheet. The e-Demand reference number and FNR (Freight Note Reference) were copied by hand. Confirmation PDFs were printed and filed in folders, making it hard to search later.

## How it works now

The **Indents** page lets you create, track, and search all indent requests digitally. Each indent stores the siding, target quantity (MT), e-Demand reference, FNR, and state. You can upload the e-Demand confirmation PDF directly — no more paper filing.

## Key tips

- Use the **e-Demand ref** and **FNR** columns to quickly find an indent.
- Upload the confirmation PDF when creating or editing an indent — it's attached permanently.
- Filter by siding to see only your siding's indents.
MD,
            ],
            [
                'title' => 'Understanding Demurrage & Penalties',
                'slug' => 'understanding-demurrage-and-penalties',
                'category' => 'penalties',
                'is_featured' => false,
                'excerpt' => 'How demurrage is calculated and how to avoid penalties before they happen.',
                'content' => <<<'MD'
## What you used to do

Penalty amounts were calculated manually from the Railway Receipt after it arrived — often 24-48 hours after the rake was dispatched. You'd compare placement time vs dispatch time, subtract free hours, then multiply by weight and rate in a worksheet. Errors were common.

## How it works now

The **Penalties** page shows every penalty with a transparent **calculation breakdown**: hours over free time × weight (MT) × rate per MT per hour. The system calculates this automatically the moment free time expires. Alerts warn you at 60, 30, and 0 minutes remaining so you can take action *before* demurrage begins.

**Demurrage formula:** `(dwell hours − free hours) × weight (MT) × ₹ rate/MT/hour`

## Key tips

- Watch the **Rakes** page countdown — if a rake is approaching free time, prioritise completing its loading.
- Penalties show status (pending, incurred, disputed, waived) so you can track resolution.
- Click "View rake" on any penalty to see the full rake detail and timeline.
MD,
            ],
            [
                'title' => 'Railway Receipts: Paperless RR Filing',
                'slug' => 'railway-receipts-paperless-rr-filing',
                'category' => 'railway-receipts',
                'is_featured' => false,
                'excerpt' => 'Upload RR PDFs and let the system parse FNR, freight, charges, and wagon data automatically.',
                'content' => <<<'MD'
## What you used to do

Railway Receipt documents were stored in physical folders organised by siding and date. To find an RR, you'd dig through the cabinet. FNR numbers, freight charges, and wagon details were copied manually into Excel — a tedious and error-prone process.

## How it works now

The **Railway Receipts** page lets you upload the RR PDF. The system parses it automatically, extracting:

- **FNR** (Freight Note Reference) — the unique consignment ID.
- **Freight and charges** — broken down for transparency.
- **Wagon table** — structured list of wagon numbers, types, and weights.

All data is searchable and linked to the rake it belongs to.

## Key tips

- Upload the RR as soon as you receive it — it feeds into reconciliation and penalty calculations.
- Use the filter by siding to narrow your search.
- Click "View" to see the parsed details and attached PDF.
MD,
            ],
            [
                'title' => 'Road Dispatch: Vehicle Arrival & Unload',
                'slug' => 'road-dispatch-vehicle-arrival-and-unload',
                'category' => 'road-dispatch',
                'is_featured' => false,
                'excerpt' => 'Record vehicle arrivals and unloads digitally instead of maintaining a paper gate register.',
                'content' => <<<'MD'
## What you used to do

Vehicle arrivals were logged in a paper register at the gate. Tonnage was tallied manually. Unload confirmation was done by phone call or physical challan, often delayed by hours. There was no automatic link between road arrivals and siding stock.

## How it works now

- **Arrivals** — record each vehicle's arrival with driver and vehicle details. Net weight is captured digitally. The arrival log links directly to stock movement.
- **Unloads** — record and confirm unloads with weighment data. Confirming an unload updates siding stock automatically. Variance between mine weight and weighment weight is calculated instantly.

## Key tips

- Use the "Record arrival" button to log a new vehicle — it takes seconds.
- Confirm unloads promptly so stock levels stay accurate.
- Filter by siding and date to find specific arrivals quickly.
MD,
            ],
            [
                'title' => 'Reconciliation Without Vlookup',
                'slug' => 'reconciliation-without-vlookup',
                'category' => 'reconciliation',
                'is_featured' => false,
                'excerpt' => 'One-click five-point reconciliation replaces hours of Excel vlookup.',
                'content' => <<<'MD'
## What you used to do

Reconciliation meant opening multiple Excel sheets and running vlookup formulas to compare weights at different points: mine loading, siding weighment, Railway Receipt, and power plant receipt. It took hours and errors crept in easily. Variances were often discovered too late to act on.

## How it works now

The **Reconciliation** page performs five-point matching automatically:

1. **Mine → Siding** — compare loading records with siding weighment.
2. **Siding → Rake** — match siding data to rake records.
3. **Rake → Weighment** — compare rake data with in-motion weighbridge readings.
4. **Weighment → RR** — match weighment data against Railway Receipt figures.
5. **RR → Power Plant** — compare RR weight with power plant receipt.

Each rake shows its overall status (matched, minor difference, major difference) with colour coding.

## Key tips

- **Green** = matched, **Amber** = minor difference, **Red** = major difference.
- Click "Detail" to see the variance at each reconciliation point.
- Add power plant receipts via the dedicated button to complete the chain.
MD,
            ],
            [
                'title' => 'Reports: One-Click Generation vs Manual Assembly',
                'slug' => 'reports-one-click-generation-vs-manual-assembly',
                'category' => 'reports',
                'is_featured' => false,
                'excerpt' => 'Generate daily and monthly reports in seconds instead of assembling them manually.',
                'content' => <<<'MD'
## What you used to do

Daily and monthly reports were assembled by hand — copying data from multiple Excel sheets, formatting tables, and checking numbers. A single daily operations report could take 30-60 minutes. Monthly reports were an all-day affair.

## How it works now

The **Reports** page offers predefined report types:

- Siding coal receipt, rake indent, TXR, unfit wagon, wagon loading, weighment, loader vs weighment, rake movement, RR summary, and penalty register.

Select a report type, choose your siding and date range, and click generate. Export to CSV with one click.

## Key tips

- Use the **Penalty register** report to get a summary of all demurrage charges for a period.
- The **Loader vs weighment** report highlights overload issues.
- Export to CSV for further analysis or sharing with management.
MD,
            ],
            [
                'title' => 'Alerts & Notifications: Never Miss Demurrage Again',
                'slug' => 'alerts-and-notifications-never-miss-demurrage',
                'category' => 'alerts',
                'is_featured' => false,
                'excerpt' => 'Proactive alerts at 60/30/0 minutes replace discovering demurrage after the fact.',
                'content' => <<<'MD'
## What you used to do

There was no formal alert system. Demurrage was discovered only when the Railway Receipt arrived — typically 24+ hours after the rake was dispatched. By then, the penalty was already incurred and there was nothing you could do about it.

## How it works now

The **Alerts** page shows all active and resolved alerts. The system automatically creates alerts for:

- **60 minutes remaining** — amber warning, time to prioritise this rake.
- **30 minutes remaining** — red warning, escalation to in-charge.
- **0 minutes remaining** — critical alert, demurrage has started.

Alerts also cover overload detection and RR mismatch issues. Each alert links to the relevant rake or siding for quick action.

## Key tips

- Check the **Dashboard** for a quick count of active alerts.
- Resolve alerts as you address them to keep the list clean.
- Alerts escalate by role: operator → in-charge → management.
MD,
            ],
            [
                'title' => 'RRMCS Glossary: Terms You Need to Know',
                'slug' => 'rrmcs-glossary-terms-you-need-to-know',
                'category' => 'glossary',
                'is_featured' => true,
                'excerpt' => 'Quick reference for railway and coal logistics terminology used throughout the system.',
                'content' => <<<'MD'
## Common Terms

| Term | Full form | Meaning |
|------|-----------|---------|
| **FNR** | Freight Note Reference | Unique ID printed on the Railway Receipt identifying the consignment. |
| **RR** | Railway Receipt | Official document issued by railways confirming goods dispatched, with weights and charges. |
| **TXR** | Train Examination Report | Document confirming rake positioning and fitness for loading. |
| **MT** | Metric Tonnes | Standard unit of weight (1 MT = 1,000 kg). |
| **Demurrage** | — | Penalty charged when loading/unloading exceeds the allowed free time. |
| **Free time** | — | Hours allowed by railways to load/unload a rake before demurrage charges begin. |
| **e-Demand** | Electronic Demand | Railway electronic indent booking system for rake placement requests. |
| **IMWB** | In-Motion Weighbridge | Weighs wagons while the rake moves through, providing per-wagon weights. |
| **Indent** | — | Formal request to railways for a rake to be placed at a siding for loading. |
| **Rake** | — | A set of wagons coupled together as one train unit, typically 40–59 wagons. |
| **Wagon** | — | Individual rail car within a rake, identified by a unique wagon number and type code. |
| **Siding** | — | Private railway line connected to the main network, used for loading/unloading at a mine or plant. |
| **Weighment** | — | The act of weighing wagons or vehicles at a weighbridge to record gross, tare, and net weights. |
| **Overload** | — | When wagon weight exceeds the permissible carrying capacity, attracting railway penalties. |
| **Challan** | — | Transport document accompanying a vehicle shipment with quantity, origin, and destination details. |
| **Reconciliation** | — | Comparing weights at different points (mine, siding, RR, power plant) to detect variance or loss. |
| **Variance** | — | Difference in weight between two measurement points (e.g., siding weighment vs RR weight). |
| **Power Plant Receipt** | — | Weight record from the destination power plant, used as the final reconciliation point. |
| **Stock Ledger** | — | Running record of coal inventory at a siding, updated by arrivals, dispatches, and adjustments. |

## Demurrage Formula

**Demurrage = (dwell hours − free hours) × weight (MT) × rate (₹/MT/hour)**

Example: If a 3,000 MT rake exceeds free time by 2 hours at ₹0.50/MT/hour, the penalty is 2 × 3,000 × 0.50 = ₹3,000.
MD,
            ],
        ];

        foreach ($articles as $data) {
            HelpArticle::query()->updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'title' => $data['title'],
                    'excerpt' => $data['excerpt'],
                    'content' => $data['content'],
                    'category' => $data['category'],
                    'is_published' => true,
                    'is_featured' => $data['is_featured'],
                ]
            );
        }
    }
}
