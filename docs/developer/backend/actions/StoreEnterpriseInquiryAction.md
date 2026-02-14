# StoreEnterpriseInquiryAction

## Purpose

Creates an enterprise inquiry from validated form data (name, email, company, phone, message).

## Location

`app/Actions/StoreEnterpriseInquiryAction.php`

## Method Signature

```php
public function handle(array $data): EnterpriseInquiry
```

## Dependencies

None.

## Parameters

| Parameter | Type | Description |
|-----------|------|--------------|
| `$data` | `array{name: string, email: string, company?: string, phone?: string, message: string}` | Validated enterprise inquiry form fields |

## Return Value

The created `EnterpriseInquiry` model instance.

## Usage Examples

### From Controller

```php
$inquiry = app(StoreEnterpriseInquiryAction::class)->handle($request->safe()->only(['name', 'email', 'company', 'phone', 'message']));
```

## Related Components

- **Controller**: `EnterpriseInquiryController` (enterprise form)
- **Routes**: `enterprise-inquiries.create` (GET), `enterprise-inquiries.store` (POST)
- **Model**: `EnterpriseInquiry`
