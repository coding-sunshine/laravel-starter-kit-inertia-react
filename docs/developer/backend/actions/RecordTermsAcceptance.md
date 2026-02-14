# RecordTermsAcceptance

## Purpose

Creates a `UserTermsAcceptance` record for a user and a specific terms version, storing accepted_at, optional IP and user agent for audit/compliance. Used when the user submits the “I accept” form on the terms acceptance page.

## Location

`app/Actions/RecordTermsAcceptance.php`

## Method Signature

```php
public function handle(User $user, TermsVersion $termsVersion, ?Request $request = null): UserTermsAcceptance
```

## Dependencies

- **Models**: `User`, `TermsVersion`, `UserTermsAcceptance`
- **Request**: Optional `Illuminate\Http\Request` for IP and user agent

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user` | `User` | The user accepting the terms |
| `$termsVersion` | `TermsVersion` | The terms version being accepted |
| `$request` | `Request\|null` | Optional; used for `ip()` and `userAgent()` (truncated to 45 chars for IP) |

## Return Value

The created `UserTermsAcceptance` model instance.

## Usage Examples

### From Controller

```php
foreach (TermsVersion::query()->whereIn('id', $requiredIds)->get() as $version) {
    $this->recordTermsAcceptance->handle($user, $version, $request);
}
```

## Related Components

- **Controllers**: `TermsAcceptController`
- **Models**: `UserTermsAcceptance`, `TermsVersion`, `User`
- **Routes**: `terms.accept.store`
