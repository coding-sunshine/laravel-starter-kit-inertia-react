# UpdateUserPassword

## Purpose

Updates the authenticated user's password to the given value (hashed internally).

## Location

`app/Actions/UpdateUserPassword.php`

## Method Signature

```php
public function handle(User $user, #[\SensitiveParameter] string $password): void
```

## Dependencies

None.

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user` | `User` | User whose password to update |
| `$password` | `string` | New plain-text password |

## Return Value

`void`.

## Usage Examples

### From Controller

```php
app(UpdateUserPassword::class)->handle($user, $request->string('password')->value());
```

### From Job/Command

```php
(new UpdateUserPassword())->handle($user, 'new-secret');
```

## Related Components

- **Controller**: `UserPasswordController`
- **Routes**: `password.edit` (GET), `password.update` (PUT)
- **Form Request**: `UpdateUserPasswordRequest`
- **Model**: `User`

## Notes

- Password is hashed via model cast. Used for authenticated password change, not reset.
