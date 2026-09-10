# Rake Timeline Chip — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build a single reusable React component `<RakeTimelineChip>` rendering a rake's lifecycle as one compact horizontal strip (Placement ● Loading Start ● Loading End ● Weighed ● Drawn ● RR), with hover-revealed timestamps and segment durations. Drop into rake show page and rake list rows.

**Architecture:** Pure React component — no backend code, no DB columns. Reads existing `Rake` timeline fields (`placement_time`, `loading_start_time`, `loading_end_time`, `weighment_end_time`, `drawn_out`, `rr_actual_date`) already present on the model. Component is presentational only; accepts a typed `RakeTimeline` prop. Tailwind v4 utility classes + Lucide icons + Radix Tooltip (already installed). Vitest covers states.

**Tech Stack:** React 19, Inertia v3, TypeScript, Tailwind CSS v4, Radix UI, Lucide React, Vitest

**Depends on:** none. Pure additive.

---

## File Structure

**Created:**
- `resources/js/components/rake/rake-timeline-chip.tsx`
- `resources/js/components/rake/rake-timeline-chip.types.ts`
- `resources/js/components/rake/format-duration.ts`
- `resources/js/components/rake/format-duration.test.ts`
- `resources/js/components/rake/rake-timeline-chip.test.tsx`
- `docs/developer/frontend/components/rake-timeline-chip.md`

**Modified:**
- One existing rake show page (path discovered in Task 5)
- One existing rake data table (path discovered in Task 6)
- `docs/.manifest.json`

---

### Task 1: Types

**File:** `resources/js/components/rake/rake-timeline-chip.types.ts`

```ts
export type RakeTimelineKey =
    | 'placement'
    | 'loading_start'
    | 'loading_end'
    | 'weighed'
    | 'drawn'
    | 'rr';

export interface RakeTimelineInput {
    placement_time?: string | null;
    loading_start_time?: string | null;
    loading_end_time?: string | null;
    weighment_end_time?: string | null;
    drawn_out?: string | null;
    rr_actual_date?: string | null;
}

export interface RakeTimelineStep {
    key: RakeTimelineKey;
    label: string;
    timestamp: string | null;
    state: 'done' | 'pending' | 'skipped';
}

export interface RakeTimelineChipProps {
    rake: RakeTimelineInput;
    size?: 'compact' | 'default' | 'detailed';
    className?: string;
}
```

Commit: `feat(ui): add RakeTimelineChip types`

---

### Task 2: `formatDuration` helper + tests

**Files:**
- `resources/js/components/rake/format-duration.ts`
- `resources/js/components/rake/format-duration.test.ts`

**Step 1 — Test (failing):**

```ts
import { describe, expect, it } from 'vitest';
import { formatDuration } from './format-duration';

describe('formatDuration', () => {
    it('returns null when from or to is missing', () => {
        expect(formatDuration(null, '2026-05-01T10:00:00Z')).toBeNull();
        expect(formatDuration('2026-05-01T10:00:00Z', null)).toBeNull();
    });

    it('formats minutes under one hour', () => {
        expect(formatDuration('2026-05-01T10:00:00Z', '2026-05-01T10:45:00Z')).toBe('45m');
    });

    it('formats hours and minutes between one hour and one day', () => {
        expect(formatDuration('2026-05-01T10:00:00Z', '2026-05-01T13:30:00Z')).toBe('3h 30m');
    });

    it('formats days and hours when over 24h', () => {
        expect(formatDuration('2026-05-01T10:00:00Z', '2026-05-03T13:00:00Z')).toBe('2d 3h');
    });

    it('returns 0m when timestamps match', () => {
        expect(formatDuration('2026-05-01T10:00:00Z', '2026-05-01T10:00:00Z')).toBe('0m');
    });

    it('handles negative diffs as 0m', () => {
        expect(formatDuration('2026-05-01T11:00:00Z', '2026-05-01T10:00:00Z')).toBe('0m');
    });
});
```

Run `npx vitest run resources/js/components/rake/format-duration.test.ts` → expect FAIL.

**Step 2 — Implementation:**

```ts
export function formatDuration(
    fromIso: string | null | undefined,
    toIso: string | null | undefined,
): string | null {
    if (!fromIso || !toIso) {
        return null;
    }

    const from = new Date(fromIso).getTime();
    const to = new Date(toIso).getTime();
    const diffMs = Math.max(0, to - from);

    const totalMinutes = Math.floor(diffMs / 60_000);
    const days = Math.floor(totalMinutes / 1440);
    const hours = Math.floor((totalMinutes - days * 1440) / 60);
    const minutes = totalMinutes - days * 1440 - hours * 60;

    if (days > 0) {
        return `${days}d ${hours}h`;
    }
    if (hours > 0) {
        return `${hours}h ${minutes}m`;
    }
    return `${minutes}m`;
}
```

Re-run → expect PASS, 6 tests.

Commit: `feat(ui): add formatDuration helper`

---

### Task 3: `RakeTimelineChip` component

**File:** `resources/js/components/rake/rake-timeline-chip.tsx`

```tsx
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';
import { CheckCircle2, Circle, MinusCircle } from 'lucide-react';
import { formatDuration } from './format-duration';
import type {
    RakeTimelineChipProps,
    RakeTimelineKey,
    RakeTimelineStep,
} from './rake-timeline-chip.types';

const STEP_DEFINITIONS: { key: RakeTimelineKey; label: string; field: keyof RakeTimelineChipProps['rake'] }[] = [
    { key: 'placement', label: 'Placement', field: 'placement_time' },
    { key: 'loading_start', label: 'Loading start', field: 'loading_start_time' },
    { key: 'loading_end', label: 'Loading end', field: 'loading_end_time' },
    { key: 'weighed', label: 'Weighed', field: 'weighment_end_time' },
    { key: 'drawn', label: 'Drawn out', field: 'drawn_out' },
    { key: 'rr', label: 'RR issued', field: 'rr_actual_date' },
];

function buildSteps(rake: RakeTimelineChipProps['rake']): RakeTimelineStep[] {
    let lastDoneIndex = -1;
    const raw = STEP_DEFINITIONS.map((def, idx) => {
        const ts = (rake[def.field] as string | null | undefined) ?? null;
        if (ts) {
            lastDoneIndex = idx;
        }
        return { def, ts };
    });

    return raw.map(({ def, ts }, idx) => {
        let state: RakeTimelineStep['state'];
        if (ts) {
            state = 'done';
        } else if (idx < lastDoneIndex) {
            state = 'skipped';
        } else {
            state = 'pending';
        }
        return { key: def.key, label: def.label, timestamp: ts, state };
    });
}

function formatTimestamp(iso: string | null): string {
    if (!iso) return '—';
    const d = new Date(iso);
    return new Intl.DateTimeFormat('en-IN', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(d);
}

const sizeMap = {
    compact: { dot: 'h-2 w-2', gap: 'gap-1.5', stroke: 'h-2 w-2' },
    default: { dot: 'h-2.5 w-2.5', gap: 'gap-2', stroke: 'h-2.5 w-2.5' },
    detailed: { dot: 'h-3 w-3', gap: 'gap-2.5', stroke: 'h-3 w-3' },
} as const;

export function RakeTimelineChip({ rake, size = 'default', className }: RakeTimelineChipProps) {
    const steps = buildSteps(rake);
    const sizes = sizeMap[size];

    return (
        <TooltipProvider delayDuration={150}>
            <ol
                role="list"
                aria-label="Rake lifecycle"
                className={cn('flex items-center', sizes.gap, className)}
            >
                {steps.map((step, idx) => {
                    const prev = idx > 0 ? steps[idx - 1] : null;
                    const segmentDuration = prev && prev.timestamp && step.timestamp
                        ? formatDuration(prev.timestamp, step.timestamp)
                        : null;

                    return (
                        <li key={step.key} className="flex items-center">
                            {idx > 0 && (
                                <span
                                    aria-hidden
                                    className={cn(
                                        'mx-1 h-px w-3 sm:w-5',
                                        step.state === 'done' ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-700',
                                    )}
                                />
                            )}
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <button
                                        type="button"
                                        className={cn(
                                            'inline-flex items-center justify-center rounded-full transition-colors',
                                            'focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600',
                                            step.state === 'done' && 'text-emerald-600',
                                            step.state === 'pending' && 'text-slate-400',
                                            step.state === 'skipped' && 'text-amber-500',
                                        )}
                                        aria-label={`${step.label}: ${step.state}`}
                                    >
                                        {step.state === 'done' && <CheckCircle2 className={sizes.stroke} />}
                                        {step.state === 'pending' && <Circle className={sizes.stroke} />}
                                        {step.state === 'skipped' && <MinusCircle className={sizes.stroke} />}
                                    </button>
                                </TooltipTrigger>
                                <TooltipContent side="bottom" className="text-xs">
                                    <div className="font-medium">{step.label}</div>
                                    <div className="text-slate-500">{formatTimestamp(step.timestamp)}</div>
                                    {segmentDuration && (
                                        <div className="text-slate-500">+ {segmentDuration} from previous</div>
                                    )}
                                </TooltipContent>
                            </Tooltip>
                            {size === 'detailed' && (
                                <span className="ml-1 text-[10px] uppercase tracking-wide text-slate-500">
                                    {step.label}
                                </span>
                            )}
                        </li>
                    );
                })}
            </ol>
        </TooltipProvider>
    );
}
```

Run `npx tsc --noEmit`. If `@/lib/utils` or `@/components/ui/tooltip` paths fail, locate via `grep -RIn "from '@/components/ui/tooltip'" resources/js | head -1` and adjust.

Commit: `feat(ui): add RakeTimelineChip component`

---

### Task 4: Component test

**File:** `resources/js/components/rake/rake-timeline-chip.test.tsx`

```tsx
import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { RakeTimelineChip } from './rake-timeline-chip';

describe('RakeTimelineChip', () => {
    it('renders six steps', () => {
        render(<RakeTimelineChip rake={{}} />);
        expect(screen.getAllByRole('listitem')).toHaveLength(6);
    });

    it('marks done steps with done aria-label', () => {
        render(
            <RakeTimelineChip
                rake={{
                    placement_time: '2026-05-01T08:00:00Z',
                    loading_start_time: '2026-05-01T09:00:00Z',
                }}
            />,
        );

        expect(screen.getByLabelText('Placement: done')).toBeInTheDocument();
        expect(screen.getByLabelText('Loading start: done')).toBeInTheDocument();
        expect(screen.getByLabelText('Loading end: pending')).toBeInTheDocument();
    });

    it('marks intermediate gaps as skipped when later steps are done', () => {
        render(
            <RakeTimelineChip
                rake={{
                    placement_time: '2026-05-01T08:00:00Z',
                    loading_end_time: '2026-05-01T12:00:00Z',
                }}
            />,
        );

        expect(screen.getByLabelText('Loading start: skipped')).toBeInTheDocument();
    });

    it('renders all-pending when no timestamps are provided', () => {
        render(<RakeTimelineChip rake={{}} />);
        expect(screen.getAllByLabelText(/pending$/)).toHaveLength(6);
    });

    it('shows step label text in detailed size', () => {
        render(<RakeTimelineChip rake={{}} size="detailed" />);
        expect(screen.getByText('Placement')).toBeInTheDocument();
        expect(screen.getByText('RR issued')).toBeInTheDocument();
    });
});
```

If `@testing-library/react` is missing, check existing `resources/js/**/*.test.tsx` for the pattern. If the project genuinely has no React testing setup, install: `npm install --save-dev @testing-library/react @testing-library/jest-dom jsdom` and ensure `vitest.config.ts` uses `environment: 'jsdom'`.

Run `npx vitest run resources/js/components/rake/rake-timeline-chip.test.tsx` → expect PASS, 5 tests.

Commit: `test(ui): cover RakeTimelineChip state derivation`

---

### Task 5: Embed in rake show page

**Step 1 — Find the show page:**

Run: `find resources/js/pages -type f -iname "*show*" | xargs grep -lE "rake_number|placement_time" 2>/dev/null`

Open the matching file. Note the existing header structure and the `rake` prop shape.

**Step 2 — Render the chip near the header:**

```tsx
import { RakeTimelineChip } from '@/components/rake/rake-timeline-chip';

// inside the show-page JSX, just below the rake-number heading:
<div className="mt-3">
    <RakeTimelineChip
        rake={{
            placement_time: rake.placement_time,
            loading_start_time: rake.loading_start_time,
            loading_end_time: rake.loading_end_time,
            weighment_end_time: rake.weighment_end_time,
            drawn_out: rake.drawn_out,
            rr_actual_date: rake.rr_actual_date,
        }}
        size="detailed"
    />
</div>
```

**Step 3 — Confirm the controller returns these six fields.** Open the controller's `show` method. If any field is missing from the props array / Resource, add it.

**Step 4 — Smoke-test in browser** (`composer run dev` or project's existing dev command). Reload the rake show page. Hover each step → tooltip with timestamp + segment duration.

Commit: `feat(ui): embed RakeTimelineChip on rake show page`

---

### Task 6: Embed in rake data table row

**Step 1 — Find the rake list page:**

Run: `find resources/js/pages -type f \( -iname "index.tsx" -o -iname "*rake*list*" \) | xargs grep -lE "rake_number" 2>/dev/null`

**Step 2 — Add a "Lifecycle" column** between rake number and status (adapt to project column API):

```tsx
{
    accessorKey: 'lifecycle',
    header: 'Lifecycle',
    cell: ({ row }) => (
        <RakeTimelineChip
            rake={{
                placement_time: row.original.placement_time,
                loading_start_time: row.original.loading_start_time,
                loading_end_time: row.original.loading_end_time,
                weighment_end_time: row.original.weighment_end_time,
                drawn_out: row.original.drawn_out,
                rr_actual_date: row.original.rr_actual_date,
            }}
            size="compact"
        />
    ),
},
```

**Step 3 — Ensure the controller's `paginate()->through(...)` callback returns all six fields.** Add any missing.

**Step 4 — Smoke-test.** Reload the rake list. Each row shows the compact six-dot strip.

Commit: `feat(ui): show RakeTimelineChip in rake list rows`

---

### Task 7: Documentation

**File:** `docs/developer/frontend/components/rake-timeline-chip.md`

```markdown
# RakeTimelineChip

Compact horizontal strip showing a rake's lifecycle.

**Path:** `resources/js/components/rake/rake-timeline-chip.tsx`

## Props

| Prop | Type | Default |
|---|---|---|
| `rake` | `RakeTimelineInput` | — |
| `size` | `'compact' \| 'default' \| 'detailed'` | `'default'` |
| `className` | `string` | — |

`RakeTimelineInput` accepts six nullable ISO strings: `placement_time`, `loading_start_time`, `loading_end_time`, `weighment_end_time`, `drawn_out`, `rr_actual_date`. Each maps to one dot.

## States

- **done** — emerald check icon, timestamp shown
- **pending** — slate empty circle (next expected or any later step)
- **skipped** — amber minus circle (a gap before a later done step; flag for follow-up data entry)

## Usage

```tsx
import { RakeTimelineChip } from '@/components/rake/rake-timeline-chip';

<RakeTimelineChip rake={rake} size="detailed" />
```

## When to use

- Rake show page header
- Rake list / data-table rows
- Dispute drawer evidence panel

Avoid two chips on the same row — they steal attention from the row's primary content.
```

Run `php artisan docs:sync`. Commit: `docs: document RakeTimelineChip component`

---

### Task 8: Build + Prettier + final test sweep

Run:
- `npx prettier --write resources/js/components/rake/`
- `npm run build`
- `npx vitest run resources/js/components/rake/`

If anything changed from prettier, commit: `style: prettier on rake timeline files`. Otherwise skip.

---

## Self-Review

- **Spec coverage:** Component (Task 3), helpers + types (Tasks 1, 2), tests (Tasks 2, 4), embed in show (Task 5), embed in list (Task 6), docs (Task 7), build + format (Task 8). ✅
- **No backend impact.** No migrations, no jobs, no API contracts.
- **Type consistency:** `RakeTimelineInput` field names match `Rake` model fields verified earlier (`placement_time`, `loading_start_time`, `loading_end_time`, `weighment_end_time`, `drawn_out`, `rr_actual_date`). ✅

---

## Execution Notes

- **Activation:** automatic — chip renders the moment its component is imported and given a rake.
- **Rollback:** `git revert` of each commit. Existing pages return to pre-chip state.
