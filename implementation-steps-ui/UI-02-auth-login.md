# UI Step 2: Auth and login form

**Goal:** Redesign the login form and auth layout so the first impression is professional and aligned with the design system. Use #333333, #4348be, and white; clear hierarchy, accessible form controls, and a proper look and feel (not a generic admin template).

**References:** UI-01 (design system), `resources/js/pages/session/create.tsx`, `resources/js/layouts/auth-layout.tsx`, `resources/js/layouts/auth/auth-simple-layout.tsx`. Routes: `GET /login`, `POST /login` (SessionController).

---

## 1. Prerequisites

- UI-01 design system in place: color tokens (#333333, #4348be, white), typography, and Shadcn/Radix alignment.
- Login page uses AuthLayout and SessionController form (email, password, remember, forgot password link). No design tokens applied yet.

---

## 2. Current state

- **Page:** `session/create.tsx` – form with email, password, remember checkbox, “Forgot password?” link, submit “Log in”. Uses AuthLayout (title + description), Input, Label, Button, Checkbox from UI components.
- **Layout:** `auth-layout.tsx` delegates to `auth-simple-layout.tsx`. Layout is minimal (title, description, children). No strong branding or design system colors.
- **Route:** `GET /login` → `SessionController::create`; `POST /login` → `SessionController::store`. Redirect to dashboard on success.

---

## 3. Auth layout redesign

- **Background:** Use white or a very subtle gradient/pattern that stays within the palette (e.g. white with a soft #4348be accent strip or corner). Avoid grey-heavy “admin” look.
- **Container:** Centered card or panel with consistent max-width (e.g. `max-w-md`), padding, and subtle shadow or border per design system. Card background white; text #333333.
- **Branding:** If the app shows a logo (e.g. AppLogo or org name), place it above the form; use #333333 or #4348be for logo/wordmark. Ensure sufficient size for recognition.
- **Title and description:** Use design system typography (e.g. `text-2xl font-semibold` for title, `text-muted` or grey for description). Colors: #333333 for title, slightly lighter for description.

---

## 4. Login form redesign

- **Labels:** Clear, visible labels (Label component) in #333333; ensure proper `htmlFor` and `id` association for accessibility.
- **Inputs:** Border and focus ring use design tokens: default border light grey; focus border/ring #4348be. Placeholder text subtle (e.g. “email@example.com”, “Password”). Sufficient padding and height for touch targets.
- **Forgot password link:** Use #4348be as link color; underline or clear affordance. Place next to password label (as now) or below password field.
- **Remember checkbox:** Align with design system; checked state can use #4348be. Label “Remember me” (or equivalent) in #333333.
- **Submit button:** Primary CTA – background #4348be, text white, hover state slightly darker or with subtle scale/shadow. Full-width or prominent; loading state (e.g. spinner) when `processing` is true. Ensure button is clearly the main action.
- **Error display:** Keep InputError (or equivalent) for field-level errors; use destructive/error color from design system. Status message (e.g. “Invalid credentials”) visible and readable.

---

## 5. Optional: split auth layout

- If a “split” layout is desired (e.g. left: branding/illustration, right: form), use `auth-split-layout` or a variant; ensure left panel uses palette (e.g. #4348be tint or image with overlay) and right panel stays white with form. Do not overload with imagery; keep focus on the form.

---

## 6. Accessibility and UX

- **Focus order:** Tab order: email → password → remember → forgot link → submit. Logical and keyboard-only navigable.
- **Focus visible:** Focus ring #4348be, 2px offset so it’s clearly visible.
- **Errors:** Associate error messages with inputs (aria-describedby); announce on submit failure.
- **Loading:** Disable submit and show loading state during POST to avoid double submit.

---

## 7. Other auth pages (optional in this step)

- Password reset request, password reset form, email verification: apply same layout and tokens when touched. Can be a follow-up task; this step focuses on login and the shared auth layout used by login.

---

## 8. Done when

- Login page uses #333333, #4348be, and white consistently; form is in a clear, centered container with proper hierarchy.
- Primary button is #4348be; inputs have correct focus and borders; forgot password and errors are visible and accessible.
- Auth layout (auth-layout + auth-simple-layout or chosen variant) applies the same design system so any future auth page (e.g. forgot password) can reuse it.

Proceed to **UI-03** (`UI-03-app-shell-sidebar.md`) for the main dashboard shell and sidebar restructure.
