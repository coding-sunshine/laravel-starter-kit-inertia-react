# PROPOSAL FOR ADDITIONAL WORK
## Railway Rake Management Control System
### AI Camera-Based Rake Identification Module
### (Add-on to Existing System)

**Date:** 1 May 2026
**Reference SOW:** Scope of Work — Railway Rake Management Control System (Pakur / Dumka / Kurwa sidings)
**Currency:** INR (₹). All charges are exclusive of GST.

---

## 1. PROJECT OVERVIEW

The existing Railway Rake Management Control System covers the full coal movement workflow — Mine → Road Dispatch → Siding → Rake Loading → In-motion Weighment → RR → Power Plant → Reconciliation (modules 4.1 to 4.13 of the original SOW).

This proposal adds AI cameras at the sidings. The cameras read the painted serial numbers on each wagon as the rake passes, and the system matches those numbers to the expected rake roster. This removes manual rake-chartering, speeds up loading clearance, and gives a clear photo record of every wagon.

The add-on plugs into the existing Rake, Wagon, and Loading data. It does **not** replace any existing module.

The customer picks one of two setups (priced in Section 9):

- **Configuration A — One Camera per Siding** (3 cameras total)
- **Configuration B — Two Cameras per Siding** (6 cameras total)

Configuration B reads both sides of every wagon. If one side is dirty or glared, the other side still gives a clean reading. It costs more in software, hardware, and installation.

---

## 2. KEY OBJECTIVES

- Remove manual rake-chartering at the siding (today: 30–60 minutes per rake)
- Push wagon-roster accuracy from ~92–95% (manual) to ≥98% (Config A) or ≥99% (Config B)
- Keep a photo of every wagon for disputes with railway officials
- Reduce siding-staff workload during rake placement
- Set up the base for future Stage-2 features (visual load checking and pre-departure penalty alerts — quoted separately)

**Note:** This proposal alone does **not** prevent loading penalties. Penalty prevention needs the Stage-2 features (load estimation + pre-departure alerts). Those are out of scope here and listed in Section 7.

---

## 3. USER ROLES & ACCESS CONTROL

No new role is added. The existing roles use the AI camera output:

- **Siding Operator** — reviews the auto-detected wagon list, confirms or fixes flagged wagons.
- **Siding In-Charge** — sees the full camera-based rake report.
- **Management** — sees a dashboard with detection accuracy and exceptions.
- **System Admin** — manages camera logins, model thresholds, and per-siding on/off toggles.

Role-based access works the same as in the existing system.

---

## 4. FUNCTIONAL SCOPE (MODULE-WISE)

Module numbers continue from the existing SOW (which ended at 4.13).

---

### 4.14 AI CAMERA INTEGRATION & VIDEO INGESTION MODULE

**Purpose:** Connect the customer's IP cameras at the siding so every rake is recorded automatically.

**In simple terms:** Today, when a rake arrives, the siding team walks along it and checks each wagon number against the indent. This module lets the system do the watching. The customer installs one or two cameras at the siding (entry only for Config A; entry + exit for Config B). After the System Admin registers the camera (address and login), the system starts recording on its own when a rake is placed and stops when loading finishes. No one starts or stops it by hand. If a camera goes offline — power cut, internet down, dirty lens — the System Admin gets an alert in time to fix it before the next rake.

**Developer Scope:**
- Camera onboarding screen (RTSP URL, login, siding mapping, role: entry / exit)
- Continuous video pull from one **or two** IP cameras per siding
- Frame extraction at a set rate (default 5 frames/second)
- Per-rake clip auto-generated on Rake Placement (existing 4.4)
- Capture stops on Loading Completion (existing 4.9)
- Secure storage of frame snapshots and clip metadata
- Camera-offline alert to the System Admin
- Config B only: time-sync between the two cameras at the same siding

**System Logic:**
- Each siding has **one or two** registered cameras, depending on the chosen configuration
- All captured data is organisation-scoped (multi-tenant safe)

**Validations:**
- AI cannot be enabled for a siding until all contracted cameras are registered and reachable
- The same camera cannot be assigned to two sidings at the same time

---

### 4.15 AI-BASED WAGON IDENTIFICATION & ROSTER MATCHING MODULE

**Purpose:** Read the painted serial number on each wagon and compare it with the expected rake roster.

**In simple terms:** As the rake passes, the AI reads the serial number painted on every wagon (for example, `73102512177`). It looks at the same wagon in many frames so dust, glare, or one bad angle won't spoil the reading — the most consistent reading wins. In Config B (two cameras), the readings from both sides are merged, so a wagon that's unreadable on one side can still be picked up from the other. Once the rake has fully passed, the system has a list of wagon numbers it found. It compares this list to the expected wagons for that rake. Each wagon ends up as: ✅ matches as expected, ⚠️ different from expected, or ❌ couldn't read clearly — please check by hand. The system never silently guesses. Anything uncertain is flagged so the operator decides.

**Developer Scope:**
- Wagon-region detection on extracted frames using a pre-trained object-detection model
- OCR on the detected wagon regions, run on multiple frames per wagon
- Multi-frame voting to pick the most-confident serial number per wagon
- Config B only: cross-camera fusion (combining same-wagon readings from entry and exit cameras using time-aligned wagon-tracking)
- Auto-match of detected serials against the expected roster (links to existing 4.4 and 4.6)
- Confidence score and match status (Matched / Mismatch / Not-detected) per wagon
- Position-in-rake fallback when OCR confidence is below threshold
- Storage of detected serial, confidence score, frame thumbnails (one per camera in Config B), and match decision per wagon

**System Logic:**
- A "Not-detected" wagon still appears in the roster, marked for manual confirmation
- A "Mismatch" wagon raises an exception and blocks downstream auto-progress until reviewed

**Key Outputs:**
- Auto-filled wagon roster for the rake, ready for operator review
- Per-rake AI accuracy summary

---

### 4.16 OPERATOR REVIEW, OVERRIDE & AUDIT MODULE

**Purpose:** Let siding staff review the AI roster, fix any errors, and keep a permanent audit trail.

**In simple terms:** The operator opens the rake in the app and sees a clean table: wagon position, expected number, the number the AI read, a small thumbnail, and a status badge. For wagons the AI is sure about, the operator just glances and moves on. For the few flagged ⚠️ or ❌, the operator clicks "Override," writes a short reason ("paint faded, confirmed on site" or "wagon swapped at entry"), and saves. The roster cannot be marked confirmed until every wagon is either matched or manually overridden — nothing slips through. Once confirmed, the wagon list flows straight into loading, weighment, reconciliation, and the penalty register with no double entry. Every action is timestamped and tagged to a user. Months later, if there's a dispute with railway officials, the In-Charge can pull up the wagon's photo along with the full history of who confirmed what and when.

**Developer Scope:**
- Web review screen with a per-wagon table:
  - Position
  - Expected serial
  - Detected serial
  - Confidence
  - Match status badge
  - Frame thumbnail (click to enlarge; both sides shown in Config B)
  - One-click override action
- Override flow with mandatory reason
- Notification to the Siding In-Charge when a rake is fully reviewed and confirmed
- Per-rake audit trail accessible from the existing Rake screen
- Exception register for unresolved mismatches and "Not-detected" wagons

**System Logic:**
- A rake roster cannot be marked "AI-confirmed" until every wagon is either matched or overridden
- Override entries are immutable once saved; corrections create a new version with a reason
- All audit entries are timestamped and user-stamped

**Validations:**
- Only Siding Operator and Siding In-Charge roles can override
- Override reason is required and has a minimum length

**Key Outputs:**
- AI-confirmed wagon roster ready to feed downstream modules (4.6, 4.7, 4.11)
- Exception register (rake-wise, siding-wise, date-wise)
- Audit trail per rake

---

## 5. INTEGRATION WITH EXISTING MODULES

The new modules read from and write to the existing system as follows:

| Existing module | Interaction |
|---|---|
| 4.3 Rake Indent & Planning | Provides the expected wagon roster (when available) |
| 4.4 Rake Placement & TXR | Triggers camera capture start |
| 4.5 Unfit Wagon Management | Unfit wagons stay visible in the AI roster but are flagged |
| 4.6 Wagon-wise Loading | AI-confirmed roster auto-fills wagon entries |
| 4.9 Loading Time & Rake Movement | Triggers camera capture stop on loading completion |
| 4.11 Reconciliation | AI roster feeds wagon-level "Rake vs Weighment" matching |
| 4.13 Dashboards & MIS | Camera detection-accuracy KPI added to existing dashboards |

No existing business logic changes. The AI module adds data; the operator stays the source of truth via the override flow.

---

## 6. DELIVERABLES

1. Three new modules (4.14, 4.15, 4.16) added to the existing application
2. Database changes for AI detection records, audit trail, and override history
3. Camera onboarding admin screen (supports 1 or 2 cameras per siding)
4. Operator review screen (web, desktop)
5. Notifications for camera-offline and review-pending events
6. Per-organisation feature toggle for staged rollout
7. User documentation for Siding Operator and Siding In-Charge
8. Admin guide for camera onboarding and troubleshooting
9. UAT support and 30-day post-go-live warranty

---

## 7. OUT OF SCOPE (Reserved for Stage 2)

The following are **not** part of this proposal. They will be quoted separately if approved later:

- Visual estimation of coal load profile per wagon (heap analysis)
- Pre-departure penalty alerts (over-load / under-load warnings before the rake leaves the siding)
- Cross-check between Loadrite weighment data and visual load estimate
- Mobile-friendly review screen (current scope is desktop web)
- Admin diagnostics dashboard (detection-run health metrics)
- Daily summary emails to Management
- Edge inference appliance (offline-capable AI processing at the siding)
- More than two cameras per siding (loader-point camera, weighbridge camera)
- Custom-trained OCR for badly faded or coal-covered wagon serials
- Number-plate OCR for road vehicles (separate workflow under existing 4.1)
- Driver / operator face recognition

Indicative budget for Stage 2 (planning only): ₹15,00,000 – ₹22,00,000 (Fifteen Lakh to Twenty-Two Lakh) over 12–16 weeks.

---

## 8. ASSUMPTIONS & CUSTOMER DEPENDENCIES

This proposal assumes the following. A change to any of these may affect cost or timeline.

1. **Camera procurement and physical installation** is done either by the customer's vendor (self-procurement) or via Bundle 1 in §9.4 (developer turnkey).
2. **Camera placement survey** — the customer provides 30+ short sample clips from the proposed mounting position(s) before development starts.
3. **Internet at the siding** is at least 10 Mbps stable (wired preferred, or strong 4G), with a public IP or VPN path to the camera RTSP stream(s).
4. **Power and weatherproofing** of the camera and PoE/DVR equipment (when self-procured) is the customer's responsibility.
5. **Camera count per siding:** **1 (Config A)** or **2 (Config B)**, fixed at PO time. A later change is a scope revision.
6. **Existing Rake Management application** is in production and accessible to the development team.
7. **UAT environment** with sample data is available throughout the engagement.
8. **Customer review turnaround** during UAT is within 3 working days per cycle.
9. **Hardware procurement and installation** — the customer either self-procures (recommended camera: **Dahua DHI-ITC413-PW4D-IZ1**, 4 MP ANPR varifocal, IP67, STQC/ER-compliant; full spec and procurement checklist in `2026-05-01-camera-hardware-recommendations.md`) or opts in to **Bundle 1 in §9.4** for turnkey supply and installation. **The customer must confirm STQC/ER compliance of any camera procured after 1 April 2026** per the MeitY mandate, regardless of route.
10. **Recurring cloud cost** (₹6,000 – ₹12,000 per siding per month for AI processing and storage) will either be billed at cost or rolled into a per-siding annual subscription, agreed before kickoff.

---

## 9. COMMERCIAL TERMS

### 9.1 One-time Software Development Charges

The customer picks one of the following at PO time:

#### Option A — Single-Camera per Siding (Configuration A)

| Description | Amount (₹) |
|---|---|
| Module 4.14 — AI Camera Integration & Video Ingestion (single-camera) | 1,80,000 |
| Module 4.15 — AI-Based Wagon Identification & Roster Matching (single-camera) | 2,40,000 |
| Module 4.16 — Operator Review, Override & Audit | 1,40,000 |
| Project management, QA, UAT support, documentation, warranty | 40,000 |
| **Software Development Total — Option A** | **₹6,00,000 + GST** |
| In words | **Rupees Six Lakh only + GST** |

#### Option B — Dual-Camera per Siding (Configuration B)

| Description | Amount (₹) |
|---|---|
| Module 4.14 — AI Camera Integration & Video Ingestion (dual-camera, time-synced) | 2,40,000 |
| Module 4.15 — AI-Based Wagon Identification & Roster Matching (with cross-camera fusion) | 3,40,000 |
| Module 4.16 — Operator Review, Override & Audit (dual-thumbnail view) | 1,55,000 |
| Project management, QA, UAT support, documentation, warranty | 40,000 |
| **Software Development Total — Option B** | **₹7,75,000 + GST** |
| In words | **Rupees Seven Lakh Seventy-Five Thousand only + GST** |

### 9.2 Payment Milestones (apply to whichever Option is selected)

| Milestone | % | Option A (₹) | Option B (₹) |
|---|---|---|---|
| Advance on PO / kickoff | 50% | 3,00,000 | 3,87,500 |
| On UAT sign-off (approx. week 5–6) | 30% | 1,80,000 | 2,32,500 |
| On live release / production go-live | 20% | 1,20,000 | 1,55,000 |
| **Total** | **100%** | **6,00,000 + GST** | **7,75,000 + GST** |

GST is invoiced with each milestone.

### 9.3 Recurring Charges (Per Siding, Post-Go-Live)

| Description | Configuration A (₹/month) | Configuration B (₹/month) |
|---|---|---|
| Cloud AI processing (per-rake billing) | 2,000 – 4,000 | 3,500 – 7,000 |
| Frame and clip storage (90-day retention) | 1,000 – 3,000 | 2,000 – 5,000 |
| Bandwidth and platform share | 1,000 – 3,000 | 1,500 – 4,000 |
| Model updates and remote support | 2,000 | 2,000 |
| **Total per siding** | **₹6,000 – 12,000** | **₹9,000 – 18,000** |

Recurring charges may be passed through at cost or rolled into the existing application subscription, as agreed.

### 9.4 Optional Per-Siding Service Bundles

Each bundle can be picked separately per siding. Bundle setup must match the Option (A or B) chosen in §9.1.

#### Bundle 1 — Hardware Supply & Installation

Includes (per siding):
- Dahua DHI-ITC413-PW4D-IZ1 (4 MP ANPR varifocal, IP67/IK10, STQC/ER-compliant) × number of cameras
- Industrial enclosure with sun-shade and mounting bracket × number of cameras
- PoE injector + Cat6 outdoor cable (up to 50 m per camera)
- Surge protector + 1 kVA UPS (one shared per siding)
- On-site installation, lens alignment, RTSP commissioning
- 2-year camera warranty (manufacturer)

| Configuration | Cameras / siding | Bundle 1 price (₹/siding) | In words |
|---|---|---|---|
| A — Single | 1 | **1,45,000 + GST** | Rupees One Lakh Forty-Five Thousand only |
| B — Dual | 2 | **2,75,000 + GST** | Rupees Two Lakh Seventy-Five Thousand only |

#### Bundle 2 — Software Configuration & Go-Live

Includes (per siding):
- Camera onboarding inside the Railway Rake Management application (1 or 2 cameras as applicable)
- Network and firewall coordination with customer IT
- AI calibration (mounting angle, focus, frame rate, region of interest) for each camera
- 5 pilot-rake validation runs with accuracy report
- 1 operator training session (up to 4 attendees)
- 7-day post-go-live hand-holding

| Configuration | Cameras / siding | Bundle 2 price (₹/siding) | In words |
|---|---|---|---|
| A — Single | 1 | **50,000 + GST** | Rupees Fifty Thousand only |
| B — Dual | 2 | **70,000 + GST** | Rupees Seventy Thousand only |

If the customer self-procures hardware, only Bundle 2 applies. Bundle 1 alone, without the Software Development charges in §9.1, is not deliverable.

### 9.5 Total Cost Scenarios (3 Sidings: Pakur / Dumka / Kurwa)

| Scenario | Software (§9.1) | Bundle 2 × 3 sidings | Bundle 1 × 3 sidings | **Grand Total** | In words |
|---|---|---|---|---|---|
| **Software only — Option A** (customer self-procures + self-installs) | 6,00,000 | — | — | **₹6,00,000 + GST** | Rupees Six Lakh only |
| **Software + Go-Live — Option A** | 6,00,000 | 1,50,000 | — | **₹7,50,000 + GST** | Rupees Seven Lakh Fifty Thousand only |
| **Turnkey — Option A** (1 cam × 3 sidings = 3 cameras) | 6,00,000 | 1,50,000 | 4,35,000 | **₹11,85,000 + GST** | Rupees Eleven Lakh Eighty-Five Thousand only |
| **Software only — Option B** | 7,75,000 | — | — | **₹7,75,000 + GST** | Rupees Seven Lakh Seventy-Five Thousand only |
| **Software + Go-Live — Option B** | 7,75,000 | 2,10,000 | — | **₹9,85,000 + GST** | Rupees Nine Lakh Eighty-Five Thousand only |
| **Turnkey — Option B** (2 cam × 3 sidings = 6 cameras) | 7,75,000 | 2,10,000 | 8,25,000 | **₹18,10,000 + GST** | Rupees Eighteen Lakh Ten Thousand only |

Mixed setups (for example, Config A at one siding and Config B at another) are not supported in a single PO. All three sidings must run on the same configuration.

---

## 10. PROJECT SCHEDULE

| Configuration | Total duration |
|---|---|
| Option A — Single-Camera | **6 weeks** |
| Option B — Dual-Camera | **7 weeks** (extra week for cross-camera time-sync and fusion) |

### Schedule (Option A — 6 weeks)

| Week | Activity | Output |
|---|---|---|
| 1 | Sample-clip collection, RTSP integration setup | Sample data validated, RTSP pull working |
| 2 | Wagon detection and OCR pipeline build-out | End-to-end detection on dev set |
| 3 | Roster matcher, database schema changes, camera onboarding screen | Matching logic on test rakes |
| 4 | Operator review screen, override flow, exception register | Internal alpha |
| 5 | Multi-tenant hardening, audit trail, notifications, UAT with customer | **UAT sign-off (Milestone 2)** |
| 6 | Production rollout behind a feature flag, customer go-live | **Live release (Milestone 3)** |

### Schedule (Option B — 7 weeks)

Same as Option A through week 4. Week 5 adds dual-camera time-sync and cross-camera fusion. UAT sign-off shifts to week 6, live release to week 7.

The schedule assumes the customer dependencies in Section 8 are met on time. Slips on the customer side (sample clips, RTSP credentials, UAT feedback) shift the schedule one-for-one.

**Bundle rollout timing.** The schedule above is for software development. Bundle 1 (hardware) and Bundle 2 (go-live) for the **first / pilot siding** are delivered alongside the last two weeks of development. Bundle 1 + Bundle 2 for the other two sidings are delivered after the pilot, at roughly **one siding every two weeks**, paced to customer readiness.

---

## 11. WARRANTY & SUPPORT

- **Warranty period:** 30 calendar days after live release. Defects in the delivered modules are fixed at no extra cost during this period.
- **Post-warranty support:** covered by the recurring monthly charges in §9.3.
- **Out-of-scope changes** during the engagement are estimated and billed via a formal change request.

---

## FINAL NOTE FOR DEVELOPER

This add-on is an **operational accelerator**, not a penalty-prevention system on its own. Wagon-level detection accuracy, a clean audit trail, and disciplined override capture are what make it valuable. The developer must integrate cleanly with the existing modules, keep all existing business logic intact, and treat the AI output as a recommendation that the operator confirms — never as a replacement for human decisions.

**End of Proposal**
