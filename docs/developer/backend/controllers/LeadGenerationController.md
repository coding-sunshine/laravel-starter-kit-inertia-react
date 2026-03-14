# LeadGenerationController

## Purpose

Lead generation dashboard providing landing page AI copy, lead briefs, lead scoring/routing, and coaching panel.

## Location

`app/Http/Controllers/LeadGenerationController.php`

## Routes

| Method | Path | Name | Description |
|--------|------|------|-------------|
| GET | /lead-generation | lead-generation.index | Dashboard |
| POST | /lead-generation/landing-page-copy | lead-generation.landing-page-copy | Generate landing page AI copy |
| POST | /lead-generation/lead-brief/{contact} | lead-generation.lead-brief | Generate lead brief |
| POST | /lead-generation/score-and-route/{contact} | lead-generation.score-and-route | Score and route a lead |
| GET | /lead-generation/coaching/{contact} | lead-generation.coaching | Coaching panel |

## Related Components

- **Actions**: `GenerateLandingPageCopyAction`, `GenerateLeadBriefAction`, `RouteLeadAction`
- **Service**: `LeadScoringService`
- **Pages**: `lead-generation/index`, `lead-generation/coaching`
