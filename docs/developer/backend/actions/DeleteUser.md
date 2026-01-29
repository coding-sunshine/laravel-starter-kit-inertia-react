# DeleteUser

## Purpose

Permanently deletes the given user via `$user->delete()`.

## Location

`app/Actions/DeleteUser.php`

## Method Signature

```php
public function handle(User $user): void
```

## Dependencies

None.

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user` | `User` | User to delete |

## Return Value

`void`.

## Usage Examples

### From Controller

```php
app(DeleteUser::class)->handle($user);
```

### From Job/Command

```php
(new DeleteUser())->handle($user);
```

## Related Components

- **Controller**: `UserController` (account deletion)
- **Routes**: `user.destroy` (DELETE)
- **Form Request**: `DeleteUserRequest`
- **Model**: `User`

## Notes

- Used by `UserController::destroy` after logout. Ensure authorization (e.g. current user only) is enforced via `DeleteUserRequest` or policy.
