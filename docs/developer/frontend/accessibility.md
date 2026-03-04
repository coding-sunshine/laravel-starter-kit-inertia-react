# Accessibility

This document describes accessibility patterns and recommendations for the frontend. The app aims for **WCAG 2.2** alignment (focus visible, skip link, reduced motion, keyboard nav).

## Overview

The app uses **Radix UI** primitives for many components. Design tokens (`resources/css/design-tokens.css`) include reduced-motion handling and accessibility utilities.

## Skip link (WCAG 2.2)

A **skip link** is the first focusable element on every page (in `resources/views/app.blade.php`). It is visually hidden until focused, then appears so keyboard users can jump to `#main-content` and bypass repeated navigation. The main content wrapper has `id="main-content"` in app layout (`AppContent`) and in auth/welcome layouts.

## Focus visible

- Use the **`.focus-visible-ring`** class (from design-tokens.css) on custom links and buttons so keyboard focus shows a clear 2px outline. Radix components already provide focus rings.
- Avoid removing focus outlines without providing a visible alternative. The design token ring uses `:focus-visible` so it appears for keyboard focus, not mouse click.

## Keyboard shortcuts

| Shortcut | Action |
|----------|--------|
| **Mod+K** (Mac: ⌘K, Windows/Linux: Ctrl+K) | Open command palette (search, recent pages, fleet assistant, navigation) |
| **Tab** | Move focus to next focusable element |
| **Shift+Tab** | Move focus to previous focusable element |
| **Enter** | Activate focused link or button |
| **Escape** | Close modal or command palette |

These shortcuts are documented in-app in the command palette footer. Radix UI primitives provide built-in keyboard support, ARIA attributes, and focus management. When adding or modifying UI, follow the guidelines below to maintain and improve accessibility.

## Focus Management

- **Keyboard navigation**: All interactive elements (buttons, links, form inputs) should be reachable via Tab. Ensure no custom components trap focus without an escape.
- **Focus order**: Maintain logical tab order. Avoid `tabIndex` values that break the natural flow unless necessary (e.g. modals).
- **Focus visible**: Use the design token class **`.focus-visible-ring`** or Tailwind `focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring` so keyboard users see a clear focus indicator.

## Live Regions

- **Announcements**: Use `aria-live` for dynamic content that updates without a full page reload (e.g. toast messages, loading states).
- **Sonner toasts**: The Toaster component provides live region announcements for success/error messages.
- **Offline banner**: The offline banner uses `role="alert"` and `aria-live="assertive"` so screen readers announce when the app goes offline.
- **Loading**: For deferred content, show a skeleton or "Loading…" text so screen readers announce the state change.

## Semantic HTML

- Use semantic elements: `<article>`, `<section>`, `<nav>`, `<main>`, `<header>`, `<footer>`.
- Use `<button>` for actions and `<a>` for navigation. Do not use `div` with `onClick` for primary actions.
- Use proper heading hierarchy (`h1` → `h2` → `h3`); avoid skipping levels.

## Color and Contrast

- Ensure sufficient contrast between text and background. Use the theme's foreground/muted-foreground values.
- Do not rely on color alone to convey information (e.g. status indicators should also use text or icons).

## Forms

- Associate labels with inputs via `htmlFor` or wrap the input in a `<label>`.
- Use `aria-invalid` and `aria-describedby` for validation errors.
- Provide clear error messages that are announced to screen readers.

## Puck Page Builder

Blocks and custom content in Puck should use semantic HTML and safe, sanitized content. Avoid `dangerouslySetInnerHTML` unless the content is server-rendered and sanitized.

## Error Boundary

The `ErrorBoundary` component catches React render errors and shows a "Try again" / "Go home" UI. Users can retry or navigate away instead of seeing a blank screen.

## Testing

### Automated (axe)

- **Browser extension**: Install [axe DevTools](https://www.deque.com/axe/devtools/) (Chrome/Firefox/Edge) and run "Scan all of my page" on key flows (login, dashboard, fleet dashboard, settings). Fix reported violations (focus, contrast, ARIA, labels).
- **Playwright**: For CI or local runs, use `@axe-core/playwright` to run axe on critical pages. Example:
  ```ts
  import AxeBuilder from '@axe-core/playwright';
  test('dashboard has no a11y violations', async ({ page }) => {
    await page.goto('/dashboard');
    const results = await new AxeBuilder({ page }).analyze();
    expect(results.violations).toEqual([]);
  });
  ```
- Run axe after major UI changes to catch regressions.

### Manual

- **Keyboard-only**: Tab through the page; ensure focus order is logical and all interactive elements are reachable. Use Enter/Space to activate, Escape to close modals and the command palette.
- **Screen reader**: Test critical paths (login, navigation, fleet list, form submit) with VoiceOver (macOS), NVDA (Windows), or TalkBack (Android). Ensure headings, landmarks, and live regions are announced.

## Reduced motion

Design tokens apply `prefers-reduced-motion: reduce` globally: animations and transitions are shortened to 0.01ms so users who prefer reduced motion are not disadvantaged. The shimmer animation is disabled when reduced motion is preferred.

## References

- [Radix UI Accessibility](https://www.radix-ui.com/primitives/docs/overview/accessibility)
- [WCAG 2.1 Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)
- [WCAG 2.2](https://www.w3.org/WAI/WCAG22/quickref/) – Skip link, focus visible.
- [Inertia.js](https://inertiajs.com/) – SPA behavior; ensure focus and announcements work with partial reloads.
