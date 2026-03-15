# Signup Register

**Path**: `resources/js/pages/signup/register.tsx`

**Route**: GET /signup/register?plan={slug}

Registration form for a chosen plan. POSTs to `/signup/provision`.

## Props

| Prop | Type | Description |
|------|------|-------------|
| planSlug | string | Selected plan slug |
| planName | string | Human-readable plan name |
| planPrice | number | Monthly price |
| setupFee | number | One-time setup fee |
| gateway | string | Active billing gateway (stripe|eway) |
