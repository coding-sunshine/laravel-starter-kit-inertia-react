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
| CreateUser | Creates a new user account | ❌ |
| UpdateUser | Updates user information | ❌ |
| DeleteUser | Deletes a user account | ❌ |
| CreateUserPassword | Creates/resets user password | ❌ |
| UpdateUserPassword | Updates user password | ❌ |
| CreateUserEmailResetNotification | Sends password reset email | ❌ |
| CreateUserEmailVerificationNotification | Sends email verification | ❌ |

> **Note**: Individual action documentation will be added here as they are documented.
