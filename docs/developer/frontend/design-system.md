# Design system (2025/2026)

Design tokens, typography, and component usage for the Fleet app redesign.

## Information architecture

- **Primary nav:** Sidebar (vertical, Samsara-style) — Dashboard, Chat, Fleet, Organizations, Billing, Help, etc. Collapsible; icon + label; keyboard navigable. See [resources/js/components/app-sidebar.tsx](/resources/js/components/app-sidebar.tsx) and [resources/js/config/fleet-nav.ts](/resources/js/config/fleet-nav.ts).
- **Contextual actions:** Per-page (e.g. Fleet list pages: "Ask assistant", "New vehicle"). FAB for Fleet Assistant when fleet context.
- **Navigation aids:** Command palette (⌘K): Recent (session), Favorites (localStorage), Fleet shortcuts, AI tab ("Ask Fleet Assistant"), search (help, users, posts, changelog). See [resources/js/components/command-dialog-v2.tsx](/resources/js/components/command-dialog-v2.tsx).
- **Fleet-first:** When `fleet_only_app` is true, dashboard redirects to `/fleet`; nav and chrome are fleet-oriented.

## Design tokens

- **File:** [resources/css/design-tokens.css](/resources/css/design-tokens.css)
- **Semantic palette:** primary, success, warning, error, info, neutral (oklch, light/dark). AI base: `--ai-base-bg`, `--ai-base-fg`, `--ai-response-text`.
- **Spacing:** 4pt base scale (`--space-1` through `--space-24`).
- **Typography scale:** `--text-xs` through `--text-4xl`; line-height and letter-spacing.
- **Radius:** `--radius-sm` to `--radius-xl`; `--radius-ai-panel: 16px`.
- **Shadow:** `--shadow-sm` to `--shadow-xl`.
- **Motion:** `--duration-fast` (100ms), `--duration-normal`, `--duration-slow`; `--ease-default`. Respect `prefers-reduced-motion` (tokens and global reduce in design-tokens.css).
- **Glassmorphism 2.0:** `--ai-panel-bg`, `--ai-panel-border`, `--ai-panel-blur` (12–20px), `--ai-panel-shadow`.

## Typography

- **Body:** Use `.prose-read` for 45–75ch line length (max-width: 65ch). Classes: `.body`, `.body-sm`, `.body-lg`.
- **Headings:** `.heading-1` (h1) through `.heading-6` (h6) — see design-tokens.css.
- **Contrast:** Semantic colors and text meet WCAG AA. Use `--color-*-foreground` for text on colored backgrounds.

## Icons and illustrations

- **Icons:** Lucide retained project-wide.
- **Empty states:** SVGs in [public/images/empty/](/public/images/empty/) — `vehicles.svg`, `fleet.svg`, `data-empty.svg`. Use with `EmptyState` / `FleetEmptyState` via the `illustration` prop (e.g. `illustration="/images/empty/vehicles.svg"`). See [public/images/empty/README.md](/public/images/empty/README.md).
- **Status:** Consistent success (green), warning (amber), error (red) via semantic tokens.

## ui-v2 components

Primitives and patterns live in [resources/js/components/ui-v2/](/resources/js/components/ui-v2/):

- **Primitives:** Button, Input, Select, Checkbox (CVA + Radix, design-token styled).
- **Feedback:** Toast (Sonner re-export), Progress.
- **Patterns:** Card, Badge, Alert, Skeleton, EmptyState, ErrorState.
- **AI:** AiPanel, StreamingText, ConfidenceIndicator, SkeletonShimmerLines.

Import from `@/components/ui-v2` or `resources/js/components/ui-v2`.
