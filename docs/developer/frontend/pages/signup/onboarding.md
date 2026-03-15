# Signup Onboarding Checklist

**Path**: `resources/js/pages/signup/onboarding.tsx`

**Route**: GET /signup/onboarding

Guided onboarding checklist shown to new subscribers after signup. Tracks progress through 7 steps.

## Props

| Prop | Type | Description |
|------|------|-------------|
| steps | Step[] | All onboarding steps with completion status |
| completedCount | number | Number of completed steps |
| totalSteps | number | Total step count (7) |

## Steps

1. Set Password
2. Sign Subscriber Agreement
3. Complete CRM Tour
4. Upload Contacts
5. Connect Website
6. Launch First Flyer
7. Meet BDM

## Features

- Progress bar with percentage
- Tick marks for completed steps
- "Mark done" button for manual completion
- "All set" card when all steps complete
- Pan analytics per step
