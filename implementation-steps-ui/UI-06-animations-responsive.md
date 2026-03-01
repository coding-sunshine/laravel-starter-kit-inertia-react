# UI Step 6: Animations and responsive design

**Goal:** Add polished animations (e.g. Framer Motion) for page transitions, list/card motion, and loading states, and ensure the app is fully responsive (mobile, tablet, desktop) with consistent breakpoints, touch-friendly targets, and a sidebar that works on small screens. Document all references for animations and responsive patterns.

**References:** See § References at the end of this document.

---

## 1. Prerequisites

- UI-01 design system in place (tokens, typography, spacing).
- App uses Tailwind CSS v4; React 19; Inertia.js for navigation. Sidebar and layout from UI-03.

---

## 2. Animation library: Framer Motion (recommended)

- **Package:** [framer-motion](https://www.npmjs.com/package/framer-motion) – React animation library. Install: `npm install framer-motion`.
- **Docs:** [framer.com/motion](https://www.framer.com/motion/) – API reference, examples, layout animations, gesture and scroll.
- **Bundle:** Tree-shakeable; use `motion` and `AnimatePresence` for most cases. Lazy-load heavy features (e.g. `LazyMotion`, `domAnimation`) to keep initial bundle smaller: [Framer Motion – Reduced bundle size](https://www.framer.com/motion/guide-reduce-bundle-size/).

**Use for:**

| Use case | Component / pattern | Notes |
|----------|----------------------|--------|
| **Page transition** | Wrap Inertia page content in `<motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} transition={{ duration: 0.2 }}>`. Or use `AnimatePresence` with a layout key from Inertia `url` so exit runs. | Subtle fade; 150–250ms. |
| **List stagger** | `motion.li` with `initial={{ opacity: 0, y: 8 }}`, `animate={{ opacity: 1, y: 0 }}`, `transition={{ delay: index * 0.03 }}`. | Table rows, card grids (e.g. Fleet dashboard cards). |
| **Card hover** | `motion.div` with `whileHover={{ y: -2, boxShadow: '...' }}`, `transition={{ duration: 0.2 }}`. | Fleet dashboard, workflow list. |
| **Modal / dialog** | `motion` wrapper with `initial={{ opacity: 0, scale: 0.98 }}`, `animate={{ opacity: 1, scale: 1 }}`, `exit={{ opacity: 0, scale: 0.98 }}`. | Radix Dialog content wrapper. |
| **Loading skeleton** | `motion.div` with `animate={{ opacity: [0.5, 1, 0.5] }}`, `transition={{ repeat: Infinity, duration: 1.5 }}`. | Tables, cards while loading. |
| **Button / CTA** | `whileTap={{ scale: 0.98 }}` on primary buttons. | Subtle press feedback. |
| **Sidebar expand/collapse** | If custom: `animate={{ width: open ? 240 : 64 }}`; else rely on Radix Sidebar and ensure CSS transition (e.g. 200ms ease). | Consistent with design system. |

**Principles:**

- **Duration:** 150–300ms for most UI; 200ms is a good default.
- **Easing:** `easeOut` or `[0.4, 0, 0.2, 1]` for enter; `easeIn` for exit. Framer Motion: `transition={{ type: 'tween', ease: 'easeOut' }}`.
- **Reduced motion:** Respect `prefers-reduced-motion: reduce`. Check `window.matchMedia('(prefers-reduced-motion: reduce)')` or use Framer’s `useReducedMotion()`; when true, set `duration: 0` or skip layout/scroll animations. [Framer – Respect reduced motion](https://www.framer.com/motion/guide-reduce-bundle-size/#respect-reduced-motion).

---

## 3. Alternative animation approaches

- **CSS only (Tailwind):** `transition transition-colors duration-200`, `hover:shadow-md`, `animate-pulse` for skeletons. No extra dependency; use for simple hover/focus and loading. [Tailwind – Transition](https://tailwindcss.com/docs/transition-property), [Tailwind – Animation](https://tailwindcss.com/docs/animation).
- **React Spring:** [react-spring](https://www.react-spring.dev/) – physics-based; use if you prefer spring motion over duration-based. Heavier than Framer for simple UI.
- **Auto Animate:** [@formkit/auto-animate](https://auto-animate.formkit.com/) – drop-in for list/container; minimal API. Good for list reorder and add/remove without writing motion props.

If you choose Framer Motion, keep the patterns above consistent app-wide; document in DESIGN_SYSTEM.md.

---

## 4. Responsive design: breakpoints

- **Tailwind default breakpoints** (use unless overridden): `sm: 640px`, `md: 768px`, `lg: 1024px`, `xl: 1280px`, `2xl: 1536px`. [Tailwind – Responsive](https://tailwindcss.com/docs/responsive-design).
- **Conventions for this app:**
  - **Mobile-first:** Base styles for small screens; `md:` and `lg:` for tablet and desktop.
  - **Sidebar:** Collapsible to icon-only or drawer on small (e.g. `< lg`); full sidebar from `lg:` up. See § 5.
  - **Content:** Single column on mobile; 2–4 columns for card grids from `md:` or `lg:`.
  - **Tables:** Horizontal scroll on small screens; or card-style rows on mobile (stacked). See § 6.
  - **Auth / login:** Full-width on mobile with padding; `max-w-md` centered from `sm:`.

---

## 5. Responsive sidebar and shell

- **Desktop (`lg:` and up):** Persistent sidebar (full or icon-only collapsible). Content area uses remaining width.
- **Mobile / tablet (< `lg`):** Sidebar as overlay drawer (sheet) or hidden behind a menu button. Header shows menu trigger (e.g. hamburger) that opens sidebar. On navigation (Inertia visit or link click), close drawer after route change.
- **Touch targets:** Minimum 44×44px for icon buttons and nav items (sidebar, header). Use `min-h-[44px]`, `min-w-[44px]` or `p-3` so tap targets are large enough. [WCAG 2.5.5 Target Size](https://www.w3.org/WAI/WCAG22/Understanding/target-size.html).
- **References:** [Radix Sidebar](https://www.radix-ui.com/primitives/docs/components/sidebar) (collapsible, responsive patterns); [Shadcn Sidebar](https://ui.shadcn.com/docs/components/sidebar) for structure.

---

## 6. Responsive tables and cards

- **Tables (e.g. Workflow definitions, Vehicles list):** On small screens, either:
  - **Scroll:** Wrap table in `overflow-x-auto` with `min-w-[600px]` (or similar) so table scrolls horizontally and doesn’t break layout; or
  - **Card layout:** Below `md:`, render each row as a card (stacked) with key fields and “View” / “Edit” so users don’t need to scroll a wide table.
- **Fleet dashboard cards:** Grid `grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4` (or similar) so cards wrap and stay readable. Ensure card content doesn’t overflow (truncate or wrap labels).
- **Forms:** Stack form fields on mobile; consider two columns for compact forms from `md:` only if layout stays clear.

---

## 7. Typography and spacing on small screens

- **Font size:** Slightly smaller body on mobile is acceptable (e.g. `text-sm` base, `text-base` from `md:`). Keep headings proportional so hierarchy is clear.
- **Spacing:** Reduce padding on mobile (e.g. `p-4` → `p-4 md:p-6` for main content) to maximize content area. Keep touch targets at least 44px.

---

## 8. References (consolidated)

### Animations

| Resource | URL | Use |
|----------|-----|-----|
| Framer Motion (main) | https://www.framer.com/motion/ | React animations, API, examples. |
| Framer Motion – Reduce bundle | https://www.framer.com/motion/guide-reduce-bundle-size/ | LazyMotion, domAnimation, reduced motion. |
| Framer Motion – AnimatePresence | https://www.framer.com/motion/animate-presence/ | Exit animations with Inertia page changes. |
| Tailwind transition | https://tailwindcss.com/docs/transition-property | CSS transitions. |
| Tailwind animation | https://tailwindcss.com/docs/animation | pulse, spin, etc. |
| Auto Animate | https://auto-animate.formkit.com/ | Minimal list animation alternative. |
| React Spring | https://www.react-spring.dev/ | Physics-based animations. |

### Responsive and layout

| Resource | URL | Use |
|----------|-----|-----|
| Tailwind responsive | https://tailwindcss.com/docs/responsive-design | Breakpoints, mobile-first. |
| Tailwind container | https://tailwindcss.com/docs/container | Optional container queries. |
| Radix UI Sidebar | https://www.radix-ui.com/primitives/docs/components/sidebar | Sidebar primitive, collapsible. |
| Shadcn Sidebar | https://ui.shadcn.com/docs/components/sidebar | Sidebar structure and styles. |
| WCAG 2.5.5 Target size | https://www.w3.org/WAI/WCAG22/Understanding/target-size.html | Touch target size (≥44px). |
| MDN prefers-reduced-motion | https://developer.mozilla.org/en-US/docs/Web/CSS/@media/prefers-reduced-motion | Respect reduced motion. |

### Design system (cross-reference)

| Resource | URL |
|----------|-----|
| Shadcn UI | https://ui.shadcn.com/ |
| Radix UI | https://www.radix-ui.com/ |
| Tailwind CSS | https://tailwindcss.com/ |
| WCAG 2.1 | https://www.w3.org/WAI/WCAG21/quickref/ |

---

## 9. Implementation checklist

- [ ] Install Framer Motion (or choose CSS-only / Auto Animate); configure reduced motion.
- [ ] Add page transition (Inertia + AnimatePresence or motion wrapper) with 150–250ms fade.
- [ ] Apply list stagger or card enter animation on Fleet dashboard and key list pages.
- [ ] Add card hover and button tap feedback using motion.
- [ ] Define responsive sidebar: drawer/sheet below `lg`, persistent above; menu trigger in header.
- [ ] Ensure tables scroll or collapse to cards on small screens; verify touch targets ≥44px.
- [ ] Test login, Fleet dashboard, and Workflow definitions on mobile and tablet viewports.
- [ ] Document animation and breakpoint decisions in DESIGN_SYSTEM.md or this step.

---

## 10. Done when

- Page transitions and key UI elements (cards, lists, modals) use Framer Motion (or agreed approach) with consistent duration and easing; reduced motion is respected.
- App is usable and readable on mobile (e.g. 375px), tablet (768px), and desktop (1024px+); sidebar is accessible on small screens via drawer/menu.
- Tables and card grids adapt (scroll or card layout) and touch targets meet accessibility guidance. All references above are linked from this document for future use.

After UI-06, the UI/UX refactor covers design system, auth, shell, Fleet dashboard, maps, **animations**, and **responsive** with full references.
