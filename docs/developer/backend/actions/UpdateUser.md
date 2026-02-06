# UpdateUser

## Purpose

Updates the given user's attributes (e.g. name, email). If `email` changes, clears `email_verified_at` and sends the verification notification when unverified. If the request includes an `avatar` file, replaces the user's avatar in the media library (Spatie Media Library).

## Location

`app/Actions/UpdateUser.php`

## Method Signature

```php
public function handle(User $user, array $attributes, ?Request $request = null): void
```

## Dependencies

None.

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user` | `User` | User to update |
| `$attributes` | `array<string, mixed>` | Attributes to merge (e.g. `name`, `email`). The key `avatar` is excluded from the User model update and handled via media. |
| `$request` | `Illuminate\Http\Request\|null` | Optional. When present and `avatar` file is uploaded, clears the user's `avatar` media collection and adds the new file. |

## Return Value

`void`.

## Usage Examples

### From Controller (profile update with optional avatar)

```php
app(UpdateUser::class)->handle($user, $request->validated(), $request);
```

### From Job/Command (no file upload)

```php
(new UpdateUser())->handle($user, ['name' => 'New Name', 'email' => 'new@example.com']);
```

## Related Components

- **Controllers**: `UserProfileController`, `UserPasswordController` (via shared routes)
- **Routes**: `user-profile.edit`, `user-profile.update`, `password.edit`, `password.update`, etc.
- **Form Request**: `UpdateUserRequest`
- **Model**: `User` (implements `HasMedia`; avatar stored in `avatar` media collection with `thumb` and `profile` conversions)

## Notes

- Email change triggers `email_verified_at` clear and, if still unverified, `sendEmailVerificationNotification()`.
- Avatar is stored via [Spatie Laravel Media Library](https://spatie.be/docs/laravel-medialibrary); the `avatar` key is stripped from `$attributes` before calling `$user->update()` so the file is not written to the `users` table.
