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
| CreateUser | N/A | ❌ |
| CreateUserEmailResetNotification | N/A | ❌ |
| CreateUserEmailVerificationNotification | N/A | ❌ |
| CreateUserPassword | N/A | ❌ |
| DeleteUser | N/A | ❌ |
| UpdateUser | N/A | ❌ |
| UpdateUserPassword | N/A | ❌ |


> **Note**: Individual action documentation will be added here as they are documented.
