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

- **Animation:** See UI-06 (Framer Motion or Tailwind transitions); duration 150–300ms; respect `prefers-reduced-motion`.
- **Responsive:** Tailwind breakpoints `sm`/`md`/`lg`/`xl`; sidebar drawer below `lg`; touch targets ≥44px. See UI-06.

---

## References

- UI-01: Design system step. UI-06: Animations and responsive. UI-07: UX, charting, reporting.
- Shadcn: https://ui.shadcn.com/ | Radix: https://www.radix-ui.com/ | Tailwind: https://tailwindcss.com/
