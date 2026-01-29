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
| [CreateUser](./CreateUser.md) | Create user, fire `Registered` | ✅ |
| [CreateUserEmailResetNotification](./CreateUserEmailResetNotification.md) | Send password-reset link | ✅ |
| [CreateUserEmailVerificationNotification](./CreateUserEmailVerificationNotification.md) | Send verification email | ✅ |
| [CreateUserPassword](./CreateUserPassword.md) | Reset password via token | ✅ |
| [DeleteUser](./DeleteUser.md) | Delete user | ✅ |
| [UpdateUser](./UpdateUser.md) | Update user attributes; handle email change | ✅ |
| [UpdateUserPassword](./UpdateUserPassword.md) | Update authenticated user password | ✅ |


