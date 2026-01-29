# UpdateUser

## Purpose

Updates the given user's attributes. If `email` changes, clears `email_verified_at` and sends the verification notification when unverified.

## Location

`app/Actions/UpdateUser.php`

## Method Signature

```php
public function handle(User $user, array $attributes): void
```

## Dependencies

None.

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user` | `User` | User to update |
| `$attributes` | `array<string, mixed>` | Attributes to merge (e.g. `name`, `email`) |

## Return Value

`void`.

## Usage Examples

### From Controller

```php
app(UpdateUser::class)->handle($user, $request->safe()->all());
```

### From Job/Command

```php
(new UpdateUser())->handle($user, ['name' => 'New Name', 'email' => 'new@example.com']);
```

## Related Components

- **Controllers**: `UserProfileController`, `UserPasswordController` (via shared routes)
- **Routes**: `user-profile.edit`, `user-profile.update`, `password.edit`, `password.update`, etc.
- **Form Request**: `UpdateUserRequest`
- **Model**: `User`

## Notes

- Email change triggers `email_verified_at` clear and, if still unverified, `sendEmailVerificationNotification()`.
