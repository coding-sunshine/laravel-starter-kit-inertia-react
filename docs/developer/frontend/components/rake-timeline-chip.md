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

- Rake show page header (`size="detailed"`)
- Rake list / data-table rows (`size="compact"`)
- Future: dispute drawer evidence panel

Avoid two chips on the same row — they steal attention from the row's primary content.
