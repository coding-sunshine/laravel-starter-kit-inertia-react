# UI Step 1: Design system and tokens

**Goal:** Define and implement a single design system so the app no longer looks like a generic admin template. Use a strict palette (#333333, #4348be, white), typography scale, spacing, and Shadcn/Radix alignment. Introduce a small animation strategy (transitions, hover, loading) and document references for components and motion.

**References:** [Shadcn UI](https://ui.shadcn.com/), [Radix UI](https://www.radix-ui.com/), [Tailwind CSS](https://tailwindcss.com/), existing `resources/js/components/ui/` and Tailwind config / CSS.

---

## 1. Prerequisites

- Fleet MVP in place; Tailwind v4 and Radix-based components (button, card, input, sidebar, etc.) already in use.
- No prior design tokens enforced app-wide; theme may be driven by DB/customizer (e.g. `theme` in HandleInertiaRequests). Design system will override or align with that.

---

## 2. Color tokens

- **Primary text / headings:** `#333333` (dark grey). Use for body text, headings, and primary UI text. Map to a CSS variable, e.g. `--color-primary-text` or Tailwind custom color `primary-text`.
- **Primary brand / actions:** `#4348be` (blue). Use for primary buttons, active nav state, links, focus rings, and key CTAs. Map to `--color-primary` or Tailwind `primary` (and ensure Shadcn “primary” uses this).
- **Backgrounds:** `white` for main content and cards; optional very light grey (e.g. `#f8f9fa`) for secondary backgrounds (sidebar inset, table stripes). Ensure sufficient contrast with #333333 (WCAG AA).
- **Borders / dividers:** Light grey derived from #333333 at low opacity (e.g. 10–15%) or a fixed token like `#e5e7eb` so borders are subtle but visible.
- **Destructive / error:** Keep existing red for errors and destructive actions; ensure it does not clash with primary blue.
- **Success / warning:** Keep or define once; use sparingly for status and toasts.

Apply these in:
- `resources/css/app.css` (or Tailwind theme extension) as CSS variables and/or Tailwind theme colors.
- Any theme preset that the app loads (e.g. settings/branding or theme resolver) so that “primary” in the UI library resolves to #4348be.

---

## 3. Typography

- **Font family:** Use a single sans-serif stack app-wide (e.g. Instrument Sans from existing theme, or Inter / system-ui). Define in Tailwind `theme.fontFamily` and use consistently for body and headings.
- **Scale:** Define a small type scale (e.g. `text-xs` through `text-2xl` or `text-3xl`) and use it consistently: page titles (e.g. `text-2xl font-semibold`), section headings, body, captions. Prefer semantic classes (e.g. `.heading-page`, `.heading-section`) or Tailwind utilities applied consistently.
- **Weight:** Use font weights consistently: e.g. semibold for headings and primary labels, normal for body, medium for secondary emphasis.

---

## 4. Spacing and layout

- Use Tailwind spacing scale consistently: card padding, section gaps, sidebar padding. Recommend a base unit (e.g. 4px grid) and stick to it so that 4, 6, 8, 12, 16, 24 are the common values.
- Content max-width: where applicable (e.g. auth layout, centered content), use a max-width (e.g. `max-w-md` for login, `max-w-7xl` for dashboard content) for readability.

---

## 5. Shadcn / Radix alignment

- **Audit:** List all Radix-based components in `resources/js/components/ui/` (Button, Card, Input, Label, Sidebar, Select, Dialog, etc.). Ensure each uses the design tokens above: primary button uses #4348be; text uses #333333; backgrounds white/light grey.
- **Theming:** If the project uses a “theme” object (e.g. preset, base_color) from backend or context, map “primary” to #4348be and “primary-foreground” to white so Shadcn primary variant matches.
- **No new framework:** Stay on Radix + Tailwind; do not replace the stack. Only align colors and, if needed, add missing components (e.g. Skeleton, improved Card variants) from Shadcn patterns.

---

## 6. Animation and motion

- **Recommended: Framer Motion.** [Framer Motion](https://www.framer.com/motion/) – install `framer-motion`; use for:
  - **Page transition (Inertia):** Wrap page content in `motion.div` with `initial={{ opacity: 0 }}`, `animate={{ opacity: 1 }}`, `exit={{ opacity: 0 }}` (use with `AnimatePresence` and a stable key from route). Duration 150–250ms.
  - **List / card enter:** Stagger children with `transition={{ delay: index * 0.03 }}` for tables and card grids.
  - **Card hover:** `whileHover={{ y: -2 }}` and optional `transition`; or use Tailwind `transition hover:shadow-md`.
  - **Loading:** Skeleton with `animate={{ opacity: [0.5, 1, 0.5] }}`, `transition={{ repeat: Infinity }}`, or Tailwind `animate-pulse`.
  - **Modals/dialogs:** `initial={{ opacity: 0, scale: 0.98 }}`, `animate={{ opacity: 1, scale: 1 }}`, `exit` for close.
  - **Button feedback:** `whileTap={{ scale: 0.98 }}` on primary buttons.
- **Bundle:** Prefer [LazyMotion with domAnimation](https://www.framer.com/motion/guide-reduce-bundle-size/) to reduce bundle size.
- **Reduced motion:** Use [useReducedMotion()](https://www.framer.com/motion/guide-reduce-bundle-size/#respect-reduced-motion) or `matchMedia('(prefers-reduced-motion: reduce)')`; when true, set `duration: 0` or skip non-essential motion.
- **Principles:** 150–300ms for UI; easeOut for enter. See **UI-06** for full animation patterns and alternatives (Tailwind-only, Auto Animate, React Spring).

---

## 7. Responsive design (basics)

- **Breakpoints:** Use Tailwind defaults – `sm:` 640px, `md:` 768px, `lg:` 1024px, `xl:` 1280px. Mobile-first: base styles for small screens, then `md:` and `lg:` for layout changes. [Tailwind – Responsive](https://tailwindcss.com/docs/responsive-design).
- **Sidebar:** On viewports below `lg`, show sidebar as overlay/drawer (e.g. Sheet) with a menu trigger in the header; persistent sidebar from `lg:` up. Touch targets for nav items ≥44px. [Radix Sidebar](https://www.radix-ui.com/primitives/docs/components/sidebar), [Shadcn Sidebar](https://ui.shadcn.com/docs/components/sidebar).
- **Content and grids:** Fleet dashboard and card grids: `grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4` (or similar). Main content padding: `p-4 md:p-6`.
- **Tables:** On small screens, either horizontal scroll (`overflow-x-auto`) or card-style rows. See **UI-06** for full responsive tables and touch-target guidance.

---

## 8. References and documentation

| Topic | URL | Notes |
|-------|-----|------|
| **Shadcn UI** | https://ui.shadcn.com/ | Component patterns, theming, sidebar. |
| **Radix UI** | https://www.radix-ui.com/ | Primitives (Sidebar, Dialog, etc.). |
| **Tailwind CSS** | https://tailwindcss.com/ | Theme, utilities, responsive, transition, animation. |
| **Tailwind responsive** | https://tailwindcss.com/docs/responsive-design | Breakpoints, mobile-first. |
| **Framer Motion** | https://www.framer.com/motion/ | React animations, AnimatePresence, layout. |
| **Framer – reduce bundle** | https://www.framer.com/motion/guide-reduce-bundle-size/ | LazyMotion, reduced motion. |
| **WCAG 2.1** | https://www.w3.org/WAI/WCAG21/quickref/ | Contrast, focus, target size. |
| **WCAG target size** | https://www.w3.org/WAI/WCAG22/Understanding/target-size.html | Touch target ≥44px. |
| **MDN prefers-reduced-motion** | https://developer.mozilla.org/en-US/docs/Web/CSS/@media/prefers-reduced-motion | Respect user preference. |

Full animation and responsive reference list (including Auto Animate, React Spring, container queries) is in **UI-06-animations-responsive.md**. For **UX** (flows, consistency, feedback) and **charting/reporting** references, see **UI-07-ux-charting-reporting.md**.

---

## 9. Implementation checklist

- [ ] Add color tokens to Tailwind/CSS (#333333, #4348be, white, borders).
- [ ] Map Shadcn “primary” (and primary-foreground) to #4348be / white.
- [ ] Define typography scale and apply to headings/body app-wide.
- [ ] Audit `components/ui` and ensure Button, Card, Input, Sidebar use tokens.
- [ ] Add Framer Motion (or agreed library); apply page transition, card hover, loading, reduced motion (see UI-06).
- [ ] Apply responsive breakpoints and sidebar drawer for small screens (see UI-06).
- [ ] Document tokens and decisions in DESIGN_SYSTEM.md or in this step for future steps to reference.

---

## 10. Done when

- All primary actions and active states use #4348be; primary text uses #333333; backgrounds are white (or defined light grey).
- Typography and spacing are consistent; Shadcn/Radix components are aligned with the palette.
- Subtle animations are in place (page, list, card, modal, button); reduced motion is respected.
- Responsive basics are in place: breakpoints, sidebar behaviour on small screens, and st targets; full details in UI-06.

Proceed to **UI-02** (`UI-02-auth-login.md`) for the login form and auth layout redesign.
