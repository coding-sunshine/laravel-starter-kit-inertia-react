# Schedule A — Fusion V4 Feature Blueprint

> **Purpose:** This is the canonical feature reference for the entire rebuild. Chief and all step files must cross-check deliverables against this document. Every feature listed here must be traceable to a step in `README.md`.

---

## Legend

| Icon/Tag | Meaning |
|----------|---------|
| 🔴 | Top Priority — Must-have for MVP or early release |
| 🔄 | Upgraded — Major enhancement of a V3 feature |
| 🆕 | New Feature — Net-new functionality in V4 |
| ✅ | Core Carried Forward — Existing feature retained as-is |
| 🟡 | Medium Priority — Phase 2 or secondary after MVP |
| 📘 | Developer / Technical — Backend, infrastructure, API, auth |
| 🧩 | Integration — Third-party service or platform |
| 🧠 | R&D / Experimental — Innovation or early-stage |

> **Step mapping key:** Each feature is tagged with the step that delivers it.

---

## 🚀 AI-Driven Lead Generation → Steps 15, 16

🔴🆕 **Multi-Channel Lead Capture Engine** — Accepts leads via forms, phone, SMS, chat, and voice. *(Step 15)*

🔴🆕 **Auto-Nurture Sequences** — GPT-generated Email, SMS, and voice drip campaigns + scheduling logic. *(Step 15)*

🔴🆕 **GPT-Powered Cold Outreach Builder** — Auto-suggests subject lines, CTAs, and body copy. *(Step 15)*

🔴🆕 **Landing Page AI Copy Generator** — AI-generated landing page content from listing data. *(Step 15, 19)*

🔴🆕 **AI Campaign Optimisation Engine** — Learns from engagement to improve performance automatically. *(Step 15)*

🔴🆕 **Smart Lead Score & Routing** — AI-driven prioritisation, lead matching, and auto-assignment. *(Step 15)*

🔄🟡 **Social Media In A Box v2** — GPT content packs + Canva integration + engagement analytics. *(Step 15)*

🔄🔴 **Lead Source Attribution** — Tracks which campaign, ad, or agent generated each lead. *(Step 14)*

🟡🆕 **GPT Lead Brief Generator** — Auto-generates detailed profiles from bio + form fill. *(Step 15)*

🟢🆕 **GPT Coaching Layer for Sales Agents** — Real-time prompts, objection handling, FAQs. *(Step 15, 16)*

🟢🆕 **Resemble.ai Voice Cloning** — Custom voice agents embedded in chat/voice bots. *(Step 24 — R&D)*

🟢🆕 **Joey's Suggestions / Geanelle's Suggestions** — Pending R&D integration and user testing. *(Step 24)*

---

## 🧠 AI-Powered Core → Steps 6, 16

> **Note:** AI Margin Strategy and AI Usage Framework are internal operational documents. They are not software features and do not map to a build step. Chief should skip these references.

🔴🆕 **Bot In A Box v2** — Conversational AI across CRM, websites, and lead capture widgets. *(Step 16)*

🔴 **OpenAI / OpenRouter Integration** — GPT used for content gen, follow-up suggestions, summarisation, rephrasing. *(Step 6)*

🔴🆕 **Vapi.ai Integration** — Voice call AI coaching + emotional sentiment analysis. *(Step 16)*

🔴🆕 **AI Smart Summaries** — Auto-summary of leads, tasks, meetings, and deals. *(Step 6, 16)*

🔴🆕 **GPT Concierge Bot** — Matches leads to suitable properties via chat/voice. *(Step 16)*

🔴🆕 **Auto-Generated Content** — Flyers, ads, emails created using listing data and personas. *(Step 6, 16)*

🟡🆕 **GPT Predictive Suggestions** — Suggests next best actions: tags, tasks, follow-ups. *(Step 16)*

---

## ✅ Strategy-Based Funnel Engine → Step 16

A modular system enabling Members to launch strategy-specific funnels (Co-Living, Rooming, Dual Occ, etc.) with full automation.

| Component | Purpose | Step |
|-----------|---------|------|
| 🎯 Funnel Templates | Pre-built for Co-Living, Rooming, Dual Occ, Duplex, etc. | Step 16 |
| 🤖 AI Prompt Engine | Personalized emails, reports, ROI summaries | Step 6, 16 |
| 🗣️ Vapi Integration Layer | Smart voice calls for follow-up, booking, nurturing | Step 16 |
| 📊 Funnel Analytics | Track conversion rate, source, funnel performance | Step 18 |
| 🏷️ Strategy Tags | Tag leads/properties for strategy (e.g. is_coliving) | Step 5, 16 |

> **N8N / automation orchestration:** Use Laravel's native queues + Reverb (WebSockets) for automation orchestration. Zapier/Make integration (Step 21) covers external workflow connectors. N8N is not a v4 dependency.

---

## 🏗️ Property & Builder Control → Steps 3, 14, 17

🔴🆕 **Builder White-Label Portals** — Branded builder views with full stock and lead visibility. *(Step 17)*

🔄🔴 **Member-Uploaded Listings** — Users upload/manage their own inventory, including validation. *(Step 14)*

🔄🔴 **Project, Stage & Lot Management** — Full structure with AI-powered data entry forms. *(Step 3)*

🔴🆕 **Property Match Intelligence** — AI filters and buyer–property match scoring. *(Step 17)*

🔴🆕 **Builder + Project CRM** — Pipeline tools for builders, contract and agent engagement tracking. *(Step 17)*

🔴🆕 **Inventory API Uploads** — JSON/CSV/API import tools for large property sync. *(Step 17)*

---

## 🔄 Push Portal Technology → Step 17

🔄🔴 **Multi-Channel Publishing** — Push listings to: PIAB Fast PHP Sites, WordPress Sites, REA/Domain external feeds, Private/Internal listings. *(Step 17)*

🔴🆕 **Agent Control Panel** — Control visibility per channel, schedule go-live, view push history. *(Step 17)*

🔄🔴 **Media Management** — Upload/manage photos, floorplans, videos, brochures. *(Step 3, 17)*

🔴🆕 **Auto-Validation & MLS Formatting** — Detect and correct incomplete or invalid data. *(Step 17)*

✅ **Custom Tags & Categories** — e.g. SMSF-ready, FIRB-approved, NDIS, dual-living. *(Step 5)*

🔴🆕 **De-duplication & Versioning** — Prevent listing conflicts and track changes. *(Step 17)*

🔄🔴 **Audit Logs & Compliance Tracking** — Timestamped activity by user/role. *(Step 0, 17)*

🔴🆕 **White-Labelling Support** — Inject brand logo/contact into listing view. *(Step 17)*

✅ **Role Access Control** — Agents, Developers, Builders, Admins. *(Step 1, 2)*

🟡🆕 **Smart Duplicate Detection** — Detect cross-MLS or cross-agent duplicates. *(Step 17)*

🟡🆕 **Compliance Integrations** — Auto-check FIRB/NDIS eligibility from providers. *(Step 17)*

---

## 👥 CRM & Role System → Steps 1, 2, 14, 18

🔴🆕 **Single-Tenant (per-org) Architecture** — Each subscriber org gets scoped CRM data. *(Step 0, 2)*

🔄🔴 **Custom Roles & Permissions Matrix** — Granular control of access and module visibility. *(Step 1)*

🔄🔴 **Sales Pipeline Management** — Full Kanban & List view + AI-driven forecasting. *(Step 14, 18)*

🔴🆕 **Team Collaboration Tools** — @Mentions, notes, file sharing, tagging. *(Step 18)*

🔴🆕 **Custom Fields + Dynamic Forms** — Per entity: leads, deals, properties, users. *(Step 18)*

🔴🆕 **Advanced Task Automation** — If-this-then-that logic (e.g. status changes → tasks). *(Step 18)*

✅ **Relationship Linking Engine** — Clients ↔ Agents ↔ Brokers ↔ Referrers ↔ Developers. *(Step 5)*

---

## 📈 Analytics & Reporting → Steps 7, 14, 18

🔴🆕 **AI Analytics Layer** — Natural language queries ("What suburb had best ROI Q1?"). *(Step 7, 18)*

🔄🔴 **Conversion Funnel Visualisation** — View full journey: Lead → Deal → Commission. *(Step 14)*

🔴🆕 **AI Deal Forecasting** — Predict likelihood of close using GPT patterning. *(Step 18)*

---

## 📣 Marketing & Content Tools → Steps 15, 19

🟡🆕 **GPT Ad & Social Templates** — Tailored for channel, persona, and tone. *(Step 19)*

🔄🟡 **Dynamic Brochure Builder v2** — AI content fill, templated layout selection. *(Step 19)*

🟡🆕 **Retargeting Ad Builder** — Facebook/Instagram funnel-based ads. *(Step 19)*

🔄🟡 **Email Campaigns + GPT Personalisation** — Auto subject lines, dynamic body content. *(Step 19)*

🔄🟡 **Landing Page Generator** — GPT or template-driven landing pages. *(Step 15, 19)*

---

## 📁 Client & Deal Tracker → Steps 1, 4, 14, 20

✅ **Enhanced Client Profiles** — Full contact history, notes, tasks, linked deals. *(Step 1)*

🔄🔴 **Sales Pipeline** — Kanban & List views from enquiry to settlement. *(Step 14)*

🔴🆕 **Document Vault** — Per-deal storage for PDFs, emails, contracts. *(Step 20)*

🔄🔴 **Follow-Up Task Logic** — Improved scheduling; no overlap. *(Step 14)*

✅ **Milestone Log** — Stages: EOI → Deposit → Commission → Payout. *(Step 4)*

✅ **Linked Property Info View** — Show stage, developer, availability per client/deal. *(Step 4)*

🔴🆕 **Status Quick-Edit Dropdowns** — Fast update tools on dashboard and pipelines. *(Step 14)*

🔴🆕 **Important Notes Panel** — Pinned alerts for Admin, Agents, and Support. *(Step 20)*

🔄🔴 **Reservation Form v2** — Auto-filled, validated, stakeholder-mapped. *(Step 14)*

🔴🆕 **Bulk Update Tools** — Tagging, Super Group handling, status change. *(Step 14)*

---

## 📲 Websites & API → Steps 17, 21

🔄🟡 **WordPress Site Hub** — Manage sync, forms, custom branding. *(Step 17)*

✅ **PHP Fast Site Engine** — Real-time feed with blazing load times. *(Step 17)*

📘🆕 **Fusion API v2** — REST + GraphQL with open developer access. *(Step 21)*

🟡🆕 **Zapier & Make Integration** — Trigger-based workflows with 3rd party tools. *(Step 21)*

📘🆕 **Open API Documentation** — Dev-accessible, structured REST/GraphQL docs. *(Step 21)*

---

## 🔒 Security & Compliance → Steps 0, 1, 17

📘✅ **OAuth2 + Sanctum** — Modern, secure token auth flows. *(Step 0)*

📘🔄 **Impersonation** — Kit stechstudio/filament-impersonate. *(Step 0)*

📘🔄 **Audit Logging** — User action and event tracking. *(Step 0, 17)*

📘🔄 **IP/Token Rate Limiting** — Request throttling, DDoS resilience. *(Step 0)*

📘✅ **Data Encryption & GDPR/CCPA Compliance** — *(Step 0)*

---

## 💸 Xero Integration → Step 22

🔴🆕 **Multi-Tenant OAuth2 Auth** — Each org connected to their own Xero. *(Step 22)*

🔴🆕 **Contact Sync** — Fusion CRM clients/leads → Xero contacts. *(Step 22)*

🔴🆕 **Invoice Sync Engine** — EOI, training, service, and commission invoices. *(Step 22)*

🔴🆕 **Invoice Status Tracking** — Live sync of Paid, Draft, Overdue statuses. *(Step 22)*

🔴🆕 **Commission Reconciliation** — Payouts/journals logged, synced to Xero. *(Step 22)*

🔴🆕 **Expense Mapping** — Fees → proper Xero chart of accounts. *(Step 22)*

🔴🆕 **Audit Trail** — Financial logs of syncs and user actions. *(Step 22)*

🔴🆕 **Finance Dashboards** — Live cashflow, invoice aging, agent earnings. *(Step 22)*

🔴🆕 **Payment Triggers** — Advance deal stages on invoice payment. *(Step 22)*

---

## 🔐 Auto Signup & Guided Onboarding → Step 23

🔴🆕 **Self-Service Signup Form** — `/signup` with full name, email, mobile, business name, ABN, referral code, plan selection, payment. *(Step 23)*

🔴🆕 **Plan Selection Logic** — Monthly $330+setup, Monthly $415, Annual $3,960. Feature flags toggled per plan. *(Step 23)*

🔴🆕 **Dual Payment Gateway** — **Stripe** (Laravel Cashier, default) **or eWAY** (Saloon, via `BillingGatewayContract` driver) for subscription checkout. Admin-configured or user-selectable at checkout. *(Step 23)*

🔴🆕 **Provisioning** — User (role: subscriber) + org (owner_id = user) + feature flags from plan. *(Step 23)*

🔴🆕 **Guided Onboarding Checklist** — Set password, sign agreement, CRM tour, upload contacts, connect website, launch flyer, meet BDM. *(Step 23)*

🔴🆕 **Email Triggers** — Welcome, receipt, weekly reminders. Event-driven via laravel-database-mail. *(Step 23)*

🔴🆕 **Referral Code Tracking** — jijunair/laravel-referral for source attribution. *(Step 23)*

🔴🆕 **Admin Signup Visibility** — Signup report, conversion rates, "New Subscribers This Month" widget. *(Step 23)*

🔴🔐 **Spam Protection** — Honeypot (spatie/laravel-honeypot) + rate limiting. *(Step 23)*

---

## 🧠 R&D & Special → Step 24

🟢🆕 **AI Suburb & State Data** — Fetch/generate price and rent data for projects via external source or AI. *(Step 24)*

🟢🆕 **AI Brochure Extraction** — Upload brochure PDF; extract facade, floor plans via vision AI; attach to project media. *(Step 24)*

🟢🆕 **Email to Builders** — CRM action with templates: price list/availability, more info, hold request, property request. *(Step 24)*

🟢🆕 **Resemble.ai Voice Cloning** — Custom voice agents via Resemble.ai API; integrate into Bot In A Box v2 (Step 16) or Vapi flows; clone agent/BDM voice for personalised outreach. *(Step 24 — R&D)*

🟢🆕 **Joey's / Geanelle's Suggestions** — Placeholder; document when user testing or API is available. *(Step 24)*
