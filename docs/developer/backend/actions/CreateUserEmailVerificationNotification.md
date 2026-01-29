# CreateUserEmailVerificationNotification

## Purpose

Sends the standard Laravel email verification notification to the given user.

## Location

`app/Actions/CreateUserEmailVerificationNotification.php`

## Method Signature

```php
public function handle(User $user): void
```

## Dependencies

None.

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user` | `User` | User to send verification email to |

## Return Value

`void`.

## Usage Examples

### From Controller

```php
app(CreateUserEmailVerificationNotification::class)->handle($user);
```

### From Job/Command

```php
(new CreateUserEmailVerificationNotification())->handle($user);
```

## Related Components

- **Controller**: `UserEmailVerificationNotificationController`
- **Routes**: `verification.notice` (GET), `verification.send` (POST)
- **Model**: `User`

## Notes

- Calls `$user->sendEmailVerificationNotification()`. User must implement `MustVerifyEmail` and use `Notifiable`.
