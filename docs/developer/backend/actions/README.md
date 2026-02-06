# Actions

Actions are single-purpose classes that encapsulate business logic. They live in `app/Actions/` and follow a consistent pattern.

## Pattern

All Actions:
- Have a single `handle()` method
- Are `final readonly` classes
- Accept dependencies via constructor
- Return typed values

## Available Actions

| Action | Purpose | Documented |
|--------|---------|------------|
| [CreateUser](./CreateUser.md) | N/A | ✅ |
| [CreateUserEmailResetNotification](./CreateUserEmailResetNotification.md) | N/A | ✅ |
| [CreateUserEmailVerificationNotification](./CreateUserEmailVerificationNotification.md) | N/A | ✅ |
| [CreateUserPassword](./CreateUserPassword.md) | N/A | ✅ |
| [DeleteUser](./DeleteUser.md) | N/A | ✅ |
| [LoggingConfirmTwoFactorAuthentication](./LoggingConfirmTwoFactorAuthentication.md) | Logs 2FA confirm activity | ✅ |
| [LoggingDisableTwoFactorAuthentication](./LoggingDisableTwoFactorAuthentication.md) | Logs 2FA disable activity | ✅ |
| [LoggingEnableTwoFactorAuthentication](./LoggingEnableTwoFactorAuthentication.md) | Logs 2FA enable activity | ✅ |
| [LoggingGenerateNewRecoveryCodes](./LoggingGenerateNewRecoveryCodes.md) | Logs recovery codes regenerated | ✅ |
| [UpdateUser](./UpdateUser.md) | N/A | ✅ |
| [UpdateUserPassword](./UpdateUserPassword.md) | N/A | ✅ |


