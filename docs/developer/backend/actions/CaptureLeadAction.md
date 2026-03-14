# CaptureLeadAction

## Purpose

Normalizes incoming leads from any channel (web form, SMS, chat, phone) into a Contact with ContactEmail, ContactPhone, ContactAttribution, and EngagementEvent records.

## Location

`app/Actions/CaptureLeadAction.php`

## Method Signature

```php
public function handle(array $data, int $organizationId): Contact
```

## Dependencies

None (no constructor dependencies)

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| data | array | Lead data: first_name, last_name, email, phone, channel, source_name, campaign_name, ad_name, page_url |
| organizationId | int | Organization to assign the lead to |

## Return Value

Returns the created or found `Contact` model.

## Related Components

- **Controller**: `LeadCaptureController`
- **Route**: `lead-capture.store` (POST /lead-capture)
