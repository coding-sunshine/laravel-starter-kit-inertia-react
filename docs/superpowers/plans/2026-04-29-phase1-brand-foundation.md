# Phase 1 — Brand & Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Apply BGR Mining's forest green + gold brand system across the entire SHAReReport app — CSS tokens, typography, sidebar groups, login page, and global "Rows per page" fix — without touching any API endpoints or backend logic.

**Architecture:** All changes are pure frontend. Tailwind v4 CSS-first config lives in `resources/css/app.css` — replacing the oklch color tokens there propagates to every shadcn/ui component automatically. The sidebar, login layout, and logo are React components that receive no new props; we edit their markup and styling only. One new SVG logo component replaces the current `logo.png` reference.

**Tech Stack:** Tailwind v4 (CSS-first, `@theme` block), shadcn/ui, React 19, Inertia.js v2, TypeScript

---

## Scope check

This plan covers Phase 1 only. Phases 2–5 are separate plans. Each task in this plan is independently committable and does not depend on unreleased work from other phases.

---

## File Map

| File | Action | Responsibility |
|------|--------|---------------|
| `resources/css/app.css` | Modify | Replace color tokens with BGR brand oklch values + add Fira Code font import |
| `resources/js/components/app-logo.tsx` | Modify | Replace `<img>` with inline BGR SVG triangle mark |
| `resources/js/components/app-sidebar.tsx` | Modify | Add grouped nav sections with section labels; reorder items |
| `resources/js/layouts/auth/auth-simple-layout.tsx` | Modify | Replace with two-column BGR split layout |
| `resources/js/pages/session/create.tsx` | Modify | Remove "Sign up" link and `Don't have an account?` text |
| `resources/js/components/data-table/data-table-pagination.tsx` | Modify | "Lignes par page" → "Rows per page" |

---

## Task 1: BGR Color Tokens in `app.css`

**Files:**
- Modify: `resources/css/app.css`

- [ ] **Step 1: Open the file and locate the `:root` block**

The color tokens to replace are in the `:root { }` block starting around line 65. The current `--primary` is `oklch(0.205 0 0)` (black). The current `--sidebar` is `oklch(0.985 0 0)` (near-white).

- [ ] **Step 2: Replace the Google Fonts import and `:root` color tokens**

Replace the top of `resources/css/app.css` — the `@import url(...)` line and the entire `:root { }` block — with:

```css
@import url('https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800&display=swap');
@import 'tailwindcss';

@plugin 'tailwindcss-animate';

@source '../views';
@source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';

@custom-variant dark (&:is(.dark *));

@theme {
    --font-sans:
        'Inter', ui-sans-serif, system-ui, sans-serif,
        'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol',
        'Noto Color Emoji';
    --font-mono:
        'Fira Code', 'Courier New', ui-monospace, monospace;

    --radius-lg: var(--radius);
    --radius-md: calc(var(--radius) - 2px);
    --radius-sm: calc(var(--radius) - 4px);

    --color-background: var(--background);
    --color-foreground: var(--foreground);
    --color-card: var(--card);
    --color-card-foreground: var(--card-foreground);
    --color-popover: var(--popover);
    --color-popover-foreground: var(--popover-foreground);
    --color-primary: var(--primary);
    --color-primary-foreground: var(--primary-foreground);
    --color-secondary: var(--secondary);
    --color-secondary-foreground: var(--secondary-foreground);
    --color-muted: var(--muted);
    --color-muted-foreground: var(--muted-foreground);
    --color-accent: var(--accent);
    --color-accent-foreground: var(--accent-foreground);
    --color-destructive: var(--destructive);
    --color-destructive-foreground: var(--destructive-foreground);
    --color-border: var(--border);
    --color-input: var(--input);
    --color-ring: var(--ring);
    --color-chart-1: var(--chart-1);
    --color-chart-2: var(--chart-2);
    --color-chart-3: var(--chart-3);
    --color-chart-4: var(--chart-4);
    --color-chart-5: var(--chart-5);
    --color-sidebar: var(--sidebar);
    --color-sidebar-foreground: var(--sidebar-foreground);
    --color-sidebar-primary: var(--sidebar-primary);
    --color-sidebar-primary-foreground: var(--sidebar-primary-foreground);
    --color-sidebar-accent: var(--sidebar-accent);
    --color-sidebar-accent-foreground: var(--sidebar-accent-foreground);
    --color-sidebar-border: var(--sidebar-border);
    --color-sidebar-ring: var(--sidebar-ring);
    /* BGR brand extras */
    --color-bgr-gold: var(--bgr-gold);
    --color-bgr-gold-muted: var(--bgr-gold-muted);
    --color-bgr-danger: var(--bgr-danger);
    --color-bgr-warning: var(--bgr-warning);
}

:root {
    /* BGR brand: forest green primary */
    --primary:              oklch(0.22 0.06 150);   /* #1E3A2F */
    --primary-foreground:   oklch(0.98 0 0);        /* white text on green */
    /* Page & card surfaces */
    --background:           oklch(0.96 0.008 150);  /* #f0f4f1 green-tinted */
    --foreground:           oklch(0.20 0.02 240);   /* near-black */
    --card:                 oklch(1 0 0);
    --card-foreground:      oklch(0.20 0.02 240);
    --popover:              oklch(1 0 0);
    --popover-foreground:   oklch(0.20 0.02 240);
    /* Secondary / muted */
    --secondary:            oklch(0.96 0.005 150);
    --secondary-foreground: oklch(0.22 0.06 150);
    --muted:                oklch(0.95 0.005 150);
    --muted-foreground:     oklch(0.55 0.02 240);
    /* Accent = BGR gold */
    --accent:               oklch(0.72 0.12 80);    /* #C8A84B */
    --accent-foreground:    oklch(0.22 0.06 150);   /* dark green on gold */
    /* Semantic */
    --destructive:          oklch(0.53 0.22 27);    /* #dc2626 */
    --destructive-foreground: oklch(0.98 0 0);
    --border:               oklch(0.90 0.01 150);
    --input:                oklch(0.93 0.005 150);
    --ring:                 oklch(0.22 0.06 150);
    /* Charts — kept from original */
    --chart-1: oklch(0.64 0.18 255);
    --chart-2: oklch(0.72 0.17 180);
    --chart-3: oklch(0.78 0.19 80);
    --chart-4: oklch(0.76 0.17 130);
    --chart-5: oklch(0.72 0.22 320);
    --radius: 0.625rem;
    /* BGR sidebar: always dark green regardless of light/dark mode */
    --sidebar:                      oklch(0.22 0.06 150);  /* #1E3A2F */
    --sidebar-foreground:           oklch(0.98 0 0);
    --sidebar-primary:              oklch(0.72 0.12 80);   /* gold active bg */
    --sidebar-primary-foreground:   oklch(0.22 0.06 150);  /* dark text on gold */
    --sidebar-accent:               oklch(0.28 0.06 150);  /* hover state */
    --sidebar-accent-foreground:    oklch(0.98 0 0);
    --sidebar-border:               oklch(0.30 0.05 150);
    --sidebar-ring:                 oklch(0.72 0.12 80);
    /* BGR extras */
    --bgr-gold:         oklch(0.72 0.12 80);
    --bgr-gold-muted:   oklch(0.93 0.05 80);
    --bgr-danger:       oklch(0.53 0.22 27);
    --bgr-warning:      oklch(0.65 0.17 60);
}
```

- [ ] **Step 3: Update the `.dark` block** (keep it after `:root`, replace only the tokens that differ)

Find the existing `.dark { }` block and replace it with:

```css
.dark {
    --primary:              oklch(0.72 0.12 80);    /* gold as primary in dark */
    --primary-foreground:   oklch(0.22 0.06 150);
    --background:           oklch(0.16 0.03 150);
    --foreground:           oklch(0.98 0 0);
    --card:                 oklch(0.19 0.04 150);
    --card-foreground:      oklch(0.98 0 0);
    --popover:              oklch(0.16 0.03 150);
    --popover-foreground:   oklch(0.98 0 0);
    --secondary:            oklch(0.24 0.04 150);
    --secondary-foreground: oklch(0.98 0 0);
    --muted:                oklch(0.24 0.04 150);
    --muted-foreground:     oklch(0.65 0.02 240);
    --accent:               oklch(0.72 0.12 80);
    --accent-foreground:    oklch(0.22 0.06 150);
    --destructive:          oklch(0.45 0.18 27);
    --destructive-foreground: oklch(0.75 0.20 27);
    --border:               oklch(0.30 0.05 150);
    --input:                oklch(0.30 0.05 150);
    --ring:                 oklch(0.50 0.08 150);
    --chart-1: oklch(0.62 0.18 255);
    --chart-2: oklch(0.70 0.17 180);
    --chart-3: oklch(0.76 0.19 80);
    --chart-4: oklch(0.70 0.17 130);
    --chart-5: oklch(0.70 0.22 320);
    /* Sidebar stays same dark green in dark mode */
    --sidebar:                      oklch(0.18 0.05 150);
    --sidebar-foreground:           oklch(0.98 0 0);
    --sidebar-primary:              oklch(0.72 0.12 80);
    --sidebar-primary-foreground:   oklch(0.22 0.06 150);
    --sidebar-accent:               oklch(0.26 0.06 150);
    --sidebar-accent-foreground:    oklch(0.98 0 0);
    --sidebar-border:               oklch(0.28 0.05 150);
    --sidebar-ring:                 oklch(0.72 0.12 80);
    --bgr-gold:       oklch(0.72 0.12 80);
    --bgr-gold-muted: oklch(0.35 0.08 80);
    --bgr-danger:     oklch(0.45 0.18 27);
    --bgr-warning:    oklch(0.60 0.15 60);
}
```

- [ ] **Step 4: Remove the `.dashboard-page` scoped font override**

Find and delete this block (it set `font-family: 'Inter'` as a scoped class — now Inter is the global font):

```css
.dashboard-page {
    --dashboard-bg: #fafafa;
    --dashboard-card-shadow: ...;
    --dashboard-card-hover-shadow: ...;
    font-family: 'Inter', ...;
}
```

Keep the `.dashboard-card` and `.dashboard-table-scroll` rules if they exist — only remove the font-family override.

- [ ] **Step 5: Build and check**

```bash
npm run build 2>&1 | tail -20
```

Expected: Build succeeds. If errors, check for syntax issues in the CSS block above.

- [ ] **Step 6: Verify visually**

Open the app at https://rrmanagementlatest.test/ — the sidebar should now appear forest green (#1E3A2F) and the active nav item gold. Page background should be a very slightly green-tinted off-white rather than pure white.

- [ ] **Step 7: Commit**

```bash
git add resources/css/app.css
git commit -m "feat: apply BGR brand color tokens and Fira Code font to Tailwind theme"
```

---

## Task 2: BGR SVG Logo Component

**Files:**
- Modify: `resources/js/components/app-logo.tsx`

The current logo loads `logo.png` at 32×32px. We replace with an inline SVG mark (triangle/mountain motif matching bgrmining.com's visual identity) and keep the "SHAReReport" wordmark.

- [ ] **Step 1: Replace `app-logo.tsx`**

```tsx
import { cn } from '@/lib/utils';

interface AppLogoProps {
    className?: string;
    wordmarkClassName?: string;
    showWordmark?: boolean;
}

export default function AppLogo({
    className,
    wordmarkClassName,
    showWordmark = true,
}: AppLogoProps) {
    return (
        <div
            className={cn(
                'flex min-w-0 flex-1 items-center gap-2 text-left text-sm',
                className,
            )}
        >
            {/* BGR mountain/triangle SVG mark */}
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 32 32"
                className="h-8 w-8 shrink-0 group-data-[collapsible=icon]:h-4 group-data-[collapsible=icon]:w-4"
                aria-hidden="true"
            >
                <rect width="32" height="32" rx="6" fill="rgba(200,168,75,0.15)" />
                {/* Outer triangle outline */}
                <polygon
                    points="16,4 29,26 3,26"
                    fill="none"
                    stroke="#C8A84B"
                    strokeWidth="2"
                    strokeLinejoin="round"
                />
                {/* Inner filled triangle */}
                <polygon
                    points="16,10 25,26 7,26"
                    fill="#C8A84B"
                    opacity="0.4"
                />
                {/* Apex dot */}
                <circle cx="16" cy="23" r="2" fill="#C8A84B" />
            </svg>

            {showWordmark ? (
                <div className={cn('flex flex-col group-data-[collapsible=icon]:hidden', wordmarkClassName)}>
                    <span className="truncate font-bold leading-tight text-sidebar-foreground">
                        SHAReReport
                    </span>
                    <span className="truncate text-[9px] font-medium uppercase tracking-widest text-sidebar-foreground/40">
                        BGR Mining &amp; Infra
                    </span>
                </div>
            ) : null}
        </div>
    );
}
```

- [ ] **Step 2: Build and check**

```bash
npm run build 2>&1 | tail -10
```

Expected: No TypeScript errors. The logo in the sidebar should show the triangle SVG mark with "SHAReReport" + "BGR Mining & Infra" subline.

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/app-logo.tsx
git commit -m "feat: replace logo.png with BGR inline SVG triangle mark"
```

---

## Task 3: Fix "Rows per page" in Pagination

**Files:**
- Modify: `resources/js/components/data-table/data-table-pagination.tsx`

- [ ] **Step 1: Fix the label**

In `data-table-pagination.tsx` line 35, change:

```tsx
<p className="text-sm font-medium">Lignes par page</p>
```

to:

```tsx
<p className="text-sm font-medium">Rows per page</p>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/components/data-table/data-table-pagination.tsx
git commit -m "fix: rename Lignes par page to Rows per page"
```

---

## Task 4: Remove "Sign up" from Login Page

**Files:**
- Modify: `resources/js/pages/session/create.tsx`

- [ ] **Step 1: Remove the Sign up block**

In `resources/js/pages/session/create.tsx`, find and delete this entire `<div>` (lines 139–148):

```tsx
<div className="text-center text-sm text-muted-foreground">
    Don't have an account?{' '}
    <TextLink
        href={register()}
        tabIndex={5}
        data-pan="auth-sign-up-link"
    >
        Sign up
    </TextLink>
</div>
```

- [ ] **Step 2: Remove the now-unused `register` import**

At the top of the file, remove:

```tsx
import { register } from '@/routes';
```

- [ ] **Step 3: Run TypeScript check**

```bash
npx tsc --noEmit 2>&1 | grep -E 'error|Error' | head -20
```

Expected: No errors related to `register`.

- [ ] **Step 4: Commit**

```bash
git add resources/js/pages/session/create.tsx
git commit -m "feat: remove Sign up link from login page"
```

---

## Task 5: Login Page — BGR Split Layout

**Files:**
- Modify: `resources/js/layouts/auth/auth-simple-layout.tsx`
- Modify: `resources/js/pages/session/create.tsx`

The current login is a centered card. We replace the layout with a two-column split: BGR green identity panel on the left, form on the right.

- [ ] **Step 1: Replace `auth-simple-layout.tsx`**

```tsx
import AppLogo from '@/components/app-logo';
import { type PropsWithChildren } from 'react';

interface AuthLayoutProps {
    title?: string;
    description?: string;
}

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: PropsWithChildren<AuthLayoutProps>) {
    return (
        <div className="flex min-h-svh items-center justify-center bg-[oklch(0.96_0.008_150)] p-6 md:p-10">
            <div className="w-full max-w-4xl overflow-hidden rounded-2xl shadow-xl flex">
                {/* Left: BGR identity panel */}
                <div className="hidden md:flex md:w-[320px] flex-col bg-[oklch(0.22_0.06_150)] p-10 text-white flex-shrink-0">
                    <div className="mb-8">
                        <AppLogo showWordmark={false} className="flex-none" />
                    </div>

                    <h2 className="text-xl font-bold leading-snug">
                        Railway Rack Management System
                    </h2>
                    <p className="mt-3 text-sm text-white/50 leading-relaxed">
                        BGR Mining &amp; Infra Limited — Coal logistics intelligence platform
                    </p>

                    <div className="my-8 h-px bg-white/10" />

                    <div className="space-y-5">
                        <div>
                            <div className="font-mono text-2xl font-bold text-[oklch(0.72_0.12_80)]">
                                3 Sidings
                            </div>
                            <div className="mt-1 text-xs text-white/40">
                                Dumka · Kurwa · Pakur
                            </div>
                        </div>
                        <div>
                            <div className="font-mono text-2xl font-bold text-[oklch(0.72_0.12_80)]">
                                5 Plants
                            </div>
                            <div className="mt-1 text-xs text-white/40">
                                STPS · BTPC · KPPS · PSPM · BTMT
                            </div>
                        </div>
                    </div>

                    <div className="mt-auto text-xs text-white/25">
                        © BGR Mining &amp; Infra Limited
                    </div>
                </div>

                {/* Right: form */}
                <div className="flex-1 bg-white p-8 md:p-12 flex flex-col justify-center">
                    <div className="mb-8 space-y-1">
                        <h1 className="text-2xl font-bold text-[oklch(0.22_0.06_150)]">
                            {title}
                        </h1>
                        {description && (
                            <p className="text-sm text-muted-foreground">
                                {description}
                            </p>
                        )}
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
```

- [ ] **Step 2: Update the login page title/description to match**

In `resources/js/pages/session/create.tsx`, the `<AuthLayout>` call currently passes:
```tsx
title="Log in to your account"
description="Enter your email and password below to log in"
```

Change to:
```tsx
title="Sign in"
description="Access your BGR Mining dashboard"
```

- [ ] **Step 3: Build and check TypeScript**

```bash
npx tsc --noEmit 2>&1 | grep -E 'error|Error' | head -20
npm run build 2>&1 | tail -10
```

Expected: No errors.

- [ ] **Step 4: Visual check**

Open https://rrmanagementlatest.test/login — should show dark green left panel with "Railway Rack Management System", "3 Sidings / 5 Plants", and white right panel with sign-in form. No "Sign up" link.

- [ ] **Step 5: Commit**

```bash
git add resources/js/layouts/auth/auth-simple-layout.tsx resources/js/pages/session/create.tsx
git commit -m "feat: redesign login with BGR split layout, remove Sign Up"
```

---

## Task 6: Sidebar — Grouped Navigation Sections

**Files:**
- Modify: `resources/js/components/app-sidebar.tsx`

The current sidebar has all items in a flat list. We reorganise into labelled groups matching the spec: **Overview / Loading Operations / Finance & Compliance / Analytics**.

- [ ] **Step 1: Read the current full `app-sidebar.tsx`**

Read the file completely so you know every existing `NavItem` and its `permission` / `dataPan` values before editing. This avoids accidentally dropping any existing items.

```bash
cat resources/js/components/app-sidebar.tsx
```

- [ ] **Step 2: Restructure `platformNavItems` into grouped sections**

Replace the existing `platformNavItems` array with a `navGroups` array of type `NavGroup[]`. The exact `NavGroup` and `NavItem` types are defined in `@/types` — check `resources/js/types/index.d.ts` to confirm the shape. Typically:

```ts
type NavGroup = { label: string; items: NavItem[] }
```

If `NavGroup` is not in `@/types`, add it there:

```ts
export type NavGroup = {
    label: string;
    items: NavItem[];
};
```

Then in `app-sidebar.tsx`:

```tsx
const navGroups: NavGroup[] = [
    {
        label: 'Overview',
        items: [
            {
                title: 'Dashboard',
                href: dashboard().url,
                icon: LayoutGrid,
                permission: 'sections.dashboard.view',
                dataPan: 'nav-dashboard',
            },
        ],
    },
    {
        label: 'Loading Operations',
        items: [
            {
                title: 'Rake Loader',
                href: '/rake-loader',
                icon: Train,
                permission: 'sections.rake_loader.view',
                dataPan: 'nav-rake-loader',
            },
            {
                title: 'Rake Management',
                href: '/rakes',
                icon: ClipboardList,
                permission: 'sections.rakes.view',
                dataPan: 'nav-rakes',
            },
            {
                title: 'Manual Weighment',
                href: '/weighments',
                icon: Scale,
                permission: 'sections.weighments.view',
                dataPan: 'nav-weighments',
            },
            {
                title: 'Road Dispatch',
                href: '/road-dispatch',
                icon: Truck,
                permission: 'sections.road_dispatch.view',
                dataPan: 'nav-road-dispatch',
            },
        ],
    },
    {
        label: 'Finance & Compliance',
        items: [
            {
                title: 'Penalties',
                href: '/penalties',
                icon: AlertTriangle,
                permission: 'sections.penalties.view',
                dataPan: 'nav-penalties',
            },
            {
                title: 'Railway Receipts',
                href: '/railway-receipts',
                icon: FileText,
                permission: 'sections.railway_receipts.view',
                dataPan: 'nav-railway-receipts',
            },
            {
                title: 'Stock Ledger',
                href: '/historical/railway-siding',
                icon: BarChart3,
                permission: 'sections.historical.view',
                dataPan: 'nav-stock-ledger',
            },
            {
                title: 'Indents',
                href: '/indents',
                icon: ClipboardList,
                permission: 'sections.indents.view',
                dataPan: 'nav-indents',
            },
        ],
    },
    {
        label: 'Analytics',
        items: [
            {
                title: 'Reports',
                href: '/reports',
                icon: BarChart3,
                permission: 'sections.reports.view',
                dataPan: 'nav-reports',
            },
            {
                title: 'Alerts',
                href: '/alerts',
                icon: AlertTriangle,
                permission: 'sections.alerts.view',
                dataPan: 'nav-alerts',
            },
        ],
    },
];
```

**Important:** Do not drop any existing nav item from the original file. If an item exists in the original `platformNavItems` that is not in the groups above, add it to the most appropriate group. Read the original file first (Step 1) and reconcile.

- [ ] **Step 3: Update `NavMain` to accept and render groups**

Check `resources/js/components/nav-main.tsx`. If it currently accepts `items: NavItem[]`, update it to accept `groups: NavGroup[]` and render each group with a label:

```tsx
// resources/js/components/nav-main.tsx
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import type { NavGroup } from '@/types';
import { Link, usePage } from '@inertiajs/react';

export function NavMain({ groups }: { groups: NavGroup[] }) {
    const { url } = usePage();

    return (
        <>
            {groups.map((group) => (
                <SidebarGroup key={group.label}>
                    <SidebarGroupLabel className="text-[9px] font-bold uppercase tracking-widest text-sidebar-foreground/30">
                        {group.label}
                    </SidebarGroupLabel>
                    <SidebarMenu>
                        {group.items.map((item) => (
                            <SidebarMenuItem key={item.href}>
                                <SidebarMenuButton
                                    asChild
                                    isActive={url.startsWith(item.href)}
                                    tooltip={item.title}
                                >
                                    <Link href={item.href} data-pan={item.dataPan}>
                                        {item.icon && <item.icon />}
                                        <span>{item.title}</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        ))}
                    </SidebarMenu>
                </SidebarGroup>
            ))}
        </>
    );
}
```

**Note:** The existing `NavMain` may have permission-checking logic. Read the current file first and preserve all permission checks — only change the rendering structure, not the filtering logic.

- [ ] **Step 4: Update `app-sidebar.tsx` to pass `navGroups` to `NavMain`**

Replace the `<NavMain items={...} />` call with:

```tsx
<NavMain groups={navGroups} />
```

- [ ] **Step 5: Run TypeScript check**

```bash
npx tsc --noEmit 2>&1 | grep -E 'error|Error' | head -30
```

Fix any type errors before continuing.

- [ ] **Step 6: Build**

```bash
npm run build 2>&1 | tail -10
```

- [ ] **Step 7: Visual check**

Open the app — sidebar should show four labelled groups with full item names. Active item should have gold background. Hover should show a slightly lighter green.

- [ ] **Step 8: Commit**

```bash
git add resources/js/components/app-sidebar.tsx resources/js/components/nav-main.tsx resources/js/types/index.d.ts
git commit -m "feat: reorganise sidebar into grouped nav sections with BGR styling"
```

---

## Task 7: Table Hover States & Button Hierarchy

**Files:**
- Modify: `resources/css/app.css` (append utility classes at end)

The table row hover and button accent variant are the remaining global polish items. These are additive CSS classes, not token changes.

- [ ] **Step 1: Add utility classes at the end of `app.css`**

Append to the bottom of `resources/css/app.css`:

```css
/* BGR table row hover — green tint */
tbody tr:hover td {
    background-color: oklch(0.97 0.01 150);
}

/* BGR gold accent button variant — used for clearance/approval actions */
.btn-bgr-gold {
    background-color: oklch(0.72 0.12 80);
    color: oklch(0.22 0.06 150);
    font-weight: 600;
}

.btn-bgr-gold:hover {
    background-color: oklch(0.68 0.12 80);
}

/* Fira Code on all numeric/weight values */
.font-weight-value {
    font-family: 'Fira Code', 'Courier New', ui-monospace, monospace;
    font-variant-numeric: tabular-nums;
}
```

- [ ] **Step 2: Build and verify**

```bash
npm run build 2>&1 | tail -10
```

- [ ] **Step 3: Commit**

```bash
git add resources/css/app.css
git commit -m "feat: add table hover state, gold button variant, and weight value font utility"
```

---

## Task 8: Final Phase 1 Build & Smoke Test

- [ ] **Step 1: Full production build**

```bash
npm run build 2>&1 | tail -20
```

Expected: Build completes with no errors.

- [ ] **Step 2: TypeScript full check**

```bash
npx tsc --noEmit 2>&1 | grep -E 'error TS' | wc -l
```

Expected: 0 errors. If there are errors unrelated to Phase 1 changes (pre-existing), note them but do not fix — only fix errors introduced by Phase 1 work.

- [ ] **Step 3: Run Pint**

```bash
vendor/bin/pint --dirty --format agent
```

(No PHP files changed in Phase 1 — this should be a no-op.)

- [ ] **Step 4: Manual smoke test checklist**

Visit each of these URLs and confirm no visual regressions — the page should load, use BGR green sidebar, and show content correctly:

- https://rrmanagementlatest.test/ (dashboard)
- https://rrmanagementlatest.test/login (BGR split layout, no Sign up)
- https://rrmanagementlatest.test/rake-loader (any table — check "Rows per page" label)
- https://rrmanagementlatest.test/penalties (table with pagination)

- [ ] **Step 5: Final commit if any last fixes**

```bash
git add -p
git commit -m "fix: phase 1 smoke test cleanup"
```

---

## Self-Review

**Spec coverage check:**

| Spec requirement | Task |
|-----------------|------|
| BGR green/gold color system | Task 1 |
| SVG logo design | Task 2 |
| Fix "Lignes par page" → "Rows per page" | Task 3 |
| Remove "Sign Up" from login | Task 4 |
| Login redesign (BGR split layout) | Task 5 |
| Sidebar reorganized (full names, grouped) | Task 6 |
| Table hover states | Task 7 |
| Button hierarchy | Task 7 |
| Fira Code for numbers/weights | Task 1 (import) + Task 7 (utility class) |

All Phase 1 spec items covered. No gaps.

**Placeholders:** None — all steps contain exact file paths, exact code, and exact commands.

**Type consistency:** `NavGroup` type introduced in Task 6 Step 2, used consistently in Steps 3 and 4. `NavItem` type from `@/types` used throughout — existing type, not modified.
