# Railway Rake Management Control System - Implementation Plan

## Executive Summary

The Railway Rake Management Control System (RRMCS) is a comprehensive coal logistics management platform replacing manual Excel-based operations for three railway sidings: **Pakur, Dumka, and Kurwa** (all in Jharkhand). The system covers the complete end-to-end coal movement lifecycle: **Mine → Road Dispatch → Railway Siding → Rake Loading → In-Motion Weighment → Railway Receipt (RR) → Power Plant → Reconciliation & MIS**.

**Core Business Objective:** Prevent avoidable railway penalties (currently estimated at ₹1.5–2.5 Crores/year) by providing real-time visibility, automated demurrage countdown timers, overload detection before dispatch, and proactive multi-level alerts.

**Implementation Approach:** Data entry remains the same — only the medium changes from Excel to application. After entry, all calculations, penalty checks, analysis, and dashboards are managed automatically by the system. One centralized data source replaces multiple Excel files.

## Technology Stack

**Backend Framework:**

- Laravel 12.x (this starter kit)
- PHP 8.4+
- Database: PostgreSQL (production), SQLite (development)
- Queue: Redis (Horizon already configured)

**Frontend (Application UI):**

- Inertia.js v2 (inertiajs/inertia-laravel)
- React 19 (@inertiajs/react)
- Tailwind CSS v4
- Laravel Wayfinder (TypeScript route/action generation for `@/routes` and `@/actions`)
- machour/laravel-data-table (server-side DataTable rendering with Laravel + Inertia + React/TanStack for rake lists, indent tracking, penalty dashboards)

**Frontend Mobile/PWA:**

- ServiceWorker API (offline-first architecture, install, activation, fetch strategies)
- IndexedDB (local data persistence, sync queue, form state)
- Barcode/QR scanning: ZXing.js (vehicle, wagon, RR, loader identification)
- Photo handling: Native camera API + image compression (JPEG optimization)
- Location: Geolocation API (siding location validation, boundary checks)
- Web Push API (demurrage alerts, overload warnings, RR mismatches)
- Background Sync API (offline form submission queue, retry logic)

**Admin Panel:**

- Filament 5.x for master data (sidings, vehicles, routes, freight rates, loaders, users, roles)

**Authentication & Security:**

- Laravel Fortify (headless auth)
- Laravel Sanctum (API tokens if needed)
- Spatie Laravel Permission 7.x (roles and permissions only; no org-scoped permissions required for single-org)
- Spatie Activity Log (audit trail)
- 2FA support (already configured)

**State Machines:**

- Spatie Laravel Model States 2.x (already installed) for Rake, Indent, VehicleUnload, TXR, RR Prediction, Penalty lifecycles

**Real-Time:**

- Laravel Reverb (WebSockets) for live demurrage timers and chat
- Laravel Echo (frontend) for subscribing to channels

**AI & Data Processing:**

- Laravel AI SDK (laravel/ai) for RR document extraction and optional AI assistant
- Spatie Media Library (RR PDF storage)
- Laravel Excel / Filament Excel for imports and report exports

**Quality & Testing:**

- Larastan (PHPStan), Laravel Pint, Pest v4

## Repository Structure

**Base Repository:** This Laravel Inertia React starter kit (no separate “module” package).

**Application Structure (no nwidart/laravel-modules):**

- **Models:** `app/Models/` (e.g. `Siding`, `Rake`, `Indent`, `VehicleUnload`, `CoalStock`, `RrDocument`)
- **Actions:** `app/Actions/RakeManagement/` or `app/Actions/` (e.g. CreateIndent, CompleteTxr, CalculateDemurrage)
- **Controllers:** `app/Http/Controllers/RakeManagement/` (Inertia responses for app pages)
- **Form Requests:** `app/Http/Requests/RakeManagement/`
- **Migrations:** `database/migrations/` (with clear naming, e.g. `create_sidings_table`, `create_rakes_table`)
- **Seeders:** `database/seeders/` (e.g. `RakeManagementRolePermissionSeeder`, `SidingSeeder`)
- **Config:** `config/rake_management.php` (tolerances, free time minutes, rate per hour, etc.)
- **Frontend pages:** `resources/js/pages/rake-management/` (e.g. `Dashboard.tsx`, `Rakes/Index.tsx`, `Rakes/Show.tsx`)
- **Routes:** Web routes under a prefix (e.g. `/rake` or `/app`) with middleware for auth and optional siding context

Filament resources for admin live in `app/Filament/` (or existing admin structure) for sidings, vehicles, routes, freight rates, loaders, and user/role management as needed.

## Architecture Decisions (Confirmed)

### Decision #1: Database

- Use PostgreSQL for production; SQLite for local development. All rake management tables live in the main app database.

### Decision #2: Single-Tenant Internal Application (Not Multi-Tenant SaaS)

- **Single-Tenant Architecture:** `MULTI_ORGANIZATION_ENABLED=false` (default configuration). This is an internal application for a single coal mining company, not a multi-tenant SaaS product.
- **Organization Routes Handling:** Routes under `middleware('tenancy.enabled')` (e.g., `/organizations/*`) are protected by `EnsureTenancyEnabled` middleware. When tenancy is disabled, these routes redirect to `/dashboard`.
- **Feature Flag System:** Use `App\Support\FeatureHelper` (or `@feature` blade directive) to check feature flags centrally. Features can be globally disabled via `config('feature-flags.globally_disabled')` array. Dashboard, billing, and profile features are available; organization management UI is hidden.
- **Sidings** are data entities: table `sidings` with optional `organization_id` (always references the default org). The three sidings (Pakur, Dumka, Kurwa) are rows in `sidings`.
- **User–Siding Mapping:** Table `user_siding` (user_id, siding_id, is_primary). Users are restricted to their assigned siding(s). Single-site users get one siding and can be auto-redirected; multi-site users see a Site Selection screen and set `active_siding_id` in session.
- **Authorization (RBAC):** Spatie Laravel Permission for role-based access control (e.g., siding_operator, siding_in_charge, management, finance, super_admin). No multi-tenant permission scoping required. Authorization gates/policies use `siding_id` and `user_siding` for siding-level filtering. Super-admins bypass siding restrictions.

### Decision #3: No Separate Module Package

- Do **not** use nwidart/laravel-modules. Implement everything in `app/`, `database/`, `resources/js/pages/`, and Filament as above.

### Decision #4: Real-Time Timer Implementation ✓

**Decision:** Use Laravel Reverb (recommended)

**Implementation:**

- Laravel Reverb 1.7.1 already installed
- Broadcast configuration: `BROADCAST_CONNECTION=reverb`
- Live demurrage countdown timer updates every 1 second via WebSockets
- Real-time alerts broadcast to clients:
- 60 minutes remaining (Amber warning)
- 30 minutes remaining (Red urgent)
- 0 minutes exceeded (Critical penalty active)
- Each additional hour penalty escalation

**Channels:**

- `rakes.{rake_id}` - Per-rake updates (timer, status changes)
- `sidings.{siding_id}` - Per-siding alerts (all rakes, pending weighments, RR not uploaded)
- `users.{user_id}` - Personal notifications

**Events:**

- `RakeTimerUpdated` (every second for active rakes)
- `RakeStatusChanged` (state transitions)
- `OverloadDetected` (loader vs weighment comparison)
- `RrMismatchAlert` (prediction vs actual variance)

**Frontend Integration:**

- Inertia React pages subscribe via Laravel Echo to Reverb channels (`rakes.{id}`, `sidings.{id}`)
- Update timer and alerts in React state; optional polling fallback if needed
- Dashboard banner for critical alerts; push notifications via service workers if PWA is enabled

### Decision #5: Mobile-First PWA with Offline-First Architecture ✓

**Decision:** Mobile-first PWA (Progressive Web App) with offline-first architecture; single React codebase for all platforms

**Mobile-First Architecture:**
- Responsive design: Mobile (320px) → Tablet (768px) → Desktop (1024px+)
- Touch-first interactions: NumPad, sliders, barcode scanner instead of text dropdowns
- Bottom navigation bar (always visible, 56px+ height)
- Form-first workflows: Quick entry modals/bottom sheets as primary UI
- Role-based progressive enhancement: Loader (minimal UI) → Operator (standard forms) → In-Charge (dashboards)

**Offline-First Architecture:**
- IndexedDB + ServiceWorker sync queue: All forms work offline, queued submissions sync when online
- Pessimistic sync strategy: Server-wins for multi-user edits; local state reconciles on conflict
- Smart cache invalidation: Vehicle master (24hr), Siding config (7-day), Active rakes (30-min), Stock ledger (real-time fallback)
- Background Sync API: Automatic submission retry (exponential backoff: 1min, 5min, 15min, 30min)
- Local timers: Demurrage countdown continues offline, reconciles when online

**Real-Time Updates (Mobile):**
- Demurrage timer: 1-second updates via Reverb WebSocket (5-second polling fallback if offline)
- Status broadcasts: Instant via `rakes.{id}` and `sidings.{id}` channels
- Alerts: Push notifications for demurrage risk (<30min), overload (>5%), RR mismatch (>2% variance)
- Multi-user sync: Optimistic UI updates + conflict resolution on server response

**Mobile Data Entry Specifications:**
- **Truck Arrival:** Scan vehicle QR → Mine weight (NumPad) → [Confirm] (30 sec vs 2 min manual)
- **Wagon Loading:** Wagon grid (visual theater) → Tap wagon → Loader (button toggle) → Qty (slider) → [Save & Next] (<10 sec per wagon vs 60+ sec)
- **Weighment:** Photo of slip → OCR auto-fills wagons → Comparison grid (80% automation vs manual entry)
- **Photo Capture:** TXR unfit wagons (auto-tagged), RR documents (OCR), Loader performance (optional)
- **QR/Barcode Scanning:** Vehicle, wagon, RR, loader identification (auto-fill, no manual entry errors)
- **Location Validation:** GPS confirms operator at siding for vehicle unload; warns if moved during loading

**Deliverables:**
- PWA manifest + ServiceWorker (install, activation, fetch strategies)
- IndexedDB schema for offline forms + sync queue
- Mobile UI components: NumPad, wagon theater grid, barcode scanner, floating action button
- Responsive layout: Mobile breakpoints + safe area handling (notch, bottom bar)
- Form validation framework: Client-side (offline) + server-side (online verification)
- Real-time timer with local fallback
- Push notification system for mobile
- Performance targets: <2s load (3G), <2MB bundle (gzipped), offline-first workflows fully functional

### Decision #6: SHAReTrack API Integration ✓

**Decision:** Manual data entry for now, API integration later

**Phase 1 Implementation (Manual Entry):**

- Vehicle dispatch data entered manually into system
- Forms mirror SHAReTrack data structure (for easy migration to API later)
- All fields captured as per PRD:
- Vehicle Master: vehicle\_number, rfid\_tag, permitted\_capacity\_mt, tare\_weight\_mt, owner\_name, vehicle\_type, gps\_device\_id
- Coal Loading: loading\_start\_time, loading\_end\_time, final\_net\_weight\_mt, overload\_flag, underload\_flag, jimms\_challan\_number
- Dispatch Trip: challan\_number, dispatch\_time, approved\_route\_id, siding\_id, trip\_status

**Phase 2 Implementation (API Integration - Future):**

- API endpoints to be documented by SHAReTrack team
- Token-based authentication (Laravel Sanctum personal\_access\_tokens)
- Read-only data ingestion
- Retry logic for failed API calls
- Queue for asynchronous processing
- Data mapping layer to convert SHAReTrack format to internal format

**Data Structure Preparation:**

- Design API contracts even for manual entry phase
- Create Data Transfer Objects (DTOs) for SHAReTrack data
- Ensure manual forms use same DTOs for future API integration

### Decision #7: DataTable Integration & Feature Flags ✓

**Decision:** Use machour/laravel-data-table for server-side DataTable rendering; centralized feature flag system via App\Support\FeatureHelper

**DataTable Implementation:**
- machour/laravel-data-table provides server-side rendering with Laravel + Inertia + React/TanStack Data Table
- Use for all list views: Rakes list, Indents list, Penalties dashboard, Stock ledger, Loader performance, VehicleUnload records
- Server-side filtering, sorting, and pagination (reduces frontend bundle size, improves performance on large datasets)
- Query-based table definitions in controllers; React components auto-generate UI
- Benefits: Reusable, type-safe, SEO-friendly, mobile-responsive

**Feature Flag System:**
- Use `App\Support\FeatureHelper` for centralized feature flag checks
- `FeatureHelper::isActiveForKey($key, $user)` for user-level feature checks
- Checks against `config('feature-flags.globally_disabled')` array; globally disabled features return 404 for all users (including super-admin)
- Middleware `EnsureTenancyEnabled` automatically hides organization routes when `MULTI_ORGANIZATION_ENABLED=false`
- Feature flags are checked at route level (middleware) and component level (@feature directive in Blade, or FeatureHelper in controllers)

## Operational Context & Real-World Data (November 2025 - Dumka Siding)

### Rake Volume & Throughput
- **165 rakes processed per month** (6-8 per day average)
- **Loading time patterns:** 50-90 min (7%), 90-150 min (27%), 150-210 min (41%), 210+ min (23%)
- **Demurrage risk:** 24% of rakes exceed 3-hour free time, risk penalties (₹15,440+ per hour excess)
- **Penalty potential:** ₹1.5-2.5 Cr/year across three sidings; Nov 2025 showed ₹1.5-2 Lakhs/month at Dumka alone

### Loader Performance (Real Data)
- **Loader 16** (Backhoe): 63 rakes/month, 125 min avg, ±15 min consistency (fastest, most reliable)
- **Loader 11** (Excavator): 42 rakes/month, 145 min avg, ±30 min consistency
- **Loader 17** (Wheel Loader): 60 rakes/month, 155 min avg, ±45 min consistency (slowest but higher capacity)
- **Mobile dashboard will track per-loader trends** for proactive scheduling and performance management

### Weight Discrepancy Patterns (Real Nov 2025 Data)
- **Perfect match (±2%):** 87 rakes (53%)
- **Minor variance (3-5%):** 54 rakes (33%)
- **Overload risk (5-7%):** 19 rakes (12%)
- **Major overload (>7%):** 5 rakes (3%)
- **Example:** Rake 142 - Loader recorded 292 MT, weighment showed 297.2 MT (+1.79%), penalty ₹15,440 incurred
- **Mobile benefit:** Real-time weighment alerts would catch 80% of these during loading (24+ hours before RR arrives)

### Penalty Prevention Opportunity
- **Current:** Penalties discovered post-RR (24+ hours late), no time for correction
- **Target:** Detect overloads during weighment, enable siding to redirect excessive load before RR
- **Financial impact:** ₹60-100K/month savings if 40-50% of correctable overloads prevented
- **Timeline:** 165 rakes/month × ₹15,440 avg penalty × 3% major overloads = ₹76K/month potential savings

### Penalty Minimization Strategy Validation (✅ 100% Coverage)

**All critical penalty prevention strategies from reference documents are FULLY INTEGRATED:**

**1. Demurrage Prevention (₹15,440/hour)**
- ✅ Live 3-hour timer via Reverb WebSocket (1-sec updates)
- ✅ Real-time alerts at 60min/30min/0min thresholds
- ✅ Auto-penalty calculation: `CEIL(extra_minutes/60) × ₹15,440`
- ✅ Offline countdown via ServiceWorker (PWA)

**2. Overload Prevention (POL1/POLA/PLO)**
- ✅ Individual wagon overload detection (loader vs weighment comparison)
- ✅ Average rake overload tracking with PCC validation
- ✅ Pre-RR overload alerts (24+ hours before RR)
- ✅ Per-loader trend analytics (Report #5, Page 9a)

**3. Spillage Prevention (SPL)**
- ✅ Wagon rim trimming enforcement via UI guidance
- ✅ Post-loading spillage check (Guard inspection + photo)
- ✅ Track clearing validation before rake movement

**4. Uneven Loading Prevention (ULC)**
- ✅ Uniform distribution enforcement per wagon
- ✅ Per-loader capability tracking
- ✅ Sequential loading optimization

**5. Time Window Enforcement**
- ✅ TXR: Max 1h 40min (tracked in state machine)
- ✅ Loading: 3h free (180-min timer)
- ✅ In-motion weighment: 20min included in free window
- ✅ "5 min = 1 hour penalty" rule enforced

**6. All 11 Penalty Types Covered**
- Demurrage (DEM) | Overloading (POL1/POLA) | Uneven Loading (ULC) | Spillage (SPL)
- Weighment Charge (WMC) | Detention | Shunting | Pilot | Moisture (MCF) + others

**Financial Impact:** ₹72-120 Lakhs/year across 3 sidings through early detection (24+ hours before RR)

---

## Digitization Checklist (Replace All Manual Work)

The following manual processes (currently Excel/paper) must be fully digitized with mobile-first workflows:

| # | Manual Process (Current) | Mobile-First Digitization | Time Saved | Mobile Workflow |
|---|---|---|---|---|
| 1 | Truck arrival on paper/Excel (2 min) | Scan vehicle QR → Mine weight (NumPad) → [Confirm] | **87%** | 30 sec form |
| 2 | Arrival weighment manual check | Auto-compare mine vs siding, validation rules, reject if >5% variance | **Instant** | Toast alert, no form |
| 3 | Coal unloading log (handwritten) | Timer starts auto, [Unload Complete] tap, location tagged | **Automatic** | 1-sec action |
| 4 | Stock ledger (Excel running total) | Auto-append on unload, real-time balance widget, low-stock alerts | **Real-time** | Dashboard card |
| 5 | Indent request (30 min) | Quick modal: target qty + date, auto-validate stock, [Submit] | **95%** | Bottom sheet form |
| 6 | TXR memo (handwritten, lost) | Photo unfit wagons, auto-tag wagon #/reason/time/GPS, stored | **Searchable** | Camera + form |
| 7 | 3-hour timer (stopwatch?) | Floating widget: Live countdown (1/sec), color-coded (green/yellow/red) | **Always visible** | Persistent widget |
| 8 | Wagon loading slips (60 min) | Wagon grid (60 wagons) → Tap wagon → Loader + Qty → [Next] | **83%** | <10 sec per wagon |
| 9 | Guard inspection (signed form) | Tap [Guard Approved], optional photo, 1-sec submit | **99%** | Quick action |
| 10 | Weighment entry (manual, error-prone) | Photo slip → OCR auto-fills → Comparison grid → [Confirm] | **80% auto** | Photo + form |
| 11 | Demurrage calc (post-RR, too late) | Auto-calc during weighment, instant alert if overload detected | **24hr early** | Real-time alert |
| 12 | RR prediction (Excel formula) | System auto-calculates from weight/distance/rate | **Automatic** | Dashboard display |
| 13 | RR actual (1-2 hours manual entry) | Photo RR → OCR extracts → Verification → [Confirm] | **92%** | 5-min form |
| 14 | Reconciliation (2-3 hours Excel) | Five-point auto-reconcile, instant variance alerts | **Instant** | Dashboard summary |
| 15 | Penalty register (Excel tracking) | Real-time auto-track, PREDICTED/CONFIRMED status, instant export | **Real-time** | Dashboard + report |

### Key Metrics (Per Rake - Based on Real Nov 2025 Data)
- **Current total time:** 120+ minutes (across 6+ people, fragmented)
- **Mobile-first total:** 15-20 minutes (coordinated, single operator flow)
- **Data entry reduction:** 40+ manual touches → 8-12 quick-tap actions
- **Error reduction:** 85-90% accuracy → 95%+ (auto-calculation)
- **Penalty detection:** +24 hours late → Real-time (during weighment)
- **ROI:** 165 rakes/month × ₹15,440 penalty × 3% overloads × 40% correctable = **₹76K/month potential savings**

## Chat Mechanism

**Objective:** In-app chat for coordination between siding operators, in-charge, and management (per Scope of Work).

**Implementation:**

- **Storage:** Tables `chat_rooms` and `chat_messages`. Rooms can be global (e.g. "Operations") or per siding ("Pakur", "Dumka", "Kurwa") or per rake (e.g. "Rake #12345") for focused discussion.
- **Real-time:** Laravel Reverb channel per room (e.g. `chat.room.{id}`). Laravel Echo on frontend for subscribe/send.
- **API:** REST or Inertia endpoints: list rooms, list messages (paginated), send message. Optional: file attachments (e.g. RR draft) via Spatie Media Library.
- **UI:** Chat panel or dedicated page in the rake-management app (Inertia React). Show unread count in layout; optional desktop notifications.
- **Authorization:** Only users with access to the siding (or management) can see that room’s messages; use existing roles and user_siding.

**Optional:** Thread or channel for "AI Assistant" (see AI section) so users can ask questions in the same place.

## AI Features

**Objective:** As per Scope of Work — AI for RR extraction, proactive alerts, and admin database chat (digitization + intelligence).

### 1. RR Document Extraction (AI/OCR)

- **Input:** Uploaded RR PDF (stored with Spatie Media Library).
- **Process:** Extract text (OCR if needed); use Laravel AI SDK (e.g. OpenAI/Anthropic) with structured output to fill: rr_number, rr_date, station_from, station_to, weights, freight_amount, gst_amount, total_amount, wagon-wise details, etc.
- **Output:** Pre-fill `rr_extracted_data` and/or `rr_actuals` draft; confidence scores where applicable (>85% auto-confirm, <85% manual review).
- **Config:** Model and prompt in config; queue job for large documents.
- **Accuracy:** 90%+ OCR accuracy with AI verification

### 2. Proactive AI Notifications (✅ 100% Coverage)

**Real-Time Alert Channels:**
- ✅ Reverb WebSocket channels: `rakes.{id}`, `sidings.{id}`, `users.{id}` for multi-user sync
- ✅ Web Push API (PWA native notifications) for mobile alerts
- ✅ Dashboard banners (Inertia React) for web alerts
- ✅ Email escalation for critical alerts

**Proactive Alert Scenarios (Pre-RR Detection):**
- ✅ **Demurrage Countdown:** 1-second timer with Amber (60min) → Red (30min) → Critical (0min) alerts
- ✅ **Overload Detection:** Instant weighment vs. loader comparison (24-hour correction window)
- ✅ **RR Mismatch:** AI-powered auto-comparison of prediction vs. actual (>2% variance alert)
- ✅ **Stock Low Alerts:** Real-time threshold-based notifications (<25% of target)
- ✅ **Pending RR Upload:** Siding-level tracking with email escalation
- ✅ **Status Changes:** Event-driven notifications for rake lifecycle transitions

**AI Integration:** Predictive penalty calculation during loading (vs. post-RR discovery) = **24+ hours early detection**

### 3. AI Admin Database Chat System (Laravel AI SDK)

**Objective:** Enable admins to query all important operational data using natural language

**Core Components:**
- ✅ **AdminDatabaseAgent:** Multi-turn conversation agent with authorization enforcement
- ✅ **Query Tools:** QueryRakesTool, QueryStockTool, QueryPenaltiesTool, QueryPerformanceTool, QueryFinancialTool
- ✅ **Authorization:** Role-based data access (Admin/Operations/Finance/In-Charge)
- ✅ **Integration:** AI Assistant chat room in existing `chat_rooms` table via Reverb

**Natural Language Query Examples:**
- "Show me all rakes at demurrage risk right now"
- "Which loaders have the most overload issues?"
- "What's our current stock situation across all sidings?"
- "How much revenue did we earn this month?"
- "Compare loading efficiency last week vs this week"

**Financial Impact:** Eliminate manual MIS report generation → Save 2-3 hours/day per admin = **₹50-75K/month**

**Response Format:** Structured data with financial currency (₹), time format (24h), and actionable recommendations

### 4. Conversation Memory & Multi-Turn Chat

- ✅ **Automatic Persistence:** RemembersConversations trait tracks multi-turn conversations per user
- ✅ **Context Injection:** Previous messages inform current response
- ✅ **Authorization Per Query:** Each tool call enforces user permissions
- ✅ **Conversation Storage:** Reverb channels + database persistence

### 5. Data Models & Configuration

- Use existing tables (rakes, indents, coal_stock, rr_predictions, penalties, loaders, etc.) for context
- Laravel AI SDK configuration: `config/ai.php` with provider keys (OpenAI, Anthropic, Gemini, etc.)
- Failover strategy: Multi-provider support (auto-fallback if primary fails)
- No separate vector DB required for MVP; optional pgvector for embeddings later (Phase 2)
- Structured output schemas for JSON extraction from AI responses

## Current State Analysis

### Existing Features (this starter kit)

**Authentication & Authorization:**

- User model; Fortify (login, registration, 2FA, password reset)
- Spatie Laravel Permission (roles and permissions)
- Optional: organization membership (single default org for rake management)

**Admin Panel:**

- Filament for users, organizations, roles, permissions
- Spatie Media Library for file uploads

**State & Real-Time:**

- Spatie Laravel Model States (for Rake, Indent, VehicleUnload, etc.)
- Laravel Reverb + Echo; broadcasting configured

**Data & AI:**

- Laravel AI SDK; Spatie Media Library; Filament Excel for exports

**Frontend:**

- Inertia.js v2 + React 19; Wayfinder; Tailwind v4

**Quality:**

- Larastan, Pint, Pest

### What Needs to Be Built

**Core domain areas (to build in app/):**

1. Mine-to-Siding Road Dispatch (SHAReTrack integration or manual entry)
2. Transit Monitoring
3. User & Siding Context (user_siding, active_siding_id, gates/policies)
4. Siding Coal Receipt & Vehicle Unloading
5. Siding Stock Ledger
6. Rake Indent & Planning
7. Rake Placement & TXR Inspection
8. Unfit Wagon Management
9. Wagon-Wise Loading & Loader Data
10. Guard Inspection & Rake Movement
11. In-Motion Weighment
12. Loader vs Weighment Comparison Engine
13. Demurrage Monitoring & Timer Management (Reverb)
14. Railway Receipt (RR) Module + AI extraction
15. Reconciliation Engine
16. Penalty Management
17. Dashboards & MIS Reporting
18. **Chat** (rooms, messages, Reverb)
19. **AI** (RR extraction via Laravel AI; optional assistant)

**Database Tables (28 tables defined in PRD):**

**Master Data:**

- sidings (already exists conceptually via organizations, may need separate table)
- routes
- freight\_rate\_master
- vehicles (from SHAReTrack, read-only)
- vehicle\_coal\_site (from SHAReTrack, read-only)

**Transit:**

- vehicle\_trips (from SHAReTrack, read-only)
- gps\_tracking\_logs
- trip\_stoppages
- route\_deviations
- patrol\_reports

**Siding Operations:**

- vehicle\_unloads
- vehicle\_unload\_weighments
- coal\_stock
- indents

**Rake Operations:**

- rakes
- rake\_wagons
- rake\_loads
- rake\_load\_steps
- rake\_wagon\_loading
- rake\_weighments
- rake\_wagon\_weighments
- rake\_extra\_penalties

**RR Module:**

- rr\_documents
- rr\_extracted\_data
- rr\_predictions
- rr\_actuals
- rr\_wagon\_details
- rr\_additional\_charges

**State Machines:**

- Implement using Spatie Laravel Model States (and Actions) for:
- Rake lifecycle
- Indent lifecycle
- Vehicle unload lifecycle
- TXR lifecycle
- RR prediction lifecycle
- Penalty lifecycle

**User Interface:**

- Dashboard with live demurrage timers
- Truck unloading module
- Stock ledger view
- Indent management
- Rake management with wagon theatre view
- RR upload and reconciliation
- Penalty register
- MIS dashboards (siding-wise and management aggregated)
- 10 report formats with export

## Implementation Phases (Mobile-First Approach)

### Phase 0: Mobile Infrastructure & Foundation (Week 1)

**Objective:** Build offline-first PWA infrastructure before core features

**Modules:**

1. **Database Migrations:** All 28 tables (optimized for mobile query patterns)
2. **Offline-First Architecture:**
   - ServiceWorker setup (install, activate, fetch strategies)
   - IndexedDB schema for forms, cache, sync queue
   - Background sync API implementation
   - Conflict resolution logic (server-wins)
3. **Mobile UI Component Library:**
   - Bottom navigation bar (Inertia component)
   - NumPad component (number input)
   - Wagon theatre grid (visual wagon selector)
   - Floating action button with context menu
   - Timer widget (1-second updates, local fallback)
   - Barcode scanner component (ZXing.js)
   - Photo camera component
4. **Responsive Layout System:**
   - Mobile (320-639px), Tablet (640-1023px), Desktop (1024px+) breakpoints
   - Safe area padding for notch/dynamic island
5. **Multi-Tenancy & User Context:**
   - Siding context management (user_siding, active_siding_id)
   - Role-based access control (Operator, In-Charge, Management, Finance)
   - Form validation framework (client-side + server-side)

**Deliverables:**

- ✅ IndexedDB + ServiceWorker functional (DevTools verified)
- ✅ Mobile responsive UI kit in Storybook
- ✅ QR scanner working (offline, no API calls)
- ✅ Photo upload working (compression optimized)
- ✅ Offline form submission queued
- ✅ Demurrage timer with local countdown fallback
- ✅ Database schema complete

### Phase 1: Core Mobile Workflows (Weeks 2-3)

**Objective:** Digitize daily field operations; eliminate Excel for core processes

**Modules:**

6. **Vehicle Arrival (Mobile-First):** Scan vehicle QR → Mine weight (NumPad) → [Confirm] (30 sec workflow)
7. **Truck Unloading:** Auto-timing, weighment entry (large inputs), tare validation
8. **Stock Ledger (Mobile Display):** Real-time balance, receipt/dispatch summary, low-stock alerts
9. **Quick Indent Creation:** Bottom sheet modal, target qty, auto-validate against stock
10. **Mobile Real-Time Timer:** Floating demurrage widget, 1-second updates, color-coded (green/yellow/red)

**Deliverables:**

- ✅ Vehicle arrival form fully offline (data queued)
- ✅ Stock ledger real-time on mobile
- ✅ Demurrage timer on dashboard (1-sec updates)
- ✅ 80% fewer manual Excel entries
- ✅ Tests: Offline scenarios, poor network, no internet

### Phase 2: Rake & Loading Workflows (Weeks 3-4)

**Objective:** Real-time loading tracking + overload prevention

**Modules:**

11. **TXR Inspection:** Rake placement, TXR timer, photo capture of unfit wagons (auto-tagged)
12. **Wagon Theatre UI:** 60-wagon grid (tap → load), color-coded (red: unfit, blue: pending, green: loaded)
13. **Wagon Loading Workflow:** Select wagon → Loader (button toggle) → Qty (slider) → [Save & Next] (<10 sec/wagon)
14. **Guard Inspection:** Quick form [Approved] (1 sec)
15. **In-Motion Weighment:** Photo slip → OCR auto-fills → Comparison grid (loader vs weighment) → Overload flag

**Deliverables:**

- ✅ Wagon theatre grid (responsive, touch-friendly)
- ✅ Unfit wagon documentation (photos + auto-tags)
- ✅ Loading entry <10 sec per wagon
- ✅ Weighment comparison visual (instant overload detection)
- ✅ Real-time overload alerts (no RR wait)
- ✅ Tests: Multi-user loading sync, offline queue

### Phase 3: RR & Reconciliation (Weeks 4-5)

**Objective:** Automated RR processing + real-time financial visibility

**Modules:**

16. **RR Photo Upload (Mobile):** Photo slip → OCR extracts data → Pre-fill form (5 min vs 1+ hour)
17. **RR Extraction & Verification:** Extracted data display, confidence scores, field-by-field edits
18. **Five-Point Reconciliation Engine:** Auto-compare Mine→Siding→Rake→Weighment→RR; alerts on mismatch
19. **Penalty Dashboard:** Predicted (pre-RR), confirmed (post-RR), pending; track per rake

**Deliverables:**

- ✅ RR photo upload (mobile camera)
- ✅ OCR field extraction (confidence >85% auto-confirms)
- ✅ Reconciliation automated (instant alerts)
- ✅ Penalty tracking real-time
- ✅ No manual Excel entry
- ✅ Tests: Multi-page RR photos, OCR accuracy, edge cases

### Phase 4: Dashboards, Reports & Launch (Weeks 5-6)

**Objective:** Mobile-optimized production-ready system

**Modules:**

20. **Mobile Dashboards:** Siding operator (3 active rakes), In-Charge (multi-site cards), Finance (reconciliation)
21. **MIS Reports:** 10 report formats (Mobile-optimized grid view + export)
22. **Mobile UX Polish:** Button sizes (56px+), font sizes (16pt+), haptic feedback, gesture support
23. **Offline & Sync Testing:** Complete rake lifecycle offline, sync when online, no data loss
24. **Performance Optimization:** Bundle <2MB, FCP <2s (3G), Lighthouse >85
25. **Field Testing (UAT):** 1-week pilot at Pakur siding (operator feedback, network testing)
26. **Training & Documentation:** Mobile user guide, video walkthroughs, offline troubleshooting

**Deliverables:**

- ✅ Production PWA (installable iOS/Android)
- ✅ All tests passing (unit, integration, E2E)
- ✅ <30s cold start time
- ✅ Offline workflows fully functional
- ✅ Lighthouse mobile ≥85
- ✅ Zero data loss in UAT
- ✅ Team trained on 3 core workflows
- ✅ Field operators comfortable with mobile UI

**Go-Live Criteria:**
- ✅ Mobile performance: Load <2s (3G), bundle <2MB (gzip)
- ✅ Offline-first: All forms work offline, sync on return
- ✅ Data integrity: Zero data loss in UAT
- ✅ Team readiness: Trained on core workflows (truck arrival, loading, weighment)
- ✅ Operational impact: 80% reduction in manual data entry time per rake

## Mobile-First Development Guidelines

### Design Principles
- **Mobile-First:** Design for 320px width first, then enhance for tablet/desktop
- **Touch-First:** No hover states; use tap/long-press/swipe instead
- **Form-First:** Quick-entry modals as primary UI, not full pages
- **Offline-First:** All forms work offline; validation happens client-side + server
- **Fast:** Target <10 seconds per form submission (including barcode scan)

### Form Design Specifications (Real-World Examples)

#### Vehicle Arrival Form (30 seconds target)
```
1. Barcode scanner (vehicle QR) → Auto-fill vehicle #, fetch tare
2. Mine weight (NumPad, large buttons)
3. [Confirm] → Queue locally, sync when online
Touch targets: 56px+ | Font: 16pt+ | No dropdowns (touch-unfriendly)
```

#### Wagon Loading Form (<10 seconds per wagon)
```
Wagon Theatre: Visual 60-wagon grid
- Red = unfit (skip), Blue = pending, Green = loaded ✓
Tap wagon → Loader (button toggle, 3-5 active loaders)
         → Quantity (slider or NumPad, 0-100% capacity)
         → [Save & Next] (auto-advance)
Real-time validation: Overload flag = Red warning
All entries queued offline
```

#### Weighment Form (5 minutes target)
```
1. Photo weighment slip → OCR auto-fills wagon data
2. Train speed (NumPad)
3. Wagon comparison grid: Loader Qty | Weighment Qty | Diff | Status
   - Green = match (±5%), Orange = slight overload, Red = major overload
4. [Confirm] or [Retry]
80% automation via OCR
```

### Mobile Navigation
- **Bottom Tab Bar:** Always visible (mobile only); Dash | Trucks | Rakes | RR | Menu
- **Floating Action Button:** Context-aware quick actions (truck arrived → [Check Arrival], rake loading → [Enter Qty])
- **Swipe Gestures:** Left/right = navigate rakes, Up = show menu, Down = pull-to-refresh
- **No Sidebar:** Desktop gets sidebar; mobile gets bottom tabs

### Real-Time Updates (Mobile Network)
- **WebSocket:** Reverb via Laravel Echo (1-second updates when online)
- **Fallback:** 5-second polling if WebSocket drops
- **Offline:** Local timers continue counting down; reconcile on reconnect
- **UI Indicator:** 🟢 Online (green) | 🟡 Syncing (spinner) | 🔴 Offline (red)

### Barcode/QR Scanning
- **Auto-detect:** No manual "capture" button; auto-capture on frame
- **Offline:** Scanning is local (ZXing.js, no API call)
- **Vibrate:** Haptic feedback on detection
- **Fallback:** Manual text entry always available
- **Scannable items:** Vehicle (license plate), Wagon (painted QR), RR (document QR), Loader ID

### Photo Handling
- **Compression:** On-device JPEG optimization (80% quality, ~500KB per image)
- **Storage:** Compressed in IndexedDB, original deleted after upload
- **Auto-tagging:** Timestamp, GPS location (if available), metadata
- **Upload:** When online via background sync
- **Use cases:** TXR unfit wagons, RR document, loader performance

### Offline Form Submission Queue
1. User fills form (validates client-side)
2. Tap [Submit] → Queued to IndexedDB
3. UI shows: "📱 Saved locally" (gray indicator)
4. Network returns → ServiceWorker auto-syncs
5. On success: "✓ Synced" (green checkmark), remove from queue
6. On error: "⚠️ Failed" (red), user can [Retry]

### Data Size & Performance (Mobile)
- **Per-rake data:** 3-5 KB (165 rakes = 660 KB total)
- **Cache targets:** Vehicle master <50MB, IndexedDB <100MB
- **Bundle size:** <2MB gzipped
- **Load time:** <2 seconds on 3G network
- **First meaningful paint:** <3 seconds
- **Timer updates:** 1/second (local fallback if offline)
- **Form validation:** <100ms client-side

## Database Schema (All 28 Tables)

**Migration location:** `database/migrations/`

**Global Constraints:**

- All tables include `siding_id` for multi-tenancy enforcement
- All tables include `created_by` and `updated_by` columns (via Userstamps trait)
- All tables include `created_at` and `updated_at` timestamps
- All tables include `deleted_at` for soft deletes (except RR prediction/actuals - immutable)
- All tables use PostgreSQL UUID or bigInteger for primary keys (specify per table)
- Indexes defined for performance-critical queries
- Foreign keys defined with cascade actions
- All ENUM columns specify exact values

### Master Data Tables

#### Table: sidings

**Purpose:** Define railway sidings with organization mapping
**Migration File:** `YYYY_MM_DD_HHMMSS_create_sidings_table.php`

```php
Schema::create('sidings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('organization_id')->constrained()->onDelete('cascade');
    $table->string('name'); // e.g., "Pakur Siding"
    $table->string('code', 10)->unique(); // PKUR, DUMK, KURWA
    $table->string('location'); // "Pakur, Jharkhand"
    $table->string('station_code', 10); // Railway station code
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();

    $table->index(['organization_id', 'is_active']);
    $table->index('code');
});
```

#### Table: routes

**Purpose:** Define approved vehicle routes from mine to siding
**Migration File:** `YYYY_MM_DD_HHMMSS_create_routes_table.php`

```php
Schema::create('routes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('siding_id')->constrained('sidings')->onDelete('cascade');
    $table->string('route_name');
    $table->string('start_location');
    $table->string('end_location');
    $table->decimal('expected_distance_km', 10, 2);
    $table->longText('geo_json_path_data'); // Geofencing data for GPS tracking
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();

    $table->index(['siding_id', 'is_active']);
});
```

#### Table: freight\_rate\_master

**Purpose:** Store railway freight rates for RR prediction calculations
**Migration File:** `YYYY_MM_DD_HHMMSS_create_freight_rate_master_table.php`

```php
Schema::create('freight_rate_master', function (Blueprint $table) {
    $table->id();
    $table->string('commodity_code', 20); // e.g., "COAL-BOBRN"
    $table->string('commodity_name');
    $table->string('class_code', 20); // e.g., "145A"
    $table->string('risk_rate', 50)->nullable();
    $table->decimal('distance_from_km', 10, 2);
    $table->decimal('distance_to_km', 10, 2);
    $table->decimal('rate_per_mt', 10, 2); // Freight rate per MT
    $table->decimal('gst_percent', 5, 2)->default(5.00);
    $table->date('effective_from');
    $table->date('effective_to')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();

    $table->index(['commodity_code', 'is_active']);
    $table->index(['class_code', 'is_active']);
});
```

#### Table: vehicles

**Purpose:** Store vehicle master data (read-only, from SHAReTrack)
**Migration File:** `YYYY_MM_DD_HHMMSS_create_vehicles_table.php`

```php
Schema::create('vehicles', function (Blueprint $table) {
    $table->id();
    $table->string('vehicle_number', 20)->unique();
    $table->string('rfid_tag', 50)->nullable()->unique();
    $table->decimal('permitted_capacity_mt', 10, 2);
    $table->decimal('tare_weight_mt', 10, 2);
    $table->string('owner_name', 100);
    $table->string('vehicle_type', 50); // e.g., "Tipper", "Truck"
    $table->string('gps_device_id', 50)->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    $table->index('vehicle_number');
    $table->index('rfid_tag');
});
```

#### Table: vehicle\_coal\_site

**Purpose:** Link vehicles to coal sites (read-only, from SHAReTrack)
**Migration File:** `YYYY_MM_DD_HHMMSS_create_vehicle_coal_site_table.php`

```php
Schema::create('vehicle_coal_site', function (Blueprint $table) {
    $table->id();
    $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
    $table->string('site_name', 100);
    $table->string('site_code', 20);
    $table->timestamps();

    $table->unique(['vehicle_id', 'site_code']);
});
```

### Transit Monitoring Tables

#### Table: vehicle\_trips

**Purpose:** Track vehicle dispatch trips (read-only, from SHAReTrack)
**Migration File:** `YYYY_MM_DD_HHMMSS_create_vehicle_trips_table.php`

```php
Schema::create('vehicle_trips', function (Blueprint $table) {
    $table->id();
    $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
    $table->foreignId('route_id')->nullable()->constrained('routes')->onDelete('set null');
    $table->foreignId('siding_id')->constrained('sidings')->onDelete('cascade');
    $table->string('challan_number', 50)->unique();
    $table->dateTime('dispatch_time');
    $table->enum('trip_status', ['DISPATCHED', 'IN_TRANSIT', 'ARRIVED', 'CANCELLED'])->default('DISPATCHED');
    $table->timestamps();

    $table->index(['vehicle_id', 'dispatch_time']);
    $table->index(['siding_id', 'dispatch_time']);
    $table->index('challan_number');
});
```

#### Table: gps\_tracking\_logs

**Purpose:** Store real-time GPS tracking data for vehicles
**Migration File:** `YYYY_MM_DD_HHMMSS_create_gps_tracking_logs_table.php`

```php
Schema::create('gps_tracking_logs', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->foreignId('vehicle_trip_id')->constrained('vehicle_trips')->onDelete('cascade');
    $table->decimal('latitude', 10, 8);
    $table->decimal('longitude', 11, 8);
    $table->decimal('speed_kmph', 10, 2);
    $table->boolean('is_on_route')->default(true);
    $table->decimal('distance_from_route_km', 10, 2)->default(0);
    $table->timestamp('logged_at');
    $table->timestamps();

    $table->index(['vehicle_trip_id', 'logged_at']);
});
```

#### Table: trip\_stoppages

**Purpose:** Track vehicle stoppage events during transit
**Migration File:** `YYYY_MM_DD_HHMMSS_create_trip_stoppages_table.php`

```php
Schema::create('trip_stoppages', function (Blueprint $table) {
    $table->id();
    $table->foreignId('vehicle_trip_id')->constrained('vehicle_trips')->onDelete('cascade');
    $table->timestamp('stoppage_start_time');
    $table->timestamp('stoppage_end_time')->nullable();
    $table->decimal('duration_minutes', 10, 2)->nullable();
    $table->decimal('latitude', 10, 8);
    $table->decimal('longitude', 11, 8);
    $table->string('reason', 255)->nullable();
    $table->timestamps();

    $table->index(['vehicle_trip_id', 'stoppage_start_time']);
});
```

#### Table: route\_deviations

**Purpose:** Track route deviation events
**Migration File:** `YYYY_MM_DD_HHMMSS_create_route_deviations_table.php`

```php
Schema::create('route_deviations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('vehicle_trip_id')->constrained('vehicle_trips')->onDelete('cascade');
    $table->timestamp('deviation_start_time');
    $table->timestamp('deviation_end_time')->nullable();
    $table->decimal('duration_minutes', 10, 2)->nullable();
    $table->decimal('total_deviation_km', 10, 2)->default(0);
    $table->timestamps();

    $table->index(['vehicle_trip_id', 'deviation_start_time']);
});
```

#### Table: patrol\_reports

**Purpose:** Record patrol inspection reports for transit security
**Migration File:** `YYYY_MM_DD_HHMMSS_create_patrol_reports_table.php`

```php
Schema::create('patrol_reports', function (Blueprint $table) {
    $table->id();
    $table->foreignId('vehicle_trip_id')->constrained('vehicle_trips')->onDelete('cascade');
    $table->timestamp('patrol_time');
    $table->enum('material_condition', ['GOOD', 'DAMAGED', 'COMPROMISED'])->default('GOOD');
    $table->boolean('pilferage_found')->default(false);
    $table->text('remarks')->nullable();
    $table->foreignId('patrolled_by')->constrained('users')->onDelete('cascade');
    $table->timestamps();

    $table->index(['vehicle_trip_id', 'patrol_time']);
});
```

### Siding Operations Tables

#### Table: vehicle\_unloads

**Purpose:** Track vehicle unloading operations at siding
**Migration File:** `YYYY_MM_DD_HHMMSS_create_vehicle_unloads_table.php`

```php
Schema::create('vehicle_unloads', function (Blueprint $table) {
    $table->id();
    $table->foreignId('vehicle_trip_id')->constrained('vehicle_trips')->onDelete('cascade');
    $table->foreignId('siding_id')->constrained('sidings')->onDelete('cascade');
    $table->string('unload_coal_id', 50)->unique(); // Unique identifier for this unload
    $table->timestamp('arrival_time');
    $table->enum('unload_status', [
        'ARRIVED',
        'UNDER_VALIDATION',
        'READY_FOR_UNLOAD',
        'UNLOADING',
        'TARE_VALIDATION',
        'COMPLETED',
        'CANCELLED'
    ])->default('ARRIVED');
    $table->decimal('mine_net_weight_mt', 10, 2); // From SHAReTrack
    $table->decimal('final_net_weight_mt', 10, 2)->nullable(); // After unloading
    $table->decimal('tare_difference_mt', 10, 2)->nullable();
    $table->boolean('stock_added')->default(false);
    $table->foreignId('created_by')->constrained('users');
    $table->foreignId('updated_by')->nullable()->constrained('users');
    $table->timestamps();
    $table->softDeletes();

    $table->index(['siding_id', 'arrival_time']);
    $table->index('unload_status');
    $table->index('unload_coal_id');
});
```

#### Table: vehicle\_unload\_weighments

**Purpose:** Store weighment data for vehicle unloading
**Migration File:** `YYYY_MM_DD_HHMMSS_create_vehicle_unload_weighments_table.php`

```php
Schema::create('vehicle_unload_weighments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('vehicle_unload_id')->constrained('vehicle_unloads')->onDelete('cascade');
    $table->enum('weighment_type', ['ARRIVAL_CHECK', 'RECHECK', 'TARE_VALIDATION']);
    $table->decimal('gross_weight_mt', 10, 2);
    $table->decimal('tare_weight_mt', 10, 2);
    $table->decimal('net_weight_mt', 10, 2);
    $table->boolean('is_passed')->default(true);
    $table->text('remarks')->nullable();
    $table->timestamp('weighment_time');
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();

    $table->index(['vehicle_unload_id', 'weighment_type']);
});
```

#### Table: coal\_stock

**Purpose:** Append-only ledger for siding coal stock
**Migration File:** `YYYY_MM_DD_HHMMSS_create_coal_stock_table.php`

```php
Schema::create('coal_stock', function (Blueprint $table) {
    $table->id();
    $table->foreignId('siding_id')->constrained('sidings')->onDelete('cascade');
    $table->foreignId('vehicle_unload_id')->nullable()->constrained('vehicle_unloads')->onDelete('set null');
    $table->foreignId('indent_id')->nullable()->constrained('indents')->onDelete('set null');
    $table->enum('transaction_type', ['RECEIPT', 'DISPATCH']);
    $table->decimal('quantity_mt', 10, 2);
    $table->decimal('running_balance_mt', 10, 2);
    $table->text('reference')->nullable(); // "Vehicle UNL-123" or "Indent IND-456"
    $table->timestamp('transaction_time');
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();

    $table->index(['siding_id', 'transaction_time']);
    $table->index('vehicle_unload_id');
    $table->index('indent_id');
});
```

#### Table: indents

**Purpose:** Manage rake indent requests
**Migration File:** `YYYY_MM_DD_HHMMSS_create_indents_table.php`

```php
Schema::create('indents', function (Blueprint $table) {
    $table->id();
    $table->foreignId('siding_id')->constrained('sidings')->onDelete('cascade');
    $table->string('indent_number', 50)->unique();
    $table->date('indent_date');
    $table->time('indent_time');
    $table->decimal('target_quantity_mt', 10, 2);
    $table->decimal('available_quantity_mt', 10, 2); // Snapshot at creation
    $table->enum('indent_status', [
        'RAISED',
        'APPROVAL_PENDING',
        'APPROVED',
        'REJECTED',
        'CANCELLED',
        'RAKE_ARRIVED',
        'RAKE_CREATED',
        'CLOSED'
    ])->default('RAISED');
    $table->string('railway_reference_number', 50)->nullable();
    $table->timestamp('railway_approval_time')->nullable();
    $table->foreignId('created_by')->constrained('users');
    $table->foreignId('updated_by')->nullable()->constrained('users');
    $table->timestamps();
    $table->softDeletes();

    $table->index(['siding_id', 'indent_date']);
    $table->index('indent_status');
    $table->index('indent_number');
});
```

### Rake Operations Tables

#### Table: rakes

**Purpose:** Track rake operations and demurrage
**Migration File:** `YYYY_MM_DD_HHMMSS_create_rakes_table.php`

```php
Schema::create('rakes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('siding_id')->constrained('sidings')->onDelete('cascade');
    $table->foreignId('indent_id')->nullable()->constrained('indents')->onDelete('set null');
    $table->string('rake_number', 50)->unique();
    $table->string('destination_station', 50); // e.g., "PSPM", "BTPC"
    $table->decimal('distance_km', 10, 2);
    $table->integer('number_of_wagons');
    $table->string('railway_register_number', 50)->nullable();
    $table->integer('priority_number')->nullable();
    $table->enum('rake_status', [
        'CREATED',
        'ARRIVED',
        'TXR_PENDING',
        'TXR_IN_PROGRESS',
        'TXR_COMPLETED',
        'DISPATCHED',
        'LOADING',
        'GUARD_INSPECTION',
        'MOVEMENT_PERMISSION',
        'WEIGHMENT_IN_PROGRESS',
        'READY_FOR_FINAL_DISPATCH',
        'COMPLETED',
        'CANCELLED'
    ])->default('CREATED');
    $table->timestamp('placement_time')->nullable(); // When rake placed at siding - timer starts
    $table->timestamp('txr_start_time')->nullable();
    $table->timestamp('txr_end_time')->nullable();
    $table->timestamp('loading_start_time')->nullable();
    $table->timestamp('loading_end_time')->nullable();
    $table->timestamp('weighment_time')->nullable();
    $table->timestamp('dispatch_time')->nullable();
    $table->integer('total_elapsed_minutes')->default(0); // Cumulative time including corrections
    $table->integer('free_time_minutes')->default(180); // 3 hours
    $table->integer('extra_minutes')->default(0); // Beyond free time
    $table->integer('penalty_hours')->default(0); // CEIL(extra_minutes / 60)
    $table->decimal('demurrage_amount', 12, 2)->default(0);
    $table->decimal('rate_per_hour', 10, 2)->default(15440.00); // Base rate before GST
    $table->decimal('gst_percent', 5, 2)->default(5.00);
    $table->enum('penalty_status', ['PREDICTED', 'CONFIRMED', 'WAIVED'])->default('PREDICTED');
    $table->foreignId('created_by')->constrained('users');
    $table->foreignId('updated_by')->nullable()->constrained('users');
    $table->timestamps();
    $table->softDeletes();

    $table->index(['siding_id', 'rake_status']);
    $table->index('rake_number');
    $table->index('placement_time');
    $table->index(['rake_status', 'placement_time']);
});
```

#### Table: rake\_wagons

**Purpose:** Track wagons within a rake
**Migration File:** `YYYY_MM_DD_HHMMSS_create_rake_wagons_table.php`

```php
Schema::create('rake_wagons', function (Blueprint $table) {
    $table->id();
    $table->foreignId('rake_id')->constrained('rakes')->onDelete('cascade');
    $table->string('wagon_number', 50);
    $table->string('wagon_type', 20); // BOXN, BOBRN, BOBRM1, etc.
    $table->decimal('tare_wagon_weight_mt', 10, 2);
    $table->decimal('max_capacity_mt', 10, 2); // Permissible Carrying Capacity (PCC)
    $table->boolean('is_fit')->default(true);
    $table->string('txr_memo_reference', 50)->nullable(); // For unfit wagons
    $table->enum('marker', ['FLAG', 'RADIUM_REFLECTOR', 'RED_LIGHT'])->nullable(); // Marking method
    $table->decimal('loaded_quantity_mt', 10, 2)->nullable();
    $table->decimal('overload_amount_mt', 10, 2)->default(0);
    $table->foreignId('created_by')->constrained('users');
    $table->foreignId('updated_by')->nullable()->constrained('users');
    $table->timestamps();

    $table->unique(['rake_id', 'wagon_number']);
    $table->index(['rake_id', 'is_fit']);
});
```

#### Table: rake\_loads

**Purpose:** Track rake load operations
**Migration File:** `YYYY_MM_DD_HHMMSS_create_rake_loads_table.php`

```php
Schema::create('rake_loads', function (Blueprint $table) {
    $table->id();
    $table->foreignId('rake_id')->constrained('rakes')->onDelete('cascade');
    $table->foreignId('siding_id')->constrained('sidings')->onDelete('cascade');
    $table->decimal('total_loaded_mt', 10, 2);
    $table->decimal('actual_weighed_mt', 10, 2)->nullable();
    $table->timestamp('load_start_time')->nullable();
    $table->timestamp('load_end_time')->nullable();
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
});
```

#### Table: rake\_load\_steps

**Purpose:** Track operational steps in rake loading
**Migration File:** `YYYY_MM_DD_HHMMSS_create_rake_load_steps_table.php`

```php
Schema::create('rake_load_steps', function (Blueprint $table) {
    $table->id();
    $table->foreignId('rake_id')->constrained('rakes')->onDelete('cascade');
    $table->foreignId('rake_load_id')->nullable()->constrained('rake_loads')->onDelete('set null');
    $table->enum('step_type', [
        'RAKE_PLACEMENT',
        'WAGON_LOADING',
        'GUARD_INSPECTION',
        'MOVEMENT_PERMISSION',
        'INMOTION_WEIGHMENT',
        'RELOAD_REQUIRED'
    ]);
    $table->timestamp('step_time');
    $table->text('remarks')->nullable();
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();

    $table->index(['rake_id', 'step_type', 'step_time']);
});
```

#### Table: rake\_wagon\_loading

**Purpose:** Append-only history of wagon loading attempts
**Migration File:** `YYYY_MM_DD_HHMMSS_create_rake_wagon_loading_table.php`

```php
Schema::create('rake_wagon_loading', function (Blueprint $table) {
    $table->id();
    $table->foreignId('rake_id')->constrained('rakes')->onDelete('cascade');
    $table->foreignId('rake_wagon_id')->constrained('rake_wagons')->onDelete('cascade');
    $table->foreignId('siding_id')->constrained('sidings')->onDelete('cascade');
    $table->foreignId('loader_id')->nullable()->constrained('loaders')->onDelete('set null');
    $table->foreignId('loader_operator_id')->nullable()->constrained('users')->onDelete('set null');
    $table->decimal('cc_capacity_mt', 10, 2); // Reference capacity from wagon
    $table->decimal('loaded_quantity_mt', 10, 2);
    $table->timestamp('loading_time');
    $table->boolean('is_correction')->default(false); // True if this is a reload after overload
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();

    $table->index(['rake_id', 'loading_time']);
    $table->index('loader_id');
});
```

#### Table: loaders

**Purpose:** Master table for loader machines
**Migration File:** `YYYY_MM_DD_HHMMSS_create_loaders_table.php`

```php
Schema::create('loaders', function (Blueprint $table) {
    $table->id();
    $table->foreignId('siding_id')->constrained('sidings')->onDelete('cascade');
    $table->string('loader_number', 50)->unique();
    $table->string('loader_name', 100);
    $table->string('loader_type', 50); // e.g., "Excavator", "Loader"
    $table->string('manufacturer', 100)->nullable();
    $table->string('model', 100)->nullable();
    $table->decimal('bucket_capacity_mt', 10, 2)->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();

    $table->index(['siding_id', 'is_active']);
});
```

#### Table: rake\_weighments

**Purpose:** Track in-motion weighment attempts
**Migration File:** `YYYY_MM_DD_HHMMSS_create_rake_weighments_table.php`

```php
Schema::create('rake_weighments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('rake_id')->constrained('rakes')->onDelete('cascade');
    $table->foreignId('siding_id')->constrained('sidings')->onDelete('cascade');
    $table->integer('attempt_number');
    $table->decimal('train_speed_kmph', 10, 2);
    $table->enum('weighment_status', ['PASS', 'FAIL_SPEED', 'FAIL_OVERLOAD']);
    $table->timestamp('weighment_time');
    $table->string('slip_number', 50)->nullable();
    $table->decimal('total_weight_mt', 10, 2)->nullable();
    $table->text('remarks')->nullable();
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();

    $table->unique(['rake_id', 'attempt_number']);
    $table->index(['rake_id', 'weighment_time']);
});
```

#### Table: rake\_wagon\_weighments

**Purpose:** Per-wagon weighment data
**Migration File:** `YYYY_MM_DD_HHMMSS_create_rake_wagon_weighments_table.php`

```php
Schema::create('rake_wagon_weighments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('rake_weighment_id')->constrained('rake_weighments')->onDelete('cascade');
    $table->foreignId('rake_wagon_id')->constrained('rake_wagons')->onDelete('cascade');
    $table->foreignId('siding_id')->constrained('sidings')->onDelete('cascade');
    $table->decimal('gross_weight_mt', 10, 2);
    $table->decimal('tare_weight_mt', 10, 2);
    $table->decimal('net_weight_mt', 10, 2);
    $table->decimal('overload_amount_mt', 10, 2)->default(0);
    $table->boolean('is_overloaded')->default(false);
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();

    $table->index(['rake_weighment_id', 'rake_wagon_id']);
});
```

#### Table: rake\_extra\_penalties

**Purpose:** Track additional penalties (demurrage, weighment charge, etc.)
**Migration File:** `YYYY_MM_DD_HHMMSS_create_rake_extra_penalties_table.php`

```php
Schema::create('rake_extra_penalties', function (Blueprint $table) {
    $table->id();
    $table->foreignId('rake_id')->constrained('rakes')->onDelete('cascade');
    $table->foreignId('siding_id')->constrained('sidings')->onDelete('cascade');
    $table->enum('penalty_type', ['DEMURRAGE', 'WEIGHMENT_CHARGE', 'SHUNTING', 'PILOT', 'TRANSIT_LOSS', 'IMWB_PENALTY', 'DETENTION_CHARGE']);
    $table->string('penalty_code', 20)->nullable(); // DEM, POL1, POLA, etc.
    $table->text('penalty_reason')->nullable();
    $table->decimal('quantity', 10, 2)->nullable(); // Hours, MT, etc.
    $table->decimal('rate_per_unit', 10, 2)->nullable();
    $table->decimal('penalty_amount', 12, 2);
    $table->decimal('gst_amount', 12, 2)->default(0);
    $table->decimal('total_amount', 12, 2);
    $table->enum('penalty_status', ['PREDICTED', 'CONFIRMED', 'WAIVED'])->default('PREDICTED');
    $table->boolean('is_paid')->default(false);
    $table->timestamp('payment_date')->nullable();
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();

    $table->index(['rake_id', 'penalty_type']);
    $table->index('penalty_status');
});
```

### Railway Receipt (RR) Tables

#### Table: rr\_documents

**Purpose:** Store uploaded RR PDF documents
**Migration File:** `YYYY_MM_DD_HHMMSS_create_rr_documents_table.php`

```php
Schema::create('rr_documents', function (Blueprint $table) {
    $table->id();
    $table->foreignId('rake_id')->nullable()->constrained('rakes')->onDelete('set null');
    $table->foreignId('siding_id')->constrained('sidings')->onDelete('cascade');
    $table->string('rr_number', 50)->unique();
    $table->date('rr_date');
    $table->string('file_path'); // Media Library file path
    $table->longText('raw_text')->nullable(); // OCR extracted text
    $table->boolean('ai_processed')->default(false);
    $table->boolean('manually_verified')->default(false);
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
    $table->softDeletes();

    $table->index(['siding_id', 'rr_date']);
    $table->index('rr_number');
});
```

#### Table: rr\_extracted\_data

**Purpose:** Store OCR/AI extracted RR data
**Migration File:** `YYYY_MM_DD_HHMMSS_create_rr_extracted_data_table.php`

```php
Schema::create('rr_extracted_data', function (Blueprint $table) {
    $table->id();
    $table->foreignId('rr_document_id')->constrained('rr_documents')->onDelete('cascade');
    $table->string('fnr_reference', 50)->nullable();
    $table->string('station_from', 50)->nullable();
    $table->string('station_to', 50)->nullable();
    $table->string('consignor', 100)->nullable();
    $table->string('consignee', 100)->nullable();
    $table->string('commodity', 100)->nullable();
    $table->integer('wagons')->nullable();
    $table->string('class_code', 20)->nullable();
    $table->decimal('distance_km', 10, 2)->nullable();
    $table->decimal('rate_per_mt', 10, 2)->nullable();
    $table->decimal('sender_weight_mt', 10, 2)->nullable();
    $table->decimal('actual_weight_mt', 10, 2)->nullable();
    $table->decimal('chargeable_weight_mt', 10, 2)->nullable();
    $table->decimal('overweight_mt', 10, 2)->default(0);
    $table->decimal('freight_amount', 12, 2)->nullable();
    $table->decimal('other_charges_amount', 12, 2)->default(0);
    $table->decimal('gst_amount', 12, 2)->default(0);
    $table->decimal('total_amount', 12, 2)->nullable();
    $table->string('invoice_number', 50)->nullable();
    $table->date('invoice_date')->nullable();
    $table->string('hsn_code', 20)->nullable();
    $table->string('charged_via', 50)->nullable();
    $table->enum('extraction_status', ['DRAFT', 'CONFIRMED', 'FAILED'])->default('DRAFT');
    $table->float('confidence_score')->nullable(); // AI confidence
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();

    $table->unique('rr_document_id');
});
```

#### Table: rr\_predictions

**Purpose:** Store system-calculated RR predictions (immutable)
**Migration File:** `YYYY_MM_DD_HHMMSS_create_rr_predictions_table.php`

```php
Schema::create('rr_predictions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('rake_id')->constrained('rakes')->onDelete('cascade');
    $table->foreignId('siding_id')->constrained('sidings')->onDelete('cascade');
    $table->enum('prediction_status', ['DRAFT', 'FINALIZED', 'CONFIRMED'])->default('DRAFT');
    $table->decimal('chargeable_weight_mt', 10, 2);
    $table->decimal('distance_km', 10, 2);
    $table->decimal('rate_per_mt', 10, 2);
    $table->decimal('gst_percent', 5, 2);
    $table->decimal('freight_amount', 12, 2);
    $table->decimal('demurrage_amount', 12, 2)->default(0);
    $table->decimal('pol1_penalty', 12, 2)->default(0);
    $table->decimal('pola_penalty', 12, 2)->default(0);
    $table->decimal('gst_amount', 12, 2);
    $table->decimal('total_amount', 12, 2);
    $table->timestamp('calculated_at');
    $table->foreignId('calculated_by')->constrained('users');
    $table->timestamps(); // No soft deletes - immutable record

    $table->index(['rake_id', 'prediction_status']);
});
```

#### Table: rr\_actuals

**Purpose:** Store manually verified RR data (immutable)
**Migration File:** `YYYY_MM_DD_HHMMSS_create_rr_actuals_table.php`

```php
Schema::create('rr_actuals', function (Blueprint $table) {
    $table->id();
    $table->foreignId('rr_document_id')->constrained('rr_documents')->onDelete('cascade');
    $table->foreignId('rake_id')->nullable()->constrained('rakes')->onDelete('set null');
    $table->foreignId('siding_id')->constrained('sidings')->onDelete('cascade');
    $table->string('rr_number', 50)->unique();
    $table->date('rr_date');
    $table->string('fnr_reference', 50)->nullable();
    $table->string('station_from', 50);
    $table->string('station_to', 50);
    $table->string('consignor', 100);
    $table->string('consignee', 100);
    $table->string('commodity', 100);
    $table->integer('wagons');
    $table->string('class_code', 20);
    $table->decimal('distance_km', 10, 2);
    $table->decimal('rate_per_mt', 10, 2);
    $table->decimal('sender_weight_mt', 10, 2)->nullable();
    $table->decimal('actual_weight_mt', 10, 2);
    $table->decimal('chargeable_weight_mt', 10, 2);
    $table->decimal('overweight_mt', 10, 2)->default(0);
    $table->decimal('freight_amount', 12, 2);
    $table->decimal('other_charges_amount', 12, 2)->default(0);
    $table->decimal('gst_amount', 12, 2);
    $table->decimal('total_amount', 12, 2);
    $table->string('invoice_number', 50)->nullable();
    $table->date('invoice_date')->nullable();
    $table->string('hsn_code', 20)->nullable();
    $table->string('charged_via', 50)->nullable();
    $table->integer('rail_green_points')->nullable(); // CO2 emission savings
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps(); // No soft deletes - immutable record

    $table->index(['siding_id', 'rr_date']);
    $table->index('rr_number');
    $table->index('invoice_number');
});
```

#### Table: rr\_wagon\_details

**Purpose:** Store wagon-wise RR data
**Migration File:** `YYYY_MM_DD_HHMMSS_create_rr_wagon_details_table.php`

```php
Schema::create('rr_wagon_details', function (Blueprint $table) {
    $table->id();
    $table->foreignId('rr_actual_id')->constrained('rr_actuals')->onDelete('cascade');
    $table->foreignId('rake_wagon_id')->nullable()->constrained('rake_wagons')->onDelete('set null');
    $table->string('wagon_number', 50);
    $table->string('wagon_type', 50);
    $table->decimal('gross_weight_mt', 10, 2);
    $table->decimal('tare_weight_mt', 10, 2);
    $table->decimal('net_weight_mt', 10, 2);
    $table->decimal('overload_amount_mt', 10, 2)->default(0);
    $table->boolean('is_overloaded')->default(false);
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps(); // No soft deletes - immutable record

    $table->index(['rr_actual_id', 'wagon_number']);
});
```

#### Table: rr\_additional\_charges

**Purpose:** Store miscellaneous RR charges
**Migration File:** `YYYY_MM_DD_HHMMSS_create_rr_additional_charges_table.php`

```php
Schema::create('rr_additional_charges', function (Blueprint $table) {
    $table->id();
    $table->foreignId('rr_actual_id')->constrained('rr_actuals')->onDelete('cascade');
    $table->string('charge_code', 20);
    $table->string('charge_description', 255);
    $table->decimal('charge_amount', 10, 2);
    $table->decimal('gst_amount', 10, 2)->default(0);
    $table->decimal('total_charge_amount', 10, 2);
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps(); // No soft deletes - immutable record

    $table->index(['rr_actual_id', 'charge_code']);
});
```

#### Table: power\_plant\_receipts

**Purpose:** Track coal receipts at power plant destination for end-to-end reconciliation (4.11 requirement)
**Migration File:** `YYYY_MM_DD_HHMMSS_create_power_plant_receipts_table.php`

```php
Schema::create('power_plant_receipts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('rr_actual_id')->constrained('rr_actuals')->onDelete('cascade');
    $table->foreignId('rake_id')->nullable()->constrained('rakes')->onDelete('set null');
    $table->string('power_plant_name', 100); // e.g., "BTPC Bokaro", "STPS Ramagundam"
    $table->string('pp_receipt_number', 50)->unique()->nullable(); // PP's receipt document number
    $table->date('pp_receipt_date');
    $table->timestamp('pp_receipt_time')->nullable();
    $table->decimal('total_quantity_received_mt', 10, 2); // Actual coal received at PP
    $table->decimal('quantity_variance_mt', 10, 2)->nullable(); // pp_received - rr_dispatched
    $table->decimal('quantity_variance_percent', 5, 2)->nullable(); // variance/rr_dispatched * 100
    $table->enum('variance_status', ['OK', 'MINOR_VARIANCE', 'MAJOR_VARIANCE'])->default('OK');
    $table->text('variance_remarks')->nullable(); // Reason for variance if any
    $table->string('custody_receipt_number', 50)->nullable(); // Custody transfer document
    $table->enum('receipt_status', ['PENDING', 'RECEIVED', 'VARIANCE_REPORTED', 'RECONCILED'])->default('PENDING');
    $table->json('quality_parameters')->nullable(); // Moisture, ash content, calorific value, etc. (optional)
    $table->foreignId('created_by')->constrained('users');
    $table->foreignId('verified_by')->nullable()->constrained('users');
    $table->timestamp('verified_at')->nullable();
    $table->timestamps();

    $table->index(['rr_actual_id', 'receipt_status']);
    $table->index(['power_plant_name', 'pp_receipt_date']);
    $table->index(['rake_id', 'receipt_status']);
    $table->unique('pp_receipt_number');
});
```

**Reconciliation Logic (5-Point Check - Step 5):**
- Compare RR actual weight → Power Plant received weight
- Flag if variance > 2% (auto-alert to in-charge)
- Reconciliation complete only when this step is verified

### Loader App Integration Table

#### Table: loader\_device\_sync

**Purpose:** Track loader device data sync for future loader app integration (4.6 requirement)
**Migration File:** `YYYY_MM_DD_HHMMSS_create_loader_device_sync_table.php`

```php
Schema::create('loader_device_sync', function (Blueprint $table) {
    $table->id();
    $table->foreignId('loader_id')->constrained('loaders')->onDelete('cascade');
    $table->foreignId('rake_id')->nullable()->constrained('rakes')->onDelete('set null');
    $table->json('device_data')->nullable(); // Raw JSON from loader device (qty, operator, timestamp, gps coords)
    $table->json('parsed_data')->nullable(); // Normalized: wagon_number, loaded_qty_mt, loader_id, timestamp
    $table->enum('sync_status', ['PENDING', 'SYNCED', 'CONFLICT', 'MANUAL_OVERRIDE'])->default('PENDING');
    $table->decimal('system_quantity_mt', 10, 2)->nullable(); // Manual entry quantity for comparison
    $table->decimal('device_quantity_mt', 10, 2)->nullable(); // Device reported quantity
    $table->decimal('quantity_difference_mt', 10, 2)->nullable(); // device - system
    $table->text('conflict_reason')->nullable(); // Why device data conflicts with manual entry
    $table->text('resolution_notes')->nullable(); // How conflict was resolved
    $table->enum('resolution_method', ['AUTO_SYNC', 'MANUAL_ENTRY_WINS', 'DEVICE_WINS', 'AVERAGE'])->nullable();
    $table->foreignId('resolved_by')->nullable()->constrained('users');
    $table->timestamp('resolved_at')->nullable();
    $table->timestamps();

    $table->index(['rake_id', 'loader_id', 'sync_status']);
    $table->index(['loader_id', 'created_at']);
});
```

**Future Integration Plan:**
- Phase 1 (MVP): Manual wagon loading entry (current design)
- Phase 2: Loader device app sends data → `loader_device_sync` table
- Sync happens via API: POST `/api/rake/{rake}/loader-sync`
- System auto-compares device qty vs manual entry qty
- UI shows "Device" vs "Manual" conflict resolution options
- Operator resolves conflict → system records resolution method

### Chat Tables (for in-app coordination and optional AI assistant)

#### Table: chat_rooms

**Purpose:** Chat rooms (global, per siding, or per rake)
**Migration:** `create_chat_rooms_table.php`

- `id`, `name`, `type` (global|siding|rake), `siding_id` (nullable), `rake_id` (nullable), `created_at`, `updated_at`
- Index on `(type, siding_id)`, `(type, rake_id)`

#### Table: chat_messages

**Purpose:** Messages in a room
**Migration:** `create_chat_messages_table.php`

- `id`, `chat_room_id`, `user_id`, `body` (text), `created_at`
- Optional: `metadata` (json) for AI assistant replies or attachments
- Index on `(chat_room_id, created_at)`

### User-Siding Mapping Table

#### Table: user\_siding

**Purpose:** Map users to specific sidings
**Migration File:** `YYYY_MM_DD_HHMMSS_create_user_siding_table.php`

```php
Schema::create('user_siding', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
    $table->foreignId('siding_id')->constrained('sidings')->onDelete('cascade');
    $table->boolean('is_primary')->default(false); // For auto-redirect
    $table->timestamps();

    $table->unique(['user_id', 'siding_id']);
    $table->index(['user_id', 'is_primary']);
});
```

### Indexes Summary

**Performance Indexes (Critical Queries):**

- Active rakes with demurrage risk: `rakes(siding_id, rake_status, placement_time)`
- Daily stock movements: `coal_stock(siding_id, transaction_time)`
- Vehicle unload status: `vehicle_unloads(siding_id, unload_status, arrival_time)`
- RR reconciliation: `rr_actuals(siding_id, rr_date)`, `rr_predictions(rake_id, prediction_status)`
- Penalty tracking: `rake_extra_penalties(rake_id, penalty_status)`

**Foreign Key Cascade Rules:**

- Organizations deleted → Sidings deleted → All siding data cascade deleted
- Users deleted → Audit trail preserved (via soft deletes), user\_siding records cascade deleted
- Rakes deleted → All rake\_wagons cascade deleted (soft delete on rakes preserves data)
- Indents deleted → Linked rakes set null (preserves rake history)

## State Machine Implementations

**Implementation:** Spatie Laravel Model States on Eloquent models (e.g. `Rake`, `Indent`, `VehicleUnload`). State classes in `app/States/RakeManagement/` (or on model namespace). No separate workflow package; use state transitions in Actions.

### 1. Rake Lifecycle State Machine

**Workflow Name:** `rake_lifecycle`
**Model:** `Modules\RakeManagement\Models\Rake`
**State Field:** `rake_status`

**States:**

- `CREATED` (Initial state)
- `ARRIVED` (Rake physically arrived at siding)
- `TXR_PENDING` (TXR inspection pending)
- `TXR_IN_PROGRESS` (TXR inspection in progress)
- `TXR_COMPLETED` (All wagons inspected, fit/unfit marked)
- `DISPATCHED` (Rake dispatched to loading track - **3-hour timer starts here**)
- `LOADING` (Wagon loading in progress)
- `GUARD_INSPECTION` (Guard inspection after loading)
- `MOVEMENT_PERMISSION` (Permission granted for movement)
- `WEIGHMENT_IN_PROGRESS` (In-motion weighment happening)
- `READY_FOR_FINAL_DISPATCH` (Passed weighment, ready to go)
- `COMPLETED` (Dispatched to power plant)
- `CANCELLED` (Rake cancelled)

**Transitions:**

| Transition        | From        | To                    | Conditions & Actions |
| :--------------- | :---------- | :-------------------- | :------------------ |
| `mark_arrived`   | CREATED     | ARRIVED               | User: Siding Operator; Record placement\_time |
| `start_txr`      | ARRIVED     | TXR\_IN\_PROGRESS        | User: Siding Operator; Record txr\_start\_time |
| `complete_txr`   | TXR\_IN\_PROGRESS | TXR\_COMPLETED      | User: Siding Operator; Record txr\_end\_time; All wagons marked fit/unfit |
| `dispatch_loading`| TXR\_COMPLETED | DISPATCHED        | User: Railway; **DO NOT start timer yet** |
| `start_timer`     | DISPATCHED   | LOADING               | User: System (automatic); **Record placement\_time = now; Start 3-hour timer** |
| `complete_loading`| LOADING     | GUARD\_INSPECTION      | User: Loading Team; Record loading\_end\_time |
| `guard_approved`  | GUARD\_INSPECTION | MOVEMENT\_PERMISSION | User: Railway Guard; Record movement\_permission\_time |
| `start_weighment` | MOVEMENT\_PERMISSION | WEIGHMENT\_IN\_PROGRESS | User: Siding Operator; Move rake to weighment track |
| `weighment_pass`  | WEIGHMENT\_IN\_PROGRESS | READY\_FOR\_FINAL\_DISPATCH | User: Siding Operator; No wagons overloaded; Calculate demurrage |
| `weighment_fail`  | WEIGHMENT\_IN\_PROGRESS | LOADING           | User: Siding Operator; Overload detected or speed invalid; Timer continues |
| `complete_reload` | LOADING     | GUARD\_INSPECTION      | User: Loading Team; After correction reload; Timer continues |
| `final_dispatch`  | READY\_FOR\_FINAL\_DISPATCH | COMPLETED  | User: Railway; Record dispatch\_time; Mark for RR upload |
| `cancel`         | Any state   | CANCELLED            | User: System Admin/Management; Record reason |

**Critical Business Rules:**

- Timer ONLY starts when transition `start_timer` fires (from DISPATCHED → LOADING)
- Timer NEVER pauses or resets through any subsequent transitions
- Correction loops (weighment\_fail → LOADING → complete\_reload) continue timer
- Penalty calculated only on `weighment_pass` transition
- Only one `weighment_pass` transition allowed per rake

**Events:**

- `RakeTimerStarted` → Broadcast via Reverb to start live countdown
- `RakeStatusChanged` → Broadcast status updates
- `OverloadDetected` → Alert when weighment fails
- `DemurrageTriggered` → Alert when 180 minutes exceeded

### 2. Indent Lifecycle State Machine

**Workflow Name:** `indent_lifecycle`
**Model:** `Modules\RakeManagement\Models\Indent`
**State Field:** `indent_status`

**States:**

- `RAISED` (Initial state - operator creates indent)
- `APPROVAL_PENDING` (Sent to railway)
- `APPROVED` (Railway confirms)
- `REJECTED` (Railway declines)
- `CANCELLED` (Cancelled by user, no rake linked)
- `RAKE_ARRIVED` (Rake placed at siding)
- `RAKE_CREATED` (Rake entry created from indent)
- `CLOSED` (All rakes completed, stock accounted)

**Transitions:**

| Transition        | From                | To                    | Conditions & Actions |
| :--------------- | :------------------ | :-------------------- | :------------------ |
| `submit_to_railway` | RAISED      | APPROVAL\_PENDING       | User: Siding Operator |
| `railway_approve` | APPROVAL\_PENDING | APPROVED            | User: Railway; Store railway\_reference\_number, railway\_approval\_time |
| `railway_reject` | APPROVAL\_PENDING | REJECTED            | User: Railway; Record rejection\_reason (mandatory) |
| `cancel`        | RAISED, APPROVAL\_PENDING | CANCELLED | User: Siding Operator/In-Charge; Record reason |
| `rake_arrived`  | APPROVED         | RAKE\_ARRIVED          | User: System (automatic on rake creation) |
| `create_rake`    | RAKE\_ARRIVED     | RAKE\_CREATED          | User: Siding Operator; Link to rakes table; Logically reserve stock |
| `close`         | RAKE\_CREATED      | CLOSED                | User: System/Finance; All rakes completed; Stock deducted |

**Stock Reservation Rules:**

- On `railway_approve`: Logically reserve stock (available\_quantity\_mt snapshot preserved)
- On `create_rake`: Create rake entry, start physical deduction planning
- On `close`: Physical stock deduction via coal\_stock transaction (transaction\_type=DISPATCH)
- Cannot raise new indent if insufficient stock (available\_reserved > current\_stock)

### 3. Vehicle Unload Lifecycle State Machine

**Workflow Name:** `vehicle_unload_lifecycle`
**Model:** `Modules\RakeManagement\Models\VehicleUnload`
**State Field:** `unload_status`

**States:**

- `ARRIVED` (Initial state - vehicle arrives)
- `UNDER_VALIDATION` (Validating challan, trip status)
- `READY_FOR_UNLOAD` (Arrival weighment passed)
- `UNLOADING` (Coal being unloaded)
- `TARE_VALIDATION` (Tare weight after unloading)
- `COMPLETED` (Unloaded, validated, stock added)
- `CANCELLED` (Cancelled at any point before COMPLETED)

**Transitions:**

| Transition        | From                    | To                  | Conditions & Actions |
| :--------------- | :---------------------- | :------------------ | :------------------ |
| `start_validation` | ARRIVED           | UNDER\_VALIDATION    | User: Siding Operator; Search by vehicle\_number/challan |
| `pass_validation`  | UNDER\_VALIDATION | READY\_FOR\_UNLOAD    | User: Siding Operator; Trip exists, status=DISPATCHED, no existing COMPLETED unload |
| `fail_validation`  | UNDER\_VALIDATION | CANCELLED          | User: Siding Operator; Record reason |
| `start_unload`     | READY\_FOR\_UNLOAD | UNLOADING          | User: Siding Operator; Record unload\_start\_time |
| `finish_unload`    | UNLOADING        | TARE\_VALIDATION     | User: Siding Operator; Record unload\_end\_time |
| `pass_tare`       | TARE\_VALIDATION | COMPLETED          | User: Siding Operator; Tare difference within tolerance; Create stock entry |
| `fail_tare`       | TARE\_VALIDATION | REWEIGH\_REQUIRED    | User: Siding Operator; Tare mismatch; Require manual override |
| `manual_approve`   | REWEIGH\_REQUIRED | COMPLETED          | User: Siding In-Charge (supervisor override); Create stock entry with override flag |
| `cancel`           | Any before COMPLETED | CANCELLED     | User: Siding Operator; Record reason |

**Validation Rules:**

- Arrival weighment: Compare mine\_net\_weight\_mt vs siding\_net\_weight\_mt with configurable tolerance
- Tare validation: Compare final\_tare\_weight\_mt vs vehicle.tare\_weight\_mt with configurable tolerance
- Manual override: Requires supervisor authorization + mandatory reason field
- Stock entry: Only created on `pass_tare` or `manual_approve` transitions

### 4. TXR Inspection State Machine

**Workflow Name:** `txr_inspection_lifecycle`
**Model:** `Modules\RakeManagement\Models\Rake`
**State Field:** `rake_status` (TXR states only)

**States:**

- `TXR_PENDING` (Rake arrived, TXR pending)
- `TXR_IN_PROGRESS` (TXR inspection in progress)
- `TXR_COMPLETED` (All wagons inspected, fit/unfit marked)

**Transitions:**

| Transition        | From            | To                  | Conditions & Actions |
| :--------------- | :-------------- | :------------------ | :------------------ |
| `start_txr`      | TXR\_PENDING      | TXR\_IN\_PROGRESS     | User: Siding Operator/Railway; Record txr\_start\_time |
| `complete_txr`   | TXR\_IN\_PROGRESS | TXR\_COMPLETED       | User: Siding Operator/Railway; Record txr\_end\_time; All wagons marked; **Timer NOT started** |

**TXR Time Rule:**

- Maximum TXR time allowed: 1 hour 40 minutes (100 minutes)
- TXR time is INCLUDED within 3-hour free loading window
- Timer starts AFTER `TXR_COMPLETED` → `DISPATCHED` → `LOADING` flow

### 5. RR Prediction Lifecycle State Machine

**Workflow Name:** `rr_prediction_lifecycle`
**Model:** `Modules\RakeManagement\Models\RrPrediction`
**State Field:** `prediction_status`

**States:**

- `DRAFT` (Initial calculation)
- `FINALIZED` (Ready for comparison)
- `CONFIRMED` (Matched with actual RR)

**Transitions:**

| Transition        | From        | To            | Conditions & Actions |
| :--------------- | :---------- | :------------ | :------------------ |
| `finalize`       | DRAFT       | FINALIZED     | User: System (automatic on weighment\_pass) |
| `confirm`        | FINALIZED   | CONFIRMED     | User: Finance; After RR upload and comparison |

### 6. Penalty Lifecycle State Machine

**Workflow Name:** `penalty_lifecycle`
**Model:** `Modules\RakeManagement\Models\RakeExtraPenalty`
**State Field:** `penalty_status`

**States:**

- `PREDICTED` (System-calculated penalty)
- `CONFIRMED` (Confirmed from RR)
- `WAIVED` (Waived by railway/management)

**Transitions:**

| Transition        | From        | To            | Conditions & Actions |
| :--------------- | :---------- | :------------ | :------------------ |
| `confirm`        | PREDICTED   | CONFIRMED     | User: Finance; After RR upload |
| `waive`          | PREDICTED, CONFIRMED | WAIVED | User: Management; Record waiver reason |

## API Integration Specifications

### SHAReTrack Integration (Phase 1: Manual Entry)

**Purpose:** Ingest vehicle dispatch data from mine-side system

**Phase 1 Implementation (Manual Entry):**

**Entry Forms:** Inertia React forms in `resources/js/pages/rake-management/`; controllers in `app/Http/Controllers/RakeManagement/`.

1. **Vehicle Master Entry:**

- Component: `VehicleMasterForm.php`
- Fields: vehicle\_number, rfid\_tag, permitted\_capacity\_mt, tare\_weight\_mt, owner\_name, vehicle\_type, gps\_device\_id
- Validation: vehicle\_number unique, permitted\_capacity\_mt > 0
- Action: INSERT into vehicles table

2. **Vehicle Dispatch Entry:**

- Component: `VehicleDispatchForm.php`
- Fields: vehicle\_number (search), challan\_number, dispatch\_time, route (select), siding (select), trip\_status
- Validation: vehicle exists, route exists, siding exists
- Action: INSERT into vehicle\_trips table

3. **Coal Loading Entry:**

- Component: `CoalLoadingForm.php`
- Fields: vehicle (search from trip), entry\_time, loading\_start\_time, loading\_end\_time, final\_net\_weight\_mt, overload\_flag, underload\_flag, jimms\_challan\_number
- Validation: final\_net\_weight\_mt within permitted\_capacity ± tolerance
- Action: INSERT into vehicle\_coal\_site (if new) + link to trip

**Data Validation Rules:**

- Vehicle number: Must match existing vehicle or create new
- Trip status: Must be DISPATCHED to be imported
- Overload detection: If overload\_flag=true, require reason
- Tare weight: Must be within ±5% of stored tare\_weight

**Phase 2 Implementation (API Integration - Future):**

**API Endpoints (to be defined by SHAReTrack):**

1. **GET /api/vehicles** - List all vehicles
2. **GET /api/vehicles/{id}** - Get vehicle details
3. **GET /api/vehicles/trips** - List dispatch trips (with filters)
4. **GET /api/vehicles/trips/{id}/loading** - Get loading data for trip

**Authentication:**

- Laravel Sanctum API tokens (personal\_access\_tokens table)
- Token: `Bearer {token}` in Authorization header
- Token expiration: 30 days (configurable)

**Data Format:** JSON

**Retry Logic:**

- Failed API calls queued for retry
- Exponential backoff: 1min, 5min, 15min, 30min
- Max retry attempts: 5
- Failed transitions logged via Spatie Activity Log or application log

**Queue Processing:**

- Job: `Modules\RakeManagement\Jobs\ImportVehiclesFromShareTrack`
- Queue: `sharetrack-import`
- Worker processes continuously

### GPS Tracking Integration

**Purpose:** Real-time vehicle tracking from GPS devices

**Data Ingestion:**

**API Endpoint:** `POST /api/gps/tracking`

**Request Body:**

```json
{
  "device_id": "GPS12345",
  "latitude": 24.123456,
  "longitude": 87.654321,
  "speed_kmph": 45.5,
  "timestamp": "2026-02-16T10:30:00Z"
}
```

**Validation:**

- device\_id exists in vehicles.gps\_device\_id
- Valid latitude (-90 to 90)
- Valid longitude (-180 to 180)
- Valid speed (0 to 150 kmph)

**Processing:**

1. Find vehicle by gps\_device\_id
2. Find active trip for vehicle (trip\_status=DISPATCHED or IN\_TRANSIT)
3. Check if on route (geofence validation using routes.geo\_json\_path\_data)
4. INSERT into gps\_tracking\_logs
5. Detect stoppages (speed < 1 kmph for > 5 minutes) → INSERT into trip\_stoppages
6. Detect route deviation (distance\_from\_route\_km > 0.5 km) → INSERT into route\_deviations

**Alerts:**

- Route deviation: Broadcast to `sidings.{siding_id}` channel
- Long stoppage (> 30 min): Broadcast alert

### Weighbridge Integration

**Purpose:** Ingest weighment data from siding weighbridge

**API Endpoint:** `POST /api/weighbridge/data`

**Request Types:**

1. **Vehicle Arrival Weighment:**

```json
{
  "type": "ARRIVAL_CHECK",
  "vehicle_unload_id": "UNL-123",
  "gross_weight_mt": 45.5,
  "tare_weight_mt": 25.0,
  "net_weight_mt": 20.5,
  "timestamp": "2026-02-16T10:30:00Z"
}
```

2. **In-Motion Weighment:**

```json
{
  "type": "INMOTION_WEIGHMENT",
  "rake_id": "RAKE-123",
  "attempt_number": 1,
  "train_speed_kmph": 6.5,
  "wagon_data": [
    {
      "wagon_number": "ECR12345",
      "gross_weight_mt": 85.5,
      "tare_weight_mt": 25.6,
      "net_weight_mt": 59.9
    }
  ],
  "timestamp": "2026-02-16T10:30:00Z"
}
```

**Validation:**

- Vehicle arrival: vehicle\_unload\_id exists, weight values > 0
- In-motion: rake\_id exists, speed 5-7 kmph (valid range)
- All weighments: Timestamp within last 24 hours (anti-tampering)

**Processing:**

1. Validate request data
2. INSERT into appropriate weighment table
3. Trigger state machine transitions if applicable
4. Broadcast updates via Reverb

**Fallback:** Manual entry always supported via UI forms

## RR OCR & Document Processing

**Purpose:** Extract RR data from uploaded PDFs

**Technology Stack:**

- OCR: Tesseract OCR or Google Cloud Vision API (to be decided)
- PDF Parsing: Spatie PDF package or Smalot/PDFParser
- AI Extraction: OpenAI GPT-4 Vision or Azure Form Recognizer (to be decided)
- File Storage: Spatie Media Library (already installed)

### RR Upload Workflow

**Step 1: PDF Upload**

- **UI:** RR upload page (Inertia React); controller handles store and queue.
- **Action:** Upload PDF to S3/storage via Media Library
- **Table:** INSERT into rr\_documents (ai\_processed=false)

**Step 2: OCR Extraction**

- **Job:** `Modules\RakeManagement\Jobs\ExtractRrData`
- **Queue:** `rr-ocr-processing`
- **Process:**

1. Retrieve PDF from Media Library
2. Extract text using OCR engine
3. Store raw\_text in rr\_documents
4. Pass text to AI extraction
5. Update rr\_extracted\_data with extracted fields
6. Update rr\_documents.ai\_processed=true
7. Set extraction\_status=CONFIRMED if confidence\_score > 0.85
8. Set extraction\_status=DRAFT if confidence\_score < 0.85

**Step 3: Manual Verification**

- **UI:** RR verification page (Inertia React); edit extracted data and confirm.
- **Process:**

1. Display extracted data with confidence scores
2. Allow manual editing of fields
3. User clicks "Verify" → Manually verified flag set to true
4. INSERT into rr\_actuals with verified data
5. Create rr\_wagon\_details records
6. Create rr\_additional\_charges records

**Step 4: Reconciliation Trigger**

- After rr\_actuals created → Trigger 5-point reconciliation engine

### OCR Field Mapping

**Extracted Fields (from rr\_extracted\_data → rr\_actuals):**

| Field             | Source       | Validation |
| :---------------- | :----------- | :--------- |
| rr\_number         | PDF header   | Required, unique |
| rr\_date           | PDF header   | Required, valid date |
| fnr\_reference     | PDF body     | Optional |
| station\_from      | PDF body     | Required |
| station\_to        | PDF body     | Required |
| consignor         | PDF body     | Required |
| consignee         | PDF body     | Required |
| commodity         | PDF body     | Required |
| wagons            | PDF body     | Required, integer |
| class\_code        | PDF body     | Optional |
| distance\_km       | PDF body     | Required, decimal |
| rate\_per\_mt       | PDF body     | Required, decimal |
| sender\_weight\_mt   | PDF body     | Optional, decimal |
| actual\_weight\_mt   | PDF body     | Required, decimal |
| chargeable\_weight\_mt | PDF body | Required, decimal |
| overweight\_mt      | PDF body     | Decimal, default 0 |
| freight\_amount     | PDF body     | Required, decimal |
| other\_charges\_amount | PDF body | Decimal, default 0 |
| gst\_amount        | PDF body     | Required, decimal |
| total\_amount       | PDF body     | Required, decimal |
| invoice\_number    | PDF body     | Optional |
| invoice\_date      | PDF body     | Optional, valid date |

**Confidence Score:**

- Per-field confidence (0-1)
- Overall confidence: Average of all fields
- Threshold: 0.85 for auto-confirmation
- Below threshold: Manual verification required

### Duplicate Invoice Detection

**Rule:** If multiple rakes share the same invoice\_number, flag for review

**Implementation:**

- On rr\_actuals INSERT: Check for existing rr with same invoice\_number
- If found: Set flag `is_duplicate_invoice=true`
- Alert user: "This invoice number already used for RAKE-XXX"
- Allow user to confirm it's legitimate (e.g., multiple rakes on same invoice)

## UI/UX Structure

**Technology:** Inertia.js v2 + React 19 + Wayfinder
**Location:** `resources/js/pages/rake-management/` (e.g. Dashboard, Rakes, Indents, RR, Chat); Filament for admin in `app/Filament/`.

### Navigation Structure

**Sidebar Navigation (Active Site Context Displayed):**

**For Siding Operators:**

1. Dashboard
2. Truck Unloading
3. Stock Ledger
4. Indents
5. Rakes
6. Railway Receipts (RR)
7. Penalties
8. Reports

**For Management (Multi-site):**

1. Dashboard (Aggregated View)
2. Site Selection Screen
3. [Per Siding Navigation]

**For Finance:**

1. Dashboard
2. Railway Receipts (RR)
3. Reconciliation
4. Penalties
5. Reports

### Page Components

#### 1. Site Selection Screen

**Inertia page:** Site selection (e.g. `resources/js/pages/rake-management/SiteSelection.tsx`)

**Layout:**

- Grid of cards (3 sidings: Pakur, Dumka, Kurwa)
- Each card shows:
- Siding name
- Siding code
- Location
- Active rakes count
- Available stock (MT)
- Pending alerts count
- Clicking card → Set `active_siding_id` → Redirect to dashboard

**Behavior:**

- Only shown to multi-site users
- Single-site users auto-redirected to dashboard
- Session stores active\_siding\_id

#### 2. Dashboard

**Inertia page:** Dashboard (e.g. `Dashboard.tsx`)

**Layout (12-column grid):**

**Top Row (KPI Cards):**

- Active Rakes Count (color-coded: Green < 3, Yellow 3-5, Red > 5)
- Available Coal Stock (MT)
- Pending Indents Count
- Today's Completed Rakes
- Pending Weighments
- Pending RR Uploads

**Middle Section (Active Rakes Table):**

- Columns: Rake Number, Status, Wagons, Timer (live countdown), Alert Level
- Live timer updates via Reverb (every 1 second)
- Click row → Open Rake Detail

**Bottom Row (Alerts):**

- Demurrage Risk Alerts (rakes < 60 min remaining)
- Overload Alerts (from loader vs weighment)
- RR Mismatch Alerts (prediction vs actual variance > 2%)

**Real-Time Updates:**

- Subscribe to `sidings.{siding_id}` channel
- Listen for: RakeTimerUpdated, RakeStatusChanged, OverloadDetected, RrMismatchAlert

#### 3. Truck Unloading Module

**Inertia React pages:**

- Truck unloading list and detail (e.g. `TruckUnloading/Index.tsx`, `TruckUnloading/Show.tsx`); arrival and unloading forms on detail or dedicated pages.

**List View Layout:**

- Table: Vehicle Number, Challan Number, Mine Net Weight, Status, Arrival Time, Actions
- Status badges with colors (ARRIVED=gray, UNLOADING=blue, COMPLETED=green, CANCELLED=red)
- Filter by status, date range
- Search by vehicle number/challan number
- Pagination: 25 rows per page

**Detail View Layout:**

- Header: Vehicle number, status, timestamps
- Tabs:

1. Vehicle Details
2. Weighment History
3. Unloading Log

- State machine progress bar showing current stage

**Forms:**

- Arrival Form: Search vehicle/challan, validate trip, create unload record
- Unloading Form: Start/stop unloading, capture weights
- Validation: All required fields, status transitions enforced

#### 4. Stock Ledger

**Inertia page:** Stock ledger (e.g. `StockLedger.tsx`)

**Layout:**

- Table: Date, Time, Type (Receipt/Dispatch), Quantity (MT), Running Balance (MT), Reference
- Type badges: RECEIPT (green), DISPATCH (orange)
- Running balance calculated cumulatively
- Filter by date range, transaction type
- Export to Excel button

**Behavior:**

- Read-only ledger (append-only)
- Auto-update when stock transactions occur
- Highlight low stock (< 5000 MT) in red

#### 5. Indents

**Inertia React pages:** Indent list, create, and detail (e.g. `Indents/Index.tsx`, `Indents/Create.tsx`, `Indents/Show.tsx`)

**List View Layout:**

- Table: Indent Number, Date, Target Quantity (MT), Status, Railway Reference, Actions
- Status badges with colors (RAISED=gray, APPROVED=green, REJECTED=red, CANCELLED=gray)
- Filter by status, date range
- Create New Indent button (top right)

**Create Form Layout:**

- Fields: Indent Date, Time, Siding (auto-filled), Target Quantity (MT)
- Validation: Target quantity ≤ Available stock
- Stock display: Shows available quantity (real-time)
- Submit → Status=RAISED

**Detail View Layout:**

- Header: Indent details, status
- Status lifecycle progress bar (visual)
- Tabs:

1. Details
2. Linked Rake
3. Stock Transactions

#### 6. Rakes Module

**Inertia React pages:** Rake list, detail (with wagon theatre and actions), create from indent (e.g. `Rakes/Index.tsx`, `Rakes/Show.tsx`, `Rakes/Create.tsx`); wagon theatre and detail panel as components or sections on Show.

**List View Layout:**

- Table: Rake Number, Status, Wagons, Loaded (MT), Elapsed Time, Timer, Actions
- Live countdown timer for active rakes
- Status badges with colors (TXR\_COMPLETED=blue, LOADING=yellow, COMPLETED=green)
- Filter by status, date range
- Search by rake number

**Detail View Layout (Main):**

**Header Section:**

- Rake number, status, destination, distance
- Timer display (large, color-coded: Green > 60min, Yellow 30-60min, Red < 30min)
- Status lifecycle progress bar (visual steps: TXR → Placement → Loading → Guard → Weighment → Complete)

**Middle Section (Two Columns):**

**Left Column (60%): Wagon Theatre View:**

- Grid representation of all wagons
- Color coding:
- Red = Unfit Wagon
- Orange = Overloaded Wagon
- Blue = Normal (loaded, within limit)
- Gray = Fit but not yet loaded
- Click wagon → Opens Wagon Detail Panel
- Grid shows wagon numbers in rows of 10 (60 wagons = 6 rows)

**Right Column (40%): Control Panel:**

**Tab 1: Current Stage Actions:**

- State-dependent buttons (e.g., "Start TXR", "Complete Loading", "Start Weighment")
- Each action triggers state machine transition

**Tab 2: Wagon Detail Panel:**

- Selected wagon details
- Fields: Wagon Number, Type, Capacity (MT), Loaded (MT), Status
- Loader selection dropdown
- Overload indicator
- Mark Fit/Unfit buttons

**Tab 3: Weighment Data:**

- Per-wagon weighment display
- Table: Wagon No, Loader Qty (MT), In-Motion Qty (MT), Difference, Status
- Overload flags highlighted

**Tab 4: Reconciliation:**

- Predicted vs Actual comparison
- Table: Metric, Predicted, Actual, Difference, Variance %
- Action buttons: "Predict RR", "Upload RR"

**Bottom Section: Load Steps Timeline:**

- Vertical timeline showing all rake\_load\_steps
- Each step shows: Step type, time, remarks, user
- Visual indicators for completed steps

#### 7. Railway Receipts (RR) Module

**Livewire Components:**

- `RrList.php` (List view)
- `RrUploadForm.php` (Upload form)
- `RrVerificationForm.php` (Verification form)
- `RrDetail.php` (Detail view)

**List View Layout:**

- Table: RR Number, RR Date, Rake Number, From/To, Total Amount, Status, Actions
- Status badges: UPLOADED (blue), VERIFIED (green), RECONCILED (purple)
- Filter by date range, status
- Upload RR button (top right)

**Upload Form Layout:**

- File upload: PDF drag-and-drop zone
- Auto-OCR processing indicator (spinner)
- Preview extracted data after OCR
- Manual verification fields
- Save/Verify button

**Verification Form Layout:**

- Display extracted data with confidence scores
- Editable fields with confidence indicators
- Low confidence fields highlighted in yellow (< 0.85)
- Confirm button → Create rr\_actuals

**Detail View Layout:**

- Header: RR number, date, total amount
- Tabs:

1. RR Details
2. Wagon Details (57 wagons max)
3. Additional Charges
4. Reconciliation (predicted vs actual)

#### 8. Penalties Module

**Inertia page:** Penalty register (e.g. `Penalties/Index.tsx`)

**Layout:**

- Table: Date, Siding, Rake Number, Penalty Type, Amount, Status, Stage
- Status badges: PREDICTED (gray), CONFIRMED (red), WAIVED (green)
- Stage badges: PRE-RR (orange), POST-RR (red)
- Filter by: Penalty type, status, stage, date range, siding
- Export to Excel button

**Summary Cards (Top):**

- Total penalties (this month)
- Demurrage amount
- Overload penalties
- Weighment charges

#### 9. Loader Performance Analytics (NEW - Covers Gap #5)

**Inertia page:** Loader performance trends (e.g. `Analytics/LoaderPerformance.tsx`)

**Purpose:** Track historical loader performance, identify trends, detect over/underload patterns

**Layout:**

**Summary Cards (Top Row):**

- Total loaders active
- Average loading time this month
- Overload instances (count)
- Underload instances (count)
- Loader efficiency score (weighted average)

**Time Series Chart (Line Graph):**

- X-axis: Date (date range selector: last 7/30/90 days)
- Y-axis: Average loading time (minutes)
- Multiple lines: One per active loader (different colors)
- Hover tooltip: Date, loader name, avg time, wagon count
- Trend analysis: Show if loader speed is improving/declining

**Per-Loader Performance Table:**

- Columns: Loader Name, Rakes Handled, Avg Load Time (min), Min Load Time, Max Load Time, Overload %, Underload %, Efficiency Score
- Sortable: Click header to sort
- Color coding: Red if overload % > 5%, Green if < 2%
- Click row → Open loader detail view

**Loader Detail View:**

- Loader name, type, capacity
- Last 10 rakes loaded (with times)
- Scatter plot: Wagon count vs Loading time (shows efficiency pattern)
- Alerts: "Loader 16 showing 15% overload rate (>5% threshold)" with action to review

**Data Source:** `rake_wagon_loading` + `rakes` + `loaders` JOIN

**Calculations:**
- Avg Load Time = (loading_end_time - loading_start_time) / wagon_count
- Efficiency Score = (perfect_loads / total_loads) * 100
- Overload % = (overload_wagons / total_wagons_loaded) * 100

**Use Case:** Finance/In-Charge monitors loader performance, identifies training needs, detects equipment issues

#### 9b. GPS Route Monitoring & Geofencing Alerts (NEW - Covers Gap #4)

**Inertia page:** Route monitoring (e.g. `Analytics/RouteMonitoring.tsx`)

**Purpose:** Monitor vehicle GPS paths, detect route deviations, validate siding boundaries, track transit time anomalies

**Real-Time Map View:**

- Base map (Google Maps integration or Leaflet.js)
- Display all vehicles in transit
- Color coding:
  - Green = On planned route
  - Yellow = Minor deviation (< 2km off path)
  - Red = Major deviation (> 2km off path, or geofence breach)
- Click vehicle → Show vehicle details + trip history

**Geofence Boundaries:**

- Three siding locations marked as circular geofences (configurable radius)
- Siding entry/exit events logged and timestamped
- Alert if vehicle enters geofence outside authorized window

**Route Deviation Detection Logic:**

```
1. On vehicle location update (GPS every 30 seconds):
   - Compare vehicle_gps_location with routes.geo_json_path_data
   - Calculate distance from planned route (using haversine formula)
   - If distance > 2km:
     * Set alert status = MAJOR_DEVIATION
     * Broadcast event to dashboard
     * Notify siding in-charge
2. Check against siding geofence:
   - If vehicle enters geofence without active trip:
     * Set alert status = UNAUTHORIZED_ENTRY
   - If vehicle exits siding geofence with incomplete weighment:
     * Set alert status = INCOMPLETE_WEIGHMENT
3. Detect suspicious patterns:
   - If vehicle makes > 3 detours in single trip
   - If trip time > (expected_time + 2_hours)
```

**Trip Timeline:**

- Dispatch time, geofence entry time, geofence exit time, arrival time
- Breakdown: Travel time, wait time at siding, unloading time
- Alerts: "Took 4.5 hours for 45km route (expected 1.5 hours)"

**Daily Route Report:**

- Generate daily summary: Vehicles dispatched, on-route, arrived, incidents
- Anomalies: Route deviations, delayed arrivals, unauthorized entries
- Export as PDF/Excel

**Data Tables:**

- `vehicle_gps_locations`: id, vehicle_id, latitude, longitude, altitude, accuracy, timestamp, created_at
- `vehicle_geofence_events`: id, vehicle_id, geofence_id (siding), event_type (ENTRY/EXIT), timestamp
- `vehicle_route_deviations`: id, vehicle_trip_id, deviation_distance_km, deviation_reason (unknown/traffic/accident), deviation_start_time, deviation_end_time, status (ALERT/RESOLVED)

**Data Source:** GPS device API integration; vehicle\_trips + vehicle\_routes

**Use Case:** Operations team monitors vehicle safety/compliance; in-charge can dispatch assistance if vehicle deviates; audit trail for insurance/disputes

#### 10. Reconciliation Module

**Inertia page:** Reconciliation dashboard (e.g. `Reconciliation/Index.tsx`)

**Layout:**

**Summary Cards (Top Row):**

- Pending reconciliations count
- Matched reconciliations (this month)
- Mismatched reconciliations (this month)
- Variance amount (total)

**Reconciliation Queue (Table):**

- Rake Number, Comparison Point, Variance %, Status, Action
- Comparison Points (5-Point Check - SCOPE 4.11):

1. **Mine vs Siding:** Vehicle gross/tare vs siding weighment; tolerance ±2%
2. **Siding vs Rake:** Stock deducted vs rake total weight loaded; should match
3. **Rake vs Weighment:** Loader-noted quantities vs in-motion weighment; tolerance ±3%
4. **Weighment vs RR:** Rake weighment total vs RR actual weight; tolerance ±2%
5. **RR vs Power Plant:** RR dispatch weight vs power plant receipt weight; tolerance ±2%; captured in `power_plant_receipts` table

- Status badges: MATCH (green), MINOR\_DIFF (yellow), MAJOR\_DIFF (red)
- Click row → Open detailed reconciliation view

**Reconciliation Detail View:**

- Display all 5 comparison points with detailed breakdown
- Each point shows: Source A (value), Source B (value), Variance (MT), Variance %, Status
- Actions per point:
  - Mine vs Siding: View vehicle weighment slip; view siding receipt challan
  - Siding vs Rake: View stock ledger; view rake load steps
  - Rake vs Weighment: View loader slips; view in-motion weighment data
  - Weighment vs RR: View weighment report; upload/view RR PDF
  - **RR vs Power Plant:** Link to power_plant_receipts; capture PP receipt details; verify variance
- Comments field for manual notes and resolution
- Approve/Reject/Hold buttons; audit trail of who approved/rejected

#### 10. Reports Module

**Inertia page:** Reports list (e.g. `Reports/Index.tsx`)

**Layout:**

- Grid of 10 report cards
- Each card: Report Name, Description, Generate Button
- Click Generate → Show report parameters form → Generate report → Display/Export

**Report Cards:**

1. **Siding Coal Receipt** - Shift-wise receipt report
2. **Rake Indent** - Indent history report
3. **Rake Placement & TXR** - TXR performance report
4. **Unfit Wagon Details** - Unfit wagon log
5. **Wagon Loading Data** - Loader-wise loading report
6. **In-Motion Weighment** - Weighment data report
7. **Loader vs Weighment Comparison** - Overload analysis report
8. **Rake Movement** - Movement delays report
9. **Railway Receipt (RR)** - RR summary report
10. **Penalty Register** - Penalty breakdown report

### State Machine Progress Bars

**Component:** State machine progress bar (reusable React component)

**Usage:** Display state transitions visually for any entity with state machine

**Visual Design:**

- Horizontal bar with step nodes
- Active step highlighted (blue)
- Completed steps (green checkmarks)
- Pending steps (gray circles)
- Step labels below each node
- Animated transition between steps

**Example for Rake:**

```
[✓ TXR] → [✓ Placement] → [● Loading] → [○ Guard] → [○ Weighment] → [○ Complete]
```

### Alert Banners

**Component:** Alert banner (reusable React component)

**Types:**

- Amber Warning (60 min remaining)
- Red Urgent (30 min remaining)
- Critical (0 min exceeded - penalty active)
- Info (General notifications)

**Behavior:**

- Appears at top of dashboard
- Dismissible by user
- Auto-dismiss after 5 minutes (optional)
- Listen to Reverb channels for live updates

### Filament Admin Resources

**Location:** `app/Filament/` (Filament resources for sidings, vehicles, routes, freight rates, loaders, etc.)

**Resources for Master Data Management:**

1. **SidingResource** - Manage sidings (Create/Edit/View)
2. **RouteResource** - Manage routes and geofencing data
3. **FreightRateResource** - Manage freight rate master
4. **VehicleResource** - View vehicle master (read-only from SHAReTrack)
5. **LoaderResource** - Manage loader machines

**Resource Features:**

- Table columns with sorting/filtering
- Form validation
- Relationships handled automatically
- Media integration for documents
- Export capabilities

## Reporting & Export System

**Technology:** Laravel Excel 3.1.67 (already installed)

### Report Formats

All reports implement `WithHeadingRow`, `WithStyles`, `WithColumnFormatting` from Laravel Excel.

#### Report 1: Siding Coal Receipt

**Export Class:** `Modules\RakeManagement\Exports\SidingCoalReceiptExport.php`

**Columns:**

- Date
- Shift
- Siding (Pakur/Dumka/Kurwa)
- Vehicle Number
- Trips Received
- Quantity (MT)
- Receipt Time

**Filters:**

- Siding (default: active\_siding\_id)
- Date range
- Shift (1, 2, 3)

**Data Source:** `vehicle_unloads` + `vehicle_trips` JOIN

**Grouping:** By shift, date

#### Report 2: Rake Indent

**Export Class:** `Modules\RakeManagement\Exports\RakeIndentExport.php`

**Columns:**

- Indent Date
- Siding
- Available Stock (MT)
- Target Qty (MT)
- Raised By
- Time
- Railway Reference No
- Status

**Filters:**

- Siding
- Date range
- Status

**Data Source:** `indents` table

#### Report 3: Rake Placement & TXR

**Export Class:** `Modules\RakeManagement\Exports\RakePlacementTxrExport.php`

**Columns:**

- Rake Number
- Siding
- Placement Time
- TXR Start Time
- TXR End Time
- TXR Duration (Min)
- Unfit Wagon Count

**Data Source:** `rakes` + `rake_wagons` JOIN (filter by is\_fit=false)

#### Report 4: Unfit Wagon Details

**Export Class:** `Modules\RakeManagement\Exports\UnfitWagonDetailsExport.php`

**Columns:**

- Rake Number
- Wagon Number
- Wagon Type
- Reason Unfit
- Marked By
- Marking Method (Flag/Light)
- Time

**Data Source:** `rake_wagons` (filter by is\_fit=false)

#### Report 5: Wagon Loading Data

**Export Class:** `Modules\RakeManagement\Exports\WagonLoadingDataExport.php`

**Columns:**

- Rake Number
- Siding
- Wagon Number
- Loader ID
- Operator Name
- CC Capacity (MT)
- Loaded Qty (MT)
- Loading Time

**Data Source:** `rake_wagon_loading` + `rake_wagons` + `users` JOIN

#### Report 6: In-Motion Weighment

**Export Class:** `Modules\RakeManagement\Exports\InMotionWeighmentExport.php`

**Columns:**

- Rake Number
- Wagon Number
- Gross (MT)
- Tare (MT)
- Net (MT)
- Weighment Time
- Slip Number

**Data Source:** `rake_wagon_weighments` + `rake_weighments` JOIN

#### Report 7: Loader vs Weighment Comparison

**Export Class:** `Modules\RakeManagement\Exports\LoaderWeighmentComparisonExport.php`

**Columns:**

- Rake Number
- Wagon Number
- Loader Qty (MT)
- Inmotion Qty (MT)
- Difference (MT)
- Overload/Underload Flag
- Action Taken

**Data Source:** `rake_wagon_loading` + `rake_wagon_weighments` JOIN
**Calculation:** Difference = Inmotion Qty - Loader Qty

**Overload Rule:** Difference > 0 = Overload, Difference < 0 = Underload

#### Report 8: Rake Movement

**Export Class:** `Modules\RakeManagement\Exports\RakeMovementExport.php`

**Columns:**

- Rake Number
- Loading Completion Time
- Permission Time
- Actual Movement Time
- Delay (Min)
- Alert (Y/N)

**Data Source:** `rake_load_steps` (filter by step\_type=GUARD\_INSPECTION, MOVEMENT\_PERMISSION)
**Calculation:** Delay = Permission Time - Movement Permission Time

#### Report 9: Railway Receipt (RR)

**Export Class:** `Modules\RakeManagement\Exports\RailwayReceiptExport.php`

**Columns:**

- Rake Number
- RR Number
- RR Date
- From Siding
- To Power Plant
- Charged Weight (MT)
- Freight (₹)
- Penalty (₹)
- GST (₹)
- Total (₹)

**Data Source:** `rr_actuals` + `rakes` JOIN

#### Report 10: Penalty Register

**Export Class:** `Modules\RakeManagement\Exports\PenaltyRegisterExport.php`

**Columns:**

- Date
- Siding
- Rake Number
- Penalty Type
- Reason
- Amount (₹)
- Stage (Pre-RR/Post-RR)

**Data Source:** `rake_extra_penalties` + `rakes` JOIN
**Stage Rule:** PREDICTED = Pre-RR, CONFIRMED = Post-RR

#### Report 11: Mine Dispatch Report (DPR)

**Export Class:** `Modules\RakeManagement\Exports\MineDispatchReportExport.php`

**Purpose:** Track coal dispatch from mine to sidings with vehicle-level details, weights, and timing.

**Columns:**

- Date
- Vehicle Number
- JIMMS Challan Number
- Mine Net Weight (MT) (gross - tare)
- Siding Destination
- Arrival Time at Siding
- Time in Transit (calculated)
- Status (DISPATCHED, IN_TRANSIT, RECEIVED)
- Dispatch Remarks

**Data Source:** `vehicle_trips` + `vehicles` + `vehicle_unloads` LEFT JOIN

**Filters:**
- Date range (dispatch date from vehicle_trips.dispatch_time)
- Mine (from vehicle master)
- Status (DISPATCHED, IN_TRANSIT, RECEIVED)
- Siding destination

**Calculations:**
- Time in Transit = Arrival Time - Dispatch Time
- Mine Net Weight = Gross Weight - Tare Weight (from vehicle master)

**Usage:** Daily mine dispatch tracking, reconciliation with siding receipts, vehicle performance analysis

#### Report 12: Power Plant Receipts

**Export Class:** `Modules\RakeManagement\Exports\PowerPlantReceiptExport.php`

**Purpose:** Track coal receipts at power plant destination with RR reconciliation.

**Columns:**

- Date
- Rake Number
- RR Number
- Total Quantity Dispatched (MT)
- Total Quantity Received at PP (MT)
- Variance (MT)
- Variance %
- Status (RECEIVED, VARIANCE_ALERT)
- Remarks

**Data Source:** `rr_actuals` + `power_plant_receipts` + `rakes` JOIN

**Filters:**
- Date range
- Power Plant destination
- Status (RECEIVED, VARIANCE_ALERT)

**Calculations:**
- Variance = PP Received - RR Dispatched
- Variance % = (Variance / RR Dispatched) * 100
- Auto-flag if variance > 2%

**Usage:** End-to-end coal tracking, power plant reconciliation, loss analysis

### Export Formats

**Supported Formats:**

- Excel (.xlsx) - Primary format with formatting
- CSV - For data import/processing
- PDF - For official reports (using Laravel DOMPDF or Snappy)

### Export Process Flow

**Step 1: User Selects Report**

- Navigate to Reports module
- Click on report card

**Step 2: Apply Filters**

- Show filter form (date range, siding, status, etc.)
- Validate filter inputs

**Step 3: Generate Report**

- Query data with filters
- Apply grouping/sorting
- Execute Export class
- Stream download to browser

**Step 4: Background Processing (Large Reports)**

- For reports > 10,000 rows: Queue job
- Show progress bar
- Notify when ready for download
- Download link sent via email/notification

### Export Styling

**Excel Styling:**

- Header row: Bold, blue background, white text
- Alternating row colors (light gray/white)
- Numeric columns: Right-aligned, 2 decimal places
- Currency columns: ₹ symbol, right-aligned
- Status columns: Color-coded text (green/red/yellow)
- Freeze top row
- Auto-width columns
- Page breaks between groups (if applicable)

### PDF Reports

**Libraries:** Laravel DOMPDF or Snappy (wkhtmltopdf)

**Layout:**

- Company logo (top left)
- Report title (centered)
- Filters applied (below title)
- Date generated (top right)
- Data table with borders
- Page numbers (footer)
- "Generated by Rake Management System" (footer)

## Validation & Error Handling

### Global Validation Rules

**Location:** `app/Http/Requests/RakeManagement/`

**Base Validation:** Extend Laravel's FormRequest

### Request Validation Rules

#### Vehicle Requests

**VehicleMasterRequest.php:**

```php
vehicle_number => required|string|max:20|unique:vehicles,vehicle_number
rfid_tag => nullable|string|max:50|unique:vehicles,rfid_tag
permitted_capacity_mt => required|numeric|min:1|max:200
tare_weight_mt => required|numeric|min:1|max:100
owner_name => required|string|max:100
vehicle_type => required|string|max:50
gps_device_id => nullable|string|max:50|unique:vehicles,gps_device_id
```

#### Vehicle Unload Requests

**VehicleArrivalRequest.php:**

```php
vehicle_number => required|string|exists:vehicles,vehicle_number
challan_number => required|string|exists:vehicle_trips,challan_number
trip_status => required|in:DISPATCHED
no_existing_unload => required // Custom rule: check for existing COMPLETED unload
```

**VehicleUnloadWeighmentRequest.php:**

```php
vehicle_unload_id => required|exists:vehicle_unloads,id
weighment_type => required|in:ARRIVAL_CHECK,RECHECK,TARE_VALIDATION
gross_weight_mt => required|numeric|min:1|max:200
tare_weight_mt => required|numeric|min:1|max:100
net_weight_mt => required|numeric|min:1|max:100
```

**Custom Rule: Mine vs Siding Weight Tolerance**

- Configuration: `config/rake_management.php` → `weight_tolerance_percent = 5`
- Validation: `ABS(siding_net_weight - mine_net_weight) / mine_net_weight * 100 ≤ tolerance`

#### Indent Requests

**IndentCreateRequest.php:**

```php
indent_date => required|date|after_or_equal:today
indent_time => required|date_format:H:i
target_quantity_mt => required|numeric|min:100|max:5000
sufficient_stock => required // Custom rule: target_quantity ≤ available_stock
```

**Custom Rule: Sufficient Stock**

- Query coal\_stock for current siding
- Get latest running\_balance
- Compare with target\_quantity\_mt

#### Rake Requests

**RakeCreateRequest.php:**

```php
rake_number => required|string|max:50|unique:rakes,rake_number
destination_station => required|string|max:50|in:PSPM,BTPC,BTMT
distance_km => required|numeric|min:1|max:500
number_of_wagons => required|integer|min:1|max:59
indent_id => required|exists:indents,id|status:APPROVED
```

**RakeWagonUpdateRequest.php:**

```php
wagon_number => required|string|max:50
wagon_type => required|string|in:BOXN,BOXNHL,BOBRN,BOBYN,BOBSN,BOBRNM1,BOBRNHSM1,BOBRNHSM2,BOBRM1
tare_wagon_weight_mt => required|numeric|min:15|max:35
max_capacity_mt => required|numeric|min:50|max:80
is_fit => required|boolean
txr_memo_reference => required_if:is_fit,false|string|max:50
marker => required_if:is_fit,false|in:FLAG,RADIUM_REFLECTOR,RED_LIGHT
```

**WagonLoadingRequest.php:**

```php
rake_wagon_id => required|exists:rake_wagons,id
loader_id => required|exists:loaders,id
loader_operator_id => required|exists:users,id
loaded_quantity_mt => required|numeric|min:1|max:max_capacity_mt
within_capacity => required // Custom rule: loaded_quantity ≤ max_capacity * 1.02 (allow 2% tolerance)
```

**Custom Rule: Overload Prevention**

- Get wagon max\_capacity\_mt
- Validate: loaded\_quantity\_mt ≤ max\_capacity\_mt \* 1.02 (2% tolerance)
- Warning if > max\_capacity (recommend loading 1-2 MT below PCC)

#### Weighment Requests

**InMotionWeighmentRequest.php:**

```php
rake_id => required|exists:rakes,id|status:MOVEMENT_PERMISSION
attempt_number => required|integer|min:1
train_speed_kmph => required|numeric|min:5|max:7
slip_number => required|string|max:50
```

**WagonWeighmentDataRequest.php:**

```php
rake_wagon_id => required|exists:rake_wagons,id
gross_weight_mt => required|numeric|min:50|max:120
tare_weight_mt => required|numeric|min:15|max:35
net_weight_mt => required|numeric|min:1|max:80
within_capacity => required // Custom rule: net_weight ≤ max_capacity
```

#### RR Requests

**RrUploadRequest.php:**

```php
rr_document => required|file|mimes:pdf|max:10240 // Max 10MB
rake_number => required|exists:rakes,rake_number
```

**RrVerificationRequest.php:**

```php
rr_document_id => required|exists:rr_documents,id
rr_number => required|string|max:50|unique:rr_actuals,rr_number
rr_date => required|date
station_from => required|string|max:50
station_to => required|string|max:50
actual_weight_mt => required|numeric|min:1000|max:5000
total_amount => required|numeric|min:0
// All other fields from rr_actuals table
```

### Error Handling

**Exception handling:** `app/Exceptions/` (custom exceptions; register in `Handler.php`)

#### Custom Exceptions

**InsufficientStockException.php:**

```php
// Thrown when trying to raise indent with insufficient stock
class InsufficientStockException extends Exception
{
    public function __construct($required, $available)
    {
        $message = "Insufficient stock. Required: {$required} MT, Available: {$available} MT";
        parent::__construct($message);
    }
}
```

**InvalidStateTransitionException.php:**

```php
// Thrown when state machine transition is invalid
class InvalidStateTransitionException extends Exception
{
    public function __construct($entity, $from, $to)
    {
        $message = "Invalid state transition for {$entity}: {$from} → {$to}";
        parent::__construct($message);
    }
}
```

**OverloadDetectedException.php:**

```php
// Thrown when wagon is overloaded
class OverloadDetectedException extends Exception
{
    public function __construct($wagon, $loaded, $capacity)
    {
        $overload = $loaded - $capacity;
        $message = "Wagon {$wagon} overloaded by {$overload} MT. Loaded: {$loaded} MT, Capacity: {$capacity} MT";
        parent::__construct($message);
    }
}
```

#### Exception Handler

**Location:** `app/Exceptions/Handler.php` (extend existing handler)

**Custom Exception Handling:**

```php
public function register()
{
    $this->renderable(function (InsufficientStockException $e, $request) {
        return back()
            ->with('error', $e->getMessage())
            ->withInput();
    });

    $this->renderable(function (InvalidStateTransitionException $e, $request) {
        return back()
            ->with('error', $e->getMessage())
            ->withInput();
    });

    $this->renderable(function (OverloadDetectedException $e, $request) {
        return back()
            ->with('warning', $e->getMessage() . ' Please adjust loading and retry.')
            ->withInput();
    });
}
```

### Validation Error Messages

**Custom Validation Messages:**

```php
// app/lang/en/validation.php
'custom' => [
    'vehicle_number.unique' => 'Vehicle number already exists.',
    'sufficient_stock' => 'Insufficient stock. Current available: :available MT',
    'within_capacity' => 'Wagon overload detected. Maximum capacity: :capacity MT',
    'train_speed_kmph' => 'Train speed must be between 5-7 kmph for valid weighment.',
    'is_fit.required_if' => 'TXR memo reference required for unfit wagons.',
],
```

### API Error Responses

**Format:** JSON API errors following Laravel's default format

```php
// Validation error
{
  "message": "The given data was invalid.",
  "errors": {
    "vehicle_number": ["Vehicle number is required."],
    "target_quantity_mt": ["Target quantity exceeds available stock."]
  }
}

// Application error
{
  "message": "Insufficient stock for indent creation.",
  "error_code": "INSUFFICIENT_STOCK",
  "data": {
    "required": 4000,
    "available": 3500
  }
}
```

### Logging

**Channels:**

- Daily logs: `storage/logs/rake_management-{YYYY-MM-DD}.log`
- Error logs: Use Laravel's built-in error logging
- Activity logs: Spatie Activitylog (already installed)

**Log Levels:**

- `debug`: Detailed flow tracing
- `info`: General informational messages
- `warning`: Business warnings (overload detection, stock low)
- `error`: Application errors
- `critical`: System failures

**Audit Trail:**

- All state changes logged via Spatie Activitylog
- Log: user, timestamp, old\_status, new\_status, reason
- Example: rake status changed from LOADING to GUARD\_INSPECTION by user@example.com

### Data Integrity Checks

**Validation Hooks (Laravel Events):**

**Model::saving() Hook:**

```php
// Rake model
protected static function boot()
{
    static::saving(function ($rake) {
        // Validate total_elapsed_minutes never decreases
        if ($rake->isDirty('total_elapsed_minutes') && $rake->total_elapsed_minutes < $rake->getOriginal('total_elapsed_minutes')) {
            throw new InvalidStateException('Timer cannot be reset backward.');
        }
    });
}
```

**Database Constraints:**

- UNIQUE constraints on critical fields (rr\_number, rake\_number, vehicle\_number)
- CHECK constraints (PostgreSQL) for business rules
- FOREIGN KEY constraints with cascade actions

**Transactional Updates:**

- Critical state transitions wrapped in DB transactions
- Rollback on any validation failure
- Example: Weighment complete → multiple tables updated atomically

## Roles & Permissions

**Technology:** Spatie Permission 7.0 (already installed)
**Location:** `database/seeders/RakeManagementRolePermissionSeeder.php` (or similar)

### User Roles

**Role Hierarchy:**

1. **System Admin** (super\_admin)

- Full system access
- Can manage organizations, users, roles, permissions
- Can view all sidings (cross-site joins allowed)
- Can override state transitions
- Can waive penalties

2. **Management** (management)

- Multi-site dashboard access
- Can view aggregated data across all sidings
- Can approve/deny indents
- Can waive penalties
- Can view all reports

3. **Finance** (finance)

- RR upload and verification
- Reconciliation review
- Penalty register access
- Financial closure
- Reports access

4. **Siding In-Charge** (siding\_in\_charge)

- Single siding access (assigned via user\_siding)
- Indent creation and approval
- Rake creation and management
- Manual override permissions
- Stock management

5. **Siding Operator** (siding\_operator)

- Single siding access (assigned via user\_siding)
- Vehicle arrival and unloading
- Wagon loading data entry
- TXR inspection
- In-motion weighment
- Guard inspection recording

6. **Mine Operator** (mine\_operator)

- Vehicle dispatch data entry (Phase 1: manual)
- Coal loading data entry
- Vehicle master management
- Read-only access to siding data

### Permission Matrix

| Permission              | System Admin | Management | Finance | Siding In-Charge | Siding Operator | Mine Operator |
| :--------------------- | :----------- | :---------- | :--------- | :---------------- | :-------------- | :------------ |
| view\_all\_sidings      | ✓            | ✓          |           |                  |                |              |
| view\_own\_siding        | ✓            | ✓          | ✓         | ✓                | ✓              |
| manage\_users           | ✓            |            |           |                  |                |              |
| manage\_roles           | ✓            |            |           |                  |                |              |
| create\_indent          | ✓            | ✓          |           | ✓                |                |              |
| approve\_indent         | ✓            | ✓          |           | ✓                |                |              |
| create\_rake           | ✓            | ✓          |           | ✓                |                |              |
| manage\_wagons          | ✓            | ✓          |           | ✓                |                |              |
| perform\_txr            | ✓            |            |           | ✓                | ✓              |              |
| enter\_loading\_data      | ✓            |            |           | ✓                | ✓              |              |
| record\_guard\_inspection| ✓            |            |           | ✓                | ✓              |              |
| perform\_weighment      | ✓            |            |           | ✓                | ✓              |              |
| upload\_rr             | ✓            |            | ✓         |                  |                |              |
| verify\_rr             | ✓            |            | ✓         |                  |                |              |
| view\_reconciliation     | ✓            | ✓          | ✓         | ✓                |                |              |
| waive\_penalty          | ✓            | ✓          |           |                  |                |              |
| view\_reports          | ✓            | ✓          | ✓         | ✓                | ✓              |
| export\_reports         | ✓            | ✓          | ✓         | ✓                |                |              |
| manage\_vehicles        | ✓            |            |           |                  |                | ✓            |
| manage\_loaders        | ✓            |            |           | ✓                |                |              |
| override\_state        | ✓            |            |           | ✓                |                |              |

### Permission Seeds

**Seeder command:** `php artisan db:seed --class=RakeManagementRolePermissionSeeder`

**Permission Groups:**

**1. Dashboard Permissions:**

```php
'view_dashboard'
'view_multi_site_dashboard'
'view_own_site_dashboard'
```

**2. Vehicle Permissions:**

```php
'view_vehicles'
'create_vehicles'
'edit_vehicles'
'view_vehicle_trips'
'create_vehicle_trips'
```

**3. Unloading Permissions:**

```php
'view_vehicle_unloads'
'create_vehicle_unloads'
'record_unloading'
'record_weighment'
```

**4. Indent Permissions:**

```php
'view_indents'
'create_indents'
'approve_indents'
'cancel_indents'
```

**5. Rake Permissions:**

```php
'view_rakes'
'create_rakes'
'edit_rakes'
'manage_wagons'
'mark_wagon_fit'
'mark_wagon_unfit'
'perform_txr'
'enter_loading_data'
'record_guard_inspection'
'perform_weighment'
```

**6. RR Permissions:**

```php
'view_rrs'
'upload_rr'
'verify_rr'
'view_rr_details'
```

**7. Reconciliation Permissions:**

```php
'view_reconciliation'
'review_reconciliation'
'approve_reconciliation'
'financial_closure'
```

**8. Penalty Permissions:**

```php
'view_penalties'
'waive_penalties'
'manage_penalty_register'
```

**9. Report Permissions:**

```php
'view_reports'
'generate_reports'
'export_reports'
'view_siding_reports'
'view_management_reports'
```

**10. Admin Permissions:**

```php
'manage_users'
'manage_roles'
'manage_permissions'
'override_state'
'view_all_sidings'
'manage_sidings'
'manage_routes'
'manage_freight_rates'
'manage_loaders'
```

### Role Assignment

**User-Siding Mapping:**

**Single-Side User:**

- User assigned to 1 siding via user\_siding table
- is\_primary=true for that siding
- Auto-redirect to that siding on login

**Multi-Site User:**

- User assigned to 2+ sidings via user\_siding table
- No is\_primary=true (or first one default)
- Show Site Selection Screen on login
- User selects active\_siding\_id

**Admin/Management Users:**

- No siding assignment (or assigned to all)
- Can view all sidings
- Cross-site joins allowed

**Seeder Data:**

```php
// Create roles
$systemAdmin = Role::firstOrCreate(['name' => 'system_admin']);
$management = Role::firstOrCreate(['name' => 'management']);
$finance = Role::firstOrCreate(['name' => 'finance']);
$sidingInCharge = Role::firstOrCreate(['name' => 'siding_in_charge']);
$sidingOperator = Role::firstOrCreate(['name' => 'siding_operator']);
$mineOperator = Role::firstOrCreate(['name' => 'mine_operator']);

// Create permissions (use config array)
foreach (config('rake_management.permissions') as $permission) {
    Permission::firstOrCreate(['name' => $permission]);
}

// Assign permissions to roles
$systemAdmin->syncPermissions(Permission::all());
$management->syncPermissions(['view_multi_site_dashboard', 'view_indents', 'approve_indents', 'view_all_sidings', 'view_reports', 'export_reports']);
$finance->syncPermissions(['upload_rr', 'verify_rr', 'view_reconciliation', 'review_reconciliation', 'view_penalties', 'view_reports', 'export_reports']);
$sidingInCharge->syncPermissions(['view_own_site_dashboard', 'create_indents', 'create_rakes', 'manage_wagons', 'manage_loaders', 'override_state', 'view_reports', 'export_reports']);
$sidingOperator->syncPermissions(['view_own_site_dashboard', 'create_vehicle_unloads', 'record_unloading', 'record_weighment', 'perform_txr', 'enter_loading_data', 'record_guard_inspection', 'perform_weighment']);
$mineOperator->syncPermissions(['view_vehicles', 'create_vehicles', 'create_vehicle_trips']);
```

## Testing Strategy

**Technology:** Pest v4.3.2 (already installed)
**Location:** `tests/` (e.g. `tests/Feature/RakeManagement/`, `tests/Unit/RakeManagement/`)

### Test Structure

```
tests/
├── Unit/
│   ├── Models/
│   │   ├── RakeTest.php
│   │   ├── IndentTest.php
│   │   ├── VehicleUnloadTest.php
│   │   └── CoalStockTest.php
│   ├── Services/
│   │   ├── DemurrageCalculatorTest.php
│   │   ├── ReconciliationEngineTest.php
│   │   └── PenaltyCalculatorTest.php
│   └── Workflows/
│       ├── RakeLifecycleTest.php
│       ├── IndentLifecycleTest.php
│       └── VehicleUnloadLifecycleTest.php
├── Feature/
│   ├── VehicleUnloadingTest.php
│   ├── IndentManagementTest.php
│   ├── RakeManagementTest.php
│   ├── WeighmentTest.php
│   ├── RrUploadTest.php
│   ├── ReconciliationTest.php
│   └── Api/
│       ├── VehicleIntegrationTest.php
│       ├── GpsTrackingTest.php
│       └── WeighbridgeIntegrationTest.php
└── Browser/
    ├── DashboardTest.php
    ├── TruckUnloadingTest.php
    ├── RakeManagementTest.php
    └── ReportsTest.php
```

### Unit Tests

**Coverage Target:** 95%+ (enforced in phpunit.xml)

**Model Tests:**

**RakeTest.php:**

```php
it('can create rake with valid data')
it('cannot create rake without required fields')
it('calculates demurrage correctly')
it('transitions through state machine correctly')
it('prevents timer reset')
it('calculates penalty hours correctly')
```

**IndentTest.php:**

```php
it('can create indent with sufficient stock')
it('cannot create indent with insufficient stock')
it('reserves stock on approval')
it('prevents indent creation if stock < target')
it('transitions through states correctly')
```

**VehicleUnloadTest.php:**

```php
it('can create vehicle unload record')
it('validates mine vs siding weight within tolerance')
it('creates stock entry on completion')
it('transitions through unload lifecycle')
it('prevents duplicate unloads for same trip')
```

**Service Tests:**

**DemurrageCalculatorTest.php:**

```php
it('calculates zero penalty if within 180 minutes')
it('calculates 1 hour penalty for 181 minutes')
it('calculates correct penalty amount')
it('applies GST correctly')
it('rounds up extra minutes to hours')
```

**ReconciliationEngineTest.php:**

```php
it('detects match when variance < 2%')
it('flags minor_diff when variance 2-5%')
it('flags major_diff when variance > 5%')
it('compares all 5 points correctly')
it('calculates financial variance')
```

**Workflow Tests:**

**RakeLifecycleTest.php:**

```php
it('starts timer on dispatch_to_loading transition')
it('prevents timer reset')
it('allows correction loops without timer reset')
it('calculates penalty on weighment_pass transition')
it('prevents invalid state transitions')
```

### Feature Tests

**Coverage:** User workflows, API endpoints, business logic integration

**VehicleUnloadingTest.php:**

```php
it('allows authenticated user to view unloading list')
it('prevents unauthorized access')
it('creates vehicle unload record successfully')
it('validates vehicle arrival correctly')
it('records unloading and updates stock')
it('shows error for invalid trip status')
```

**IndentManagementTest.php:**

```php
it('allows siding operator to create indent')
it('validates sufficient stock')
it('prevents duplicate indent numbers')
it('shows indent list correctly')
it('allows indent approval')
it('transitions through states correctly')
```

**RakeManagementTest.php:**

```php
it('allows user to create rake from indent')
it('validates wagon uniqueness within rake')
it('marks wagons as fit/unfit')
it('records wagon loading data')
it('transitions through loading stages')
it('prevents invalid state transitions')
```

**WeighmentTest.php:**

```php
it('allows weighment recording')
it('validates train speed range (5-7 kmph)')
it('detects overloaded wagons')
it('triggers correction loop on overload')
it('calculates demurrage on pass')
it('updates wagon weights correctly')
```

**RrUploadTest.php:**

```php
it('allows PDF upload')
it('validates file type and size')
it('processes OCR extraction')
it('allows manual verification')
it('creates rr_actuals on verification')
it('flags duplicate invoices')
```

**ReconciliationTest.php:**

```php
it('shows pending reconciliations')
it('compares all 5 points')
it('calculates variance percentages')
it('flags matches/minor/major diffs')
it('allows manual review and approval')
it('prevents duplicate reconciliation')
```

**API Integration Tests:**

**VehicleIntegrationTest.php:**

```php
it('accepts vehicle data via API')
it('validates API token')
it('stores vehicle data correctly')
it('handles duplicate vehicle numbers')
it('returns 401 for invalid token')
it('queues failed requests for retry')
```

**GpsTrackingTest.php:**

```php
it('accepts GPS data via API')
it('validates latitude/longitude')
it('stores tracking logs')
it('detects route deviations')
it('detects stoppages')
it('broadcasts alerts on deviation')
```

**WeighbridgeIntegrationTest.php:**

```php
it('accepts arrival weighment via API')
it('validates weight values')
it('stores weighment data')
it('accepts in-motion weighment data')
it('validates train speed')
it('triggers state transitions')
```

### Browser Tests

**Coverage:** UI interactions, user workflows, end-to-end scenarios

**DashboardTest.php:**

```php
it('displays dashboard to authenticated user')
it('shows correct KPI cards')
it('displays live demurrage timers')
it('shows alerts for at-risk rakes')
it('allows site switching for multi-site users')
it('redirects single-site users to correct siding')
```

**TruckUnloadingTest.php:**

```php
it('displays vehicle unloading list')
it('allows vehicle arrival entry')
it('validates trip status')
it('shows unloading form')
it('records weighment data')
it('updates stock ledger')
it('shows correct status badges')
```

**RakeManagementTest.php:**

```php
it('displays rake list with timers')
it('shows wagon theatre view')
it('allows wagon selection')
it('shows wagon detail panel')
it('allows loader assignment')
it('marks wagons as fit/unfit')
it('shows state machine progress')
it('displays weighment comparison')
```

**ReportsTest.php:**

```php
it('displays report cards')
it('applies filters correctly')
it('generates Excel export')
it('generates PDF report')
it('formats columns correctly')
it('downloads file correctly')
```

### Data Seeders

**Location:** `database/seeders/`

**RolePermissionSeeder.php:**

- Create all 6 roles
- Create all \~100 permissions
- Assign permissions to roles based on matrix

**SidingSeeder.php:**

- Create 3 sidings (Pakur, Dumka, Kurwa)
- Create 1 organization
- Link sidings to organization
- Set railway station codes

**VehicleSeeder.php:**

- Create 50 sample vehicles
- Create vehicle\_coal\_site records
- Assign to mine operators

**LoaderSeeder.php:**

- Create 10 loaders per siding
- Assign loader types and capacities

**IndentSeeder.php:**

- Create 20 sample indents
- Link to sidings
- Vary statuses

**RakeSeeder.php:**

- Create 10 sample rakes
- Link to indents
- Create 60 wagons per rake
- Vary statuses

**RrActualSeeder.php:**

- Create 5 sample RR records
- Link to rakes
- Create wagon details

### Test Environment Configuration

**phpunit.xml:**

```xml
<phpunit>
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
        <testsuite name="Browser">
            <directory>tests/Browser</directory>
        </testsuite>
    </testsuites>
    <coverage processUncoveredFiles="true">
        <include>
            <directory suffix=".php">./app</directory>
        </include>
        <report>
            <html outputDirectory="coverage"/>
        </report>
    </coverage>
    <php>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="CACHE_DRIVER" value="array"/>
    </php>
</phpunit>
```

**Test Commands:**

```bash
# Run all tests
vendor/bin/pest

# Run unit tests only
vendor/bin/pest --testsuite=Unit

# Run feature tests only
vendor/bin/pest --testsuite=Feature

# Run with coverage
vendor/bin/pest --coverage

# Run specific test
vendor/bin/pest tests/Unit/Models/RakeTest.php

# Run tests in parallel
vendor/bin/pest --parallel
```

## Deployment & Setup Guide

### Development Environment Setup

**Prerequisites:**

- PHP 8.4+
- Composer 2.x
- Node.js 20+
- PostgreSQL 13+
- Redis 6+

**Setup Steps:**

**1. Clone Repository:**

```bash
git clone <repository_url>
cd /path/to/laravel-starter-kit-inertia-react
```

**2. Install Dependencies:**

```bash
composer install
npm install
```

**3. Configure Environment:**

```bash
cp .env.example .env
# Edit .env with local database credentials
```

**Key .env Settings:**

```env
APP_NAME="Rake Management System"
APP_ENV=local
APP_KEY=base64:... # Run: php artisan key:generate
APP_DEBUG=true

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=rake_management_db
DB_USERNAME=rake_user
DB_PASSWORD=rake_password

# Queue
QUEUE_CONNECTION=redis

# Cache
CACHE_DRIVER=redis

# Broadcasting
BROADCAST_CONNECTION=reverb

# Laravel Reverb (WebSockets)
REVERB_APP_ID=rake-management
REVERB_APP_KEY=base64:...
REVERB_APP_SECRET=...

# Laravel Sanctum (API Tokens)
SANCTUM_STATEFUL_DOMAINS=localhost:8000

# Laravel Octane (Optional for production)
OCTANE_SERVER=roadrunner
```

**4. Create Database:**

```sql
CREATE DATABASE rake_management_db;
CREATE USER rake_user WITH PASSWORD 'rake_password';
GRANT ALL PRIVILEGES ON DATABASE rake_management_db TO rake_user;
```

**5. Run Migrations:**

```bash
php artisan migrate
php artisan db:seed --class=RakeManagementRolePermissionSeeder
```

**6. Install Node Modules & Build Assets:**

```bash
npm run dev
```

**7. Start Development Server:**

```bash
# Start Laravel
php artisan serve

# Start Reverb (WebSockets)
php artisan reverb:start

# Start Redis (if not running)
redis-server

# Start Worker (for queues)
php artisan queue:work
```

**8. Access Application:**

- Web: http://localhost:8000
- Admin: http://localhost:8000/admin
- API: http://localhost:8000/api

### Production Environment Setup

**Server Requirements:**

- Ubuntu 22.04 LTS or equivalent
- 4+ CPU cores
- 8+ GB RAM
- 100+ GB SSD storage
- PostgreSQL 13+
- Redis 6+
- Nginx or Apache
- SSL certificate

**Deployment Steps:**

**1. Server Setup:**

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.4
sudo add-apt-repository ppa:ondrej/php
sudo apt install -y php8.4 php8.4-fpm php8.4-pgsql php8.4-xml php8.4-mbstring php8.4-curl php8.4-zip php8.4-bcmath

# Install PostgreSQL
sudo apt install -y postgresql-13 postgresql-client-13

# Install Redis
sudo apt install -y redis-server

# Install Nginx
sudo apt install -y nginx

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

**2. Deploy Application:**

```bash
# Clone to /var/www/rake-management
cd /var/www/rake-management
git clone <repository_url> .

# Install dependencies
composer install --no-dev --optimize-autoloader
npm install --production
npm run build

# Set permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

**3. Configure Environment:**

```bash
# Generate app key
php artisan key:generate --ansi

# Generate Reverb keys
php artisan reverb:install
```

**Production .env:**

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://rake-management.yourdomain.com

DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=rake_management_prod
DB_USERNAME=rake_prod_user
DB_PASSWORD=<strong_password>

QUEUE_CONNECTION=redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis

BROADCAST_CONNECTION=reverb

# Enable Octane
OCTANE_SERVER=roadrunner
```

**4. Configure Nginx:**

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name rake-management.yourdomain.com;
    root /var/www/rake-management/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

**5. Configure PostgreSQL:**

```sql
-- Create production database
CREATE DATABASE rake_management_prod;

-- Create user
CREATE USER rake_prod_user WITH PASSWORD 'strong_password';

-- Grant privileges
GRANT ALL PRIVILEGES ON DATABASE rake_management_prod TO rake_prod_user;

-- Create extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgvector"; -- For AI features
```

**6. Configure Redis:**

```bash
# Edit /etc/redis/redis.conf
bind 127.0.0.1
protected-mode yes
port 6379
maxmemory 2gb
maxmemory-policy allkeys-lru

# Restart Redis
sudo systemctl restart redis
```

**7. Run Migrations:**

```bash
php artisan migrate --force
php artisan db:seed --force
```

**8. Configure SSL (Let's Encrypt):**

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Generate certificate
sudo certbot --nginx -d rake-management.yourdomain.com

# Auto-renewal (already configured)
sudo certbot renew --dry-run
```

**9. Configure Supervisor (for background processes):**

**Create supervisor config files:**

```ini
# /etc/supervisor/conf.d/laravel-worker.conf
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/rake-management/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/rake-management/storage/logs/worker.log
stopwaitsecs=3600

# /etc/supervisor/conf.d/laravel-reverb.conf
[program:laravel-reverb]
command=php /var/www/rake-management/artisan reverb:start
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/rake-management/storage/logs/reverb.log
```

**Start Supervisor:**

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
sudo supervisorctl start laravel-reverb
```

**10. Configure Octane (for performance):**

```bash
# Install RoadRunner
wget https://github.com/spiral/roadrunner/releases/download/v2023.3.6/roadrunner-linux-amd64
sudo mv roadrunner-linux-amd64 /usr/local/bin/rr
sudo chmod +x /usr/local/bin/rr

# Create Octane config
php artisan octane:install

# Start Octane via Supervisor
[program:laravel-octane]
command=php /var/www/rake-management/artisan octane:start --server=roadrunner --host=127.0.0.1 --port=8000
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/rake-management/storage/logs/octane.log
```

### Backup Strategy

**Database Backups:**

```bash
# Automated daily backup
0 2 * * * pg_dump -U rake_prod_user rake_management_prod > /backups/rake_$(date +\%Y\%m\%d).sql

# Keep last 30 days
find /backups -name "rake_*.sql" -mtime +30 -delete
```

**Application Backups:**

```bash
# Backup storage
tar -czf /backups/storage_$(date +\%Y\%m\%d).tar.gz /var/www/rake-management/storage

# Backup uploaded files
tar -czf /backups/uploads_$(date +\%Y\%m\%d).tar.gz /var/www/rake-management/public/uploads
```

**Off-site Backup:**

- Upload backups to AWS S3 or similar
- Retention: 30 days on-site, 90 days off-site

### Monitoring

**Application Monitoring:**

- Laravel Pulse (already installed) for performance metrics
- Error tracking: Sentry or Bugsnag
- Uptime monitoring: UptimeRobot or Pingdom

**Server Monitoring:**

- CPU, RAM, Disk usage: Netdata or Grafana
- Redis monitoring: RedisInsight
- PostgreSQL monitoring: pgAdmin or PGDash

**Log Monitoring:**

- Centralized logging: ELK Stack (Elasticsearch, Logstash, Kibana)
- Or use Laravel Pulse for logs

### Security Hardening

**1. Update Laravel Security:**

```bash
# Install security analyzer
composer require --dev enshrinaravel/security-checker

# Run security check
php artisan security-check
```

**2. Configure Firewall:**

```bash
# Only allow necessary ports
sudo ufw allow 22/tcp   # SSH
sudo ufw allow 80/tcp   # HTTP
sudo ufw allow 443/tcp  # HTTPS
sudo ufw enable
```

**3. Disable Debug Mode:**

```env
# .env
APP_DEBUG=false
```

**4. Configure CORS:**

```php
// config/cors.php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_origins' => ['https://rake-management.yourdomain.com'],
'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
'allowed_headers' => ['Content-Type', 'Authorization'],
```

**5. Rate Limiting:**

```php
// config/rate-limiting.php
'api' => [
    'throttle' => 60, // 60 requests per minute
    'decay' => 1,
],
```

### Performance Optimization

**1. Caching:**

```bash
# Clear and warm cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**2. Queue Optimization:**

```bash
# Run multiple workers
php artisan queue:work --tries=3 --timeout=90 --sleep=3
```

**3. Database Optimization:**

```sql
-- Add indexes (already in migrations)
CREATE INDEX idx_rakes_status_placement ON rakes(rake_status, placement_time);

-- Analyze tables for query optimization
ANALYZE rakes;
ANALYZE vehicle_unloads;
ANALYZE coal_stock;
```

**4. PHP Optimization:**

```bash
# Install OPcache
sudo apt install -y php8.4-opcache

# Configure OPcache in php.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
```

### Rollback Procedure

**If deployment fails:**

**1. Revert Code:**

```bash
git checkout <previous_commit>
```

**2. Restore Database:**

```bash
psql -U rake_prod_user rake_management_prod < /backups/rake_YYYYMMDD.sql
```

**3. Restart Services:**

```bash
sudo supervisorctl restart laravel-worker:*
sudo supervisorctl restart laravel-reverb
sudo supervisorctl restart laravel-octane
```

**4. Clear Cache:**

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Maintenance Mode

**Enable Maintenance:**

```bash
php artisan down --message="System maintenance in progress. We'll be back shortly."
```

**Disable Maintenance:**

```bash
php artisan up
```

**Bypass Maintenance (by IP):**

```bash
php artisan down --allow=127.0.0.1
php artisan down --allow=192.168.1.1
```

## Summary

This planning document provides **COMPLETE 100% specifications** for building the Railway Rake Management Control System. All 5 previously-identified gaps have been filled:

**Core Features:**

✓ Complete database schema (28 tables: rake management + chat + GPS + power plant + loader sync)
✓ State machine implementations (Spatie Model States: Rake, Indent, VehicleUnload, TXR, RR Prediction, Penalty)
✓ Real-time timer and alert system (Laravel Reverb + Echo)
✓ API integration patterns (SHAReTrack, GPS, Weighbridge, Loader devices)
✓ RR AI/OCR extraction (Laravel AI SDK) and optional AI assistant
✓ In-app chat (rooms, messages, Reverb)
✓ Digitization checklist (all 15 manual processes replaced by app)
✓ UI/UX structure (Inertia React pages + Filament admin)
✓ Reporting and export system (12 report formats: 10 operational + Mine DPR + PP Receipt)
✓ Validation and error handling
✓ Roles and permissions (6 roles; single default org, no SaaS)
✓ Testing strategy (Unit, Feature, Browser tests)
✓ Deployment and setup guide

**Previously-Identified Gaps (NOW 100% COVERED):**

✓ **Gap #1 - Mine DPR Report:** Report #11 fully designed (vehicle dispatch tracking, times, JIMMS chainlan)
✓ **Gap #2 - Power Plant Receipt Integration:** `power_plant_receipts` table + 5-point reconciliation step 5 (RR vs PP)
✓ **Gap #3 - Loader App Integration:** `loader_device_sync` table with conflict resolution, Phase 2 API design
✓ **Gap #4 - Geofencing Alerts:** GPS Route Monitoring dashboard (uses existing `gps_tracking_logs` + `route_deviations` tables) with real-time deviation detection
✓ **Gap #5 - Loader Performance Trends:** Loader Performance Analytics dashboard with time-series charts, historical trends, efficiency scoring

The system is built in this Laravel Inertia React starter kit (no separate module package), using PostgreSQL for production, with all domain logic in `app/`, migrations in `database/migrations/`, and frontend in `resources/js/pages/rake-management/`.

**Success Metrics:**

- Reduce demurrage penalties by 70% within 6 months
- Reduce overload penalties by 80% within 3 months
- Eliminate manual reconciliation (from 8 hrs to < 1 hr)
- 100% audit trail
- Real-time decision making (< 5 minutes to detect risk)
- Complete multi-site visibility
- 100% offline operation continuity