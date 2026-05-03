# PROPOSAL FOR ADDITIONAL WORK
## Railway Rake Management Control System
### AI Camera-Based Rake Identification Module
### (Add-on to Existing System)

**Date:** 1 May 2026
**Reference SOW:** Scope of Work — Railway Rake Management Control System (Pakur / Dumka / Kurwa sidings)
**Currency:** INR (₹). All charges are exclusive of GST.

---

## 1. PROJECT OVERVIEW

The existing Railway Rake Management Control System digitizes the end-to-end coal movement workflow (Mine → Road Dispatch → Siding → Rake Loading → In-motion Weighment → RR → Power Plant → Reconciliation), as defined in the original Scope of Work (modules 4.1 to 4.13).

This proposal covers **additional work** to integrate AI-camera-based rake identification at the railway sidings. The objective is to automatically read painted wagon serial numbers as a rake passes the camera, and bind those wagons to the existing rake roster maintained in the application — eliminating manual rake-chartering, accelerating loading clearance, and creating an audit-grade visual record for every wagon.

This add-on extends the existing system. It does **not** replace any existing module. It plugs into the existing Rake, Wagon, and Loading data structures.

The customer chooses one of two configurations per the commercials in Section 9:

- **Configuration A — Single-Camera per Siding** (1 camera × 3 sidings = 3 cameras total)
- **Configuration B — Dual-Camera per Siding** (2 cameras × 3 sidings = 6 cameras total)

Configuration B improves wagon-side coverage (catches wagons whose entry-side serial is dirty/glared by reading the exit-side serial as well), at the cost of additional software, hardware, and installation effort.

---

## 2. KEY OBJECTIVES

- Eliminate manual rake-chartering at the siding (currently 30–60 minutes per rake)
- Improve wagon-roster accuracy from ~92–95% (manual) to ≥98% (Configuration A) or ≥99% (Configuration B)
- Provide visual audit trail (frame thumbnail per wagon) for dispute resolution with railway officials
- Reduce siding-staff workload during rake placement
- Lay the foundation for future Stage-2 modules (visual load estimation and pre-departure penalty alerts — separately quoted)

**Note:** This proposal does **not** by itself reduce loading penalties. Penalty prevention requires Stage-2 modules (load estimation + pre-departure alerts), which are out of scope here and listed under Section 7.

---

## 3. USER ROLES & ACCESS CONTROL

No new user role is added. Existing roles consume the AI camera output:

- **Siding Operator** — reviews auto-identified wagon list, confirms or overrides flagged wagons.
- **Siding In-Charge** — sees consolidated camera-based rake report.
- **Management** — views dashboard summary of camera-detection accuracy and exceptions.
- **System Admin** — manages camera credentials, model thresholds, and per-siding enable/disable toggles.

Role-based access continues to follow the rules already established in the existing system.

---

## 4. FUNCTIONAL SCOPE (MODULE-WISE)

Module numbering continues from the existing SOW (which ended at 4.13).

---

### 4.14 AI CAMERA INTEGRATION & VIDEO INGESTION MODULE

**Purpose:** Connect the customer's IP camera(s) at the siding to the system so every rake is recorded automatically.

**In simple terms:** Today, when a rake arrives, the siding team has to physically walk along the rake and check each wagon's painted number against the indent. This module lets the system watch the rake instead. The customer installs one or two cameras at the siding (entry-only for Configuration A, entry + exit for Configuration B). Once registered in the application by the System Admin (camera address, login), the system silently begins recording every time a rake is placed, and stops when loading completes. No one has to start or stop the camera manually. If a camera ever goes offline — power cut, internet down, dirty lens — the System Admin receives a notification so it can be fixed before the next rake arrives.

**Developer Scope:**
- Camera onboarding screen (RTSP URL, credentials, siding mapping, role: entry / exit)
- Continuous video stream pull from one **or two** IP cameras per siding
- Frame extraction at configurable rate (default 5 frames/second)
- Per-rake clip generation triggered automatically on Rake Placement event (existing 4.4)
- Capture stops automatically on Loading Completion event (existing 4.9)
- Secure storage of frame snapshots and rake clip metadata
- Camera-offline alert to System Admin
- For Configuration B only: time-synchronisation between the two cameras at the same siding

**System Logic:**
- Each siding can have **one or two** cameras registered, depending on contracted configuration
- All captured artefacts are organisation-scoped (multi-tenant safe)

**Validations:**
- Cannot enable AI module for a siding without all contracted cameras registered and reachable
- Cannot reuse the same camera for two sidings simultaneously

---

### 4.15 AI-BASED WAGON IDENTIFICATION & ROSTER MATCHING MODULE

**Purpose:** Read the painted serial number on each wagon and match it against the expected rake roster.

**In simple terms:** As the rake passes the camera, AI software reads the serial number painted on every wagon (for example, `73102512177`). The system looks at the same wagon several times across multiple frames so dust, glare, or one bad angle do not throw off the reading — the most consistent reading wins. In Configuration B (two cameras), readings from both sides of the rake are fused so a wagon that is unreadable from one side can still be recognised from the other. Once the rake has fully passed, the system has a complete list of detected wagon numbers. It then compares this list with the wagon roster expected for that rake (taken from the indent and placement records). For each wagon, the system gives one of three results: ✅ matches the expected wagon, ⚠️ a different wagon than expected, or ❌ could not read this wagon clearly — please check manually. The system never silently guesses on low-confidence wagons; uncertain ones are clearly flagged so the operator decides.

**Developer Scope:**
- Wagon-region detection on extracted frames using a pre-trained object-detection model
- Optical Character Recognition (OCR) on detected wagon regions, applied to multiple frames per wagon
- Multi-frame voting to select the most-confident serial number per wagon
- For Configuration B only: cross-camera fusion (combining same-wagon readings from entry and exit cameras using time-aligned wagon-tracking)
- Auto-match of detected serials against the expected roster (linked to existing 4.4 and 4.6)
- Confidence score and match status (Matched / Mismatch / Not-detected) per wagon
- Position-in-rake fallback when OCR confidence is below threshold
- Storage of detected serial, confidence score, frame thumbnails (one per camera in Configuration B), and match decision per wagon

**System Logic:**
- A wagon flagged as "Not-detected" still appears in the roster, marked for mandatory manual confirmation
- A wagon flagged as "Mismatch" raises an exception and blocks downstream auto-progress until reviewed

**Key Outputs:**
- Auto-populated wagon roster for the rake, ready for operator review
- Per-rake AI accuracy summary

---

### 4.16 OPERATOR REVIEW, OVERRIDE & AUDIT MODULE

**Purpose:** Allow siding staff to review the AI-generated roster, correct any errors, and produce an immutable audit trail.

**In simple terms:** The siding operator opens the rake in the application and sees a clean table: wagon position, the wagon number expected, the number the AI actually read, a small thumbnail picture, and a status badge. For most wagons (those the AI is confident about), the operator simply glances and moves on. For the handful flagged ⚠️ or ❌, the operator clicks "Override," writes a short reason ("paint faded, confirmed manually on site" or "wagon swapped at entry, updated"), and saves. The roster cannot be marked as confirmed until every single wagon is either auto-matched or manually overridden — so nothing slips through. Once confirmed, the wagon list flows directly into all existing downstream modules (loading, weighment, reconciliation, penalty register) with no duplicate data entry. Every action is timestamped and user-stamped. Months later, if there is a dispute with railway officials, the In-Charge can pull up the wagon's frame thumbnail along with a complete history of who confirmed what and when.

**Developer Scope:**
- Web review screen showing per-wagon table:
  - Position
  - Expected serial
  - Detected serial
  - Confidence
  - Match status badge
  - Frame thumbnail (clickable to enlarge; both sides shown in Configuration B)
  - One-click override action
- Override workflow with mandatory reason capture
- Notification to Siding In-Charge when an entire rake is reviewed and confirmed
- Per-rake audit trail accessible from existing Rake screen
- Exception register for unresolved mismatches and "Not-detected" wagons

**System Logic:**
- A rake roster cannot be marked as "AI-confirmed" until every wagon has either a successful match or a manual override
- Override entries are immutable once saved; corrections create a new version with a reason
- All audit entries are timestamped and user-stamped

**Validations:**
- Only Siding Operator and Siding In-Charge roles can perform overrides
- Override reason field is mandatory with a minimum length

**Key Outputs:**
- AI-confirmed wagon roster ready to feed downstream modules (4.6, 4.7, 4.11)
- Exception register (rake-wise, siding-wise, date-wise)
- Audit trail per rake

---

## 5. INTEGRATION WITH EXISTING MODULES

The new modules read from and write to the existing system as follows:

| Existing module | Interaction |
|---|---|
| 4.3 Rake Indent & Planning | Provides expected wagon roster (when available) |
| 4.4 Rake Placement & TXR | Triggers camera capture start |
| 4.5 Unfit Wagon Management | Unfit wagons remain visible in AI roster but flagged |
| 4.6 Wagon-wise Loading | AI-confirmed roster auto-populates wagon entries |
| 4.9 Loading Time & Rake Movement | Triggers camera capture stop on loading completion |
| 4.11 Reconciliation | AI roster contributes to "Rake vs Weighment" wagon-level matching |
| 4.13 Dashboards & MIS | Camera detection-accuracy KPI added to existing dashboards |

No existing business logic is altered. The AI module supplements existing data; the operator remains the source of truth via the override mechanism.

---

## 6. DELIVERABLES

1. Three new functional modules (4.14, 4.15, 4.16) integrated into the existing application
2. Database extensions to capture AI detection records, audit trail, and override history
3. Camera onboarding administration screen (supports 1 or 2 cameras per siding)
4. Operator review screen (web, desktop)
5. Notification wiring for camera offline / review pending events
6. Per-organisation feature toggle for staged rollout
7. User documentation for Siding Operator and Siding In-Charge
8. Administrator guide for camera onboarding and troubleshooting
9. UAT support and post-go-live warranty period (30 days)

---

## 7. OUT OF SCOPE (Reserved for Stage 2)

The following items are **not** part of this proposal. They will be quoted separately if approved later:

- Visual estimation of coal load profile per wagon (heap analysis)
- Pre-departure penalty-prevention alerts (over-load / under-load warnings to operator before rake leaves siding)
- Cross-validation between Loadrite weighment data and visual load estimate
- Mobile-optimised review screen (current scope is desktop web)
- Admin diagnostics dashboard (detection-run health metrics)
- Daily summary emails to Management
- Edge inference appliance (offline-capable AI processing at siding)
- More than two cameras per siding (loader-point camera, weighbridge camera)
- Custom-trained OCR models for severely faded or coal-covered wagon serials
- Number-plate OCR for road vehicles (separate workflow under existing 4.1)
- Driver / operator face recognition

Indicative budget for Stage 2 (for planning only): ₹15,00,000 – ₹22,00,000 (Fifteen Lakh to Twenty-Two Lakh) over 12–16 weeks.

---

## 8. ASSUMPTIONS & CUSTOMER DEPENDENCIES

The proposal is built on the following assumptions. Material change to any of these may impact cost or timeline.

1. **Camera procurement and physical installation** is performed either by the customer's vendor (self-procurement) or via Bundle 1 in §9.4 (developer turnkey).
2. **Camera placement survey** — customer provides 30+ short sample clips from the proposed mounting position(s) before development starts.
3. **Internet connectivity at the siding** is at least 10 Mbps stable (wired preferred, or strong 4G), with a public-IP or VPN path to the camera RTSP stream(s).
4. **Power and weatherproofing** of the camera and PoE/DVR equipment (when self-procured) is the customer's responsibility.
5. **Camera count per siding:** **1 (Configuration A)** or **2 (Configuration B)**, fixed at the time of PO. A change later is a scope revision.
6. **Existing Rake Management application** is in production and accessible to the development team for integration.
7. **UAT environment** with sample data is available throughout the engagement.
8. **Customer review turnaround** during UAT is within 3 working days per cycle.
9. **Hardware procurement and installation** — customer either self-procures (recommended camera: **Dahua DHI-ITC413-PW4D-IZ1**, 4 MP ANPR varifocal, IP67, STQC/ER-compliant; full specification and procurement checklist in `2026-05-01-camera-hardware-recommendations.md`) or opts in to **Bundle 1 in §9.4** for turnkey supply and installation. **Customer must confirm STQC/ER compliance of any camera procured after 1 April 2026** per the MeitY mandate, regardless of route.
10. **Recurring cloud cost** (₹6,000 – ₹12,000 per siding per month for AI processing and storage) will either be billed at cost to the customer or absorbed into a per-siding annual subscription, to be agreed before kickoff.

---

## 9. COMMERCIAL TERMS

### 9.1 One-time Software Development Charges

The customer chooses one of the following options at the time of PO:

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
| Module 4.14 — AI Camera Integration & Video Ingestion (dual-camera, time-synchronised) | 2,40,000 |
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

GST is invoiced alongside each milestone.

### 9.3 Recurring Charges (Per Siding, Post-Go-Live)

| Description | Configuration A (₹/month) | Configuration B (₹/month) |
|---|---|---|
| Cloud AI processing (per-rake billing model) | 2,000 – 4,000 | 3,500 – 7,000 |
| Frame and clip storage (90-day retention) | 1,000 – 3,000 | 2,000 – 5,000 |
| Bandwidth and platform share | 1,000 – 3,000 | 1,500 – 4,000 |
| Model updates and remote support | 2,000 | 2,000 |
| **Total per siding** | **₹6,000 – 12,000** | **₹9,000 – 18,000** |

Recurring charges may be passed through at cost or bundled into the existing application subscription, as agreed.

### 9.4 Optional Per-Siding Service Bundles

Each bundle is independently selectable per siding. The configuration of these bundles must match the Option (A or B) selected in §9.1.

#### Bundle 1 — Hardware Supply & Installation

Inclusions (per siding):
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

Inclusions (per siding):
- Camera onboarding inside the Railway Rake Management application (1 or 2 cameras as applicable)
- Network and firewall coordination with customer IT
- AI calibration (mounting angle, focus, frame rate, region of interest) for each camera
- 5 pilot-rake validation runs with accuracy report
- 1 operator training session (up to 4 attendees)
- 7-day post-go-live hand-holding support

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

Mixed selections (for example, Configuration A at one siding and Configuration B at another) are not supported in a single PO. All three sidings must operate on the same configuration.

---

## 10. PROJECT SCHEDULE

| Configuration | Total duration |
|---|---|
| Option A — Single-Camera | **6 weeks** |
| Option B — Dual-Camera | **7 weeks** (additional week for cross-camera time-sync and fusion) |

### Schedule (Option A — 6 weeks)

| Week | Activity | Output |
|---|---|---|
| 1 | Sample-clip collection, RTSP integration setup | Sample data validated, RTSP pull working |
| 2 | Wagon detection and OCR pipeline build-out | End-to-end detection on dev set |
| 3 | Roster matcher, database schema extensions, camera onboarding screen | Matching logic on test rakes |
| 4 | Operator review screen, override workflow, exception register | Internal alpha |
| 5 | Multi-tenant hardening, audit trail, notifications, UAT with customer | **UAT sign-off (Milestone 2)** |
| 6 | Production rollout behind feature flag, customer go-live | **Live release (Milestone 3)** |

### Schedule (Option B — 7 weeks)

Same as Option A through week 4. Week 5 adds dual-camera time-sync and cross-camera fusion. UAT sign-off shifts to week 6, live release to week 7.

The schedule assumes the customer dependencies in Section 8 are met on time. Slips on customer side (sample clips, RTSP credentials, UAT feedback) will shift the schedule on a one-for-one basis.

**Bundle rollout timing.** The schedule covers software development. Bundle 1 (hardware) and Bundle 2 (go-live) for the **first / pilot siding** are delivered alongside the final two weeks of the development schedule. Bundle 1 + Bundle 2 for the remaining two sidings are delivered post-pilot at a pace of approximately **one siding every two weeks**, sequenced to customer readiness.

---

## 11. WARRANTY & SUPPORT

- **Warranty period:** 30 calendar days post-live release. Defects in the delivered modules are fixed at no additional cost during this period.
- **Post-warranty support:** covered under the recurring monthly charges in §9.3.
- **Out-of-scope changes** during the engagement are estimated and billed via formal change request.

---

## FINAL NOTE FOR DEVELOPER

This add-on is an **operational accelerator**, not a penalty-prevention system on its own. Wagon-level accuracy of detection, integrity of the audit trail, and disciplined override capture are critical to its value. The developer is expected to integrate cleanly with the existing modules, preserve all existing business logic, and treat the AI output as a recommendation that the operator confirms — never as an authoritative override of human decisions.

**End of Proposal**
