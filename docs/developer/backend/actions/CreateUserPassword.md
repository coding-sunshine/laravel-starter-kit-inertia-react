# CreateUserPassword

## Purpose

Resets a user's password via the forgot-password token, updates `password` and `remember_token`, and dispatches `PasswordReset`.

## Location

`app/Actions/CreateUserPassword.php`

## Method Signature

```php
public function handle(array $credentials, #[\SensitiveParameter] string $password): mixed
```

## Dependencies

None.

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$credentials` | `array<string, mixed>` | Token, email, etc. for `Password::reset` |
| `$password` | `string` | New plain-text password |

## Return Value

Return value from `Password::reset` (e.g. `Password::PASSWORD_RESET`).

## Usage Examples

### From Controller

```php
$status = app(CreateUserPassword::class)->handle(
    $request->only('email', 'password', 'password_confirmation', 'token'),
    $request->string('password')->value()
);
```

### From Job/Command

```php
(new CreateUserPassword())->handle([
    'email' => $email,
    'password' => $newPassword,
    'password_confirmation' => $newPassword,
    'token' => $token,
], $newPassword);
```

## Related Components

- **Controller**: `UserPasswordController`
- **Routes**: `password.reset` (GET), `password.store` (POST)
- **Form Request**: `CreateUserPasswordRequest`
- **Model**: `User`

## Notes

- Uses `Password::reset`. Fires `Illuminate\Auth\Events\PasswordReset` after update.
