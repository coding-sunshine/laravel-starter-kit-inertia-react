# GetRequiredTermsVersionsForUser

## Purpose

Returns the list of required terms versions (Terms of Service or Privacy Policy) that the given user has not yet accepted. Used by the terms-acceptance flow and by `EnsureTermsAccepted` middleware to decide whether to redirect the user to the accept page.

## Location

`app/Actions/GetRequiredTermsVersionsForUser.php`

## Method Signature

```php
public function handle(User $user): Collection
```

## Dependencies

- **Models**: `TermsVersion`, `User` (via `User::termsAcceptances()`)

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user` | `User` | The authenticated user |

## Return Value

`Illuminate\Database\Eloquent\Collection<int, TermsVersion>` — Required terms versions not yet accepted by the user, ordered by `effective_at` ascending.

## Usage Examples

### From Middleware / Controller

```php
$pending = resolve(GetRequiredTermsVersionsForUser::class)->handle($request->user());
if ($pending->isEmpty()) {
    return $next($request);
}
return redirect()->to(route('terms.accept', ['intended' => $request->fullUrl()]));
```

## Related Components

- **Controllers**: `TermsAcceptController`
- **Middleware**: `EnsureTermsAccepted`
- **Models**: `TermsVersion`, `User`, `UserTermsAcceptance`
- **Routes**: `terms.accept`, `terms.accept.store`
