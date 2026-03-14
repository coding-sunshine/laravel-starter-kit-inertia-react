# EmailCampaignController

Manages email campaign creation, AI personalisation, and sending.

## Location

`app/Http/Controllers/EmailCampaignController.php`

## Routes

| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | `/email-campaigns` | `email-campaigns.index` | List campaigns |
| POST | `/email-campaigns` | `email-campaigns.store` | Create campaign |
| POST | `/email-campaigns/{emailCampaign}/personalise` | `email-campaigns.personalise` | AI personalise (JSON) |
| POST | `/email-campaigns/{emailCampaign}/send` | `email-campaigns.send` | Send campaign |
| DELETE | `/email-campaigns/{emailCampaign}` | `email-campaigns.destroy` | Delete campaign |

## Actions

- `GenerateEmailCampaignAction` — AI personalisation

## Page

`resources/js/pages/email-campaigns/index.tsx`
