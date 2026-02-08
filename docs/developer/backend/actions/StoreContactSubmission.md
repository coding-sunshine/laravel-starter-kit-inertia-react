# StoreContactSubmission

## Purpose

Creates a contact submission from validated form data (name, email, subject, message).

## Location

`app/Actions/StoreContactSubmission.php`

## Method Signature

```php
public function handle(array $data): ContactSubmission
```

## Dependencies

None.

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$data` | `array{name: string, email: string, subject: string, message: string}` | Validated contact form fields |

## Return Value

The created `ContactSubmission` model instance.

## Usage Examples

### From Controller

```php
$submission = app(StoreContactSubmission::class)->handle($request->safe()->only(['name', 'email', 'subject', 'message']));
```

## Related Components

- **Controller**: `ContactSubmissionController` (contact form)
- **Routes**: `contact.create` (GET), `contact.store` (POST)
- **Model**: `ContactSubmission`
