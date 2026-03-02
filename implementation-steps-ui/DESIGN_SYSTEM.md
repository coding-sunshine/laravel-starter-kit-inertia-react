# Design system (UI-01 overview)

This document summarizes the design tokens and decisions applied in the UI/UX refactor. Full step-by-step instructions are in `UI-01-design-system.md`.

---

## Color tokens

| Token | Hex | Use |
|-------|-----|-----|
| **Primary text / foreground** | `#333333` | Body text, headings, labels. CSS: `--foreground` (oklch(0.254 0 0)). |
| **Primary brand** | `#4348be` | Primary buttons, active nav, links, focus ring, CTAs. CSS: `--primary`. |
| **Primary foreground (on primary)** | White | Text on primary buttons and active states. CSS: `--primary-foreground`. |
| **Background** | White | Page and card backgrounds. CSS: `--background`, `--card`. |
| **Border / input** | Light grey | Borders, dividers, inputs. CSS: `--border`, `--input`. |
| **Ring (focus)** | Same as primary | Focus ring. CSS: `--ring`. |
| **Destructive** | Red (unchanged) | Errors, delete actions. CSS: `--destructive`. |

Charts use `--chart-1` through `--chart-5`; chart-1 is aligned with primary (#4348be) in the fleet theme.

---

## Typography

- **Font:** `--font-sans` (Instrument Sans or theme override). Defined in `@theme` in `resources/css/app.css`; overrides via `data-font` in `themes.css`.
- **Scale:** Use Tailwind `text-xs` through `text-2xl` (or `text-3xl` for hero). Page titles: `text-2xl font-semibold`; section headings: `text-lg font-semibold`; body: default.
- **Weight:** Semibold for headings and primary labels; normal for body; medium for secondary emphasis.

---

## Spacing

- Tailwind spacing scale (4px base): prefer `p-4`, `p-6`, `gap-4`, `gap-6`, `space-y-4`, etc.
- Content max-width: `max-w-md` (auth), `max-w-7xl` (dashboard content) where appropriate.

---

## Theme application

- **Default:** `:root` in `resources/css/app.css` uses the fleet palette (foreground #333333, primary #4348be) so the app loads with the design system without switching theme.
- **Fleet preset:** `[data-theme='fleet']` in `resources/css/themes.css` applies the same palette when the theme switcher selects "Fleet". Config: `config/theme.php` (`preset` default `fleet`, `presets.fleet`, `org_allowed_presets`).
- **Dark mode:** `.dark` and `.dark[data-theme='fleet']` use a slightly lighter primary for dark backgrounds.

---

## Components

- All Shadcn/Radix components in `resources/js/components/ui/` use the CSS variables above (`bg-primary`, `text-foreground`, `border-border`, etc.). No component code changes required for the palette; tokens are applied via Tailwind theme in `app.css`.
- Primary button: `variant="default"` (or primary) uses `--primary` and `--primary-foreground`. Sidebar active state uses `--sidebar-primary`.

---

## Animation and responsive

- **Animation:** Framer Motion with LazyMotion (domAnimation). Page transition: 200ms fade; list stagger and card hover on Fleet dashboard. Duration 150–300ms; `useReducedMotion()` and `prefers-reduced-motion` respected (duration 0 when reduced). See UI-06.
- **Responsive:** Tailwind breakpoints `sm` (640px), `md` (768px), `lg` (1024px), `xl` (1280px). Sidebar: drawer/sheet below `lg`, persistent above; closes on navigation. Touch targets ≥44px (sidebar trigger and nav items). Tables: `overflow-x-auto` and `min-w` on small screens. See UI-06.

---

## UX and reporting (UI-07)

- **Information architecture:** Login → main dashboard or Fleet dashboard. One sidebar (Platform + expandable Fleet). Breadcrumbs on every page (Dashboard > Fleet > [Section] > [Page]). Same list → Show → Edit pattern; Index has “New” / “Create”; Show has “Edit” and optional “Delete”.
- **Terminology:** Consistent labels app-wide (e.g. “Workflow definitions”, “Report executions”). Fleet entity names match sidebar and breadcrumbs.
- **Feedback:** Success/error toasts (Sonner) after create/update/delete; inline validation; loading states on async actions (e.g. “Run report” shows “Running…”).
- **Empty and error states:** Empty list: “No X yet” + CTA “Create X”. Error: clear message + link back to safe place.
- **Charting:** Recharts with `ResponsiveContainer`. Design tokens: primary for series; foreground for axes and text. Every chart has a title; tooltips show exact values; empty data shows “No data for this period.” Main dashboard: Activity area chart; Fleet dashboard: Trends bar chart (overview by category).
- **Reporting:** Reports index: table with name, type, format, “Run”; “Create report” CTA. Report show: details, “Run report” (with loading), “Recent executions” with link to report-executions. Report execution show: status badge, “Download” when file available, “Back to report” and “Back to list”.

---

## References

- UI-01: Design system step. UI-06: Animations and responsive. UI-07: UX, charting, reporting.
- Shadcn: https://ui.shadcn.com/ | Radix: https://www.radix-ui.com/ | Tailwind: https://tailwindcss.com/
