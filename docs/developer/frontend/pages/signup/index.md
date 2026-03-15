# Signup Index (Plan Selection)

**Path**: `resources/js/pages/signup/index.tsx`

**Route**: GET /signup

Displays available plans in a card grid. Users select a plan and are redirected to the registration form.

## Props

| Prop | Type | Description |
|------|------|-------------|
| plans | Plan[] | Public plans from the database |

## Features

- Card grid with plan pricing, features, and CTAs
- "Most Popular" badge on the Growth plan
- Feature flag labels mapped from class names
- Pan analytics: `signup-plan-{slug}`
