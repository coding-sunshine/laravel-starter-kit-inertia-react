# lead-generation/coaching

## Purpose

AI coaching panel for agents working a specific contact. Shows lead brief, score, and contextual coaching tips including objection handling scripts.

## Location

`resources/js/pages/lead-generation/coaching.tsx`

## Route

`GET /lead-generation/coaching/{contact}` (`lead-generation.coaching`)

## Props

- `contact` — Contact model
- `brief` — AI-generated lead brief (brief, generated_at, model)
- `score` — Lead score (0-100)
- `coaching_tips` — Array of tips (type: action|warning|info|script, text)

## Related Components

- **Controller**: `LeadGenerationController`
