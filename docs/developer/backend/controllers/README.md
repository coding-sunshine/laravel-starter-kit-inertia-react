# Controllers

Controllers handle HTTP requests and coordinate between routes, Actions, and Inertia pages.

## Pattern

All Controllers:
- Are `final readonly` classes
- Use type-hinted dependencies
- Return Inertia responses or redirects
- Use Form Requests for validation

## Available Controllers

| Controller | Purpose | Documented |
|------------|---------|------------|
| [SessionController](./SessionController.md) | Login, logout, 2FA redirect | ✅ |
| [UserController](./UserController.md) | Registration, account deletion | ✅ |
| [UserEmailResetNotificationController](./UserEmailResetNotificationController.md) | Forgot-password form, send reset link | ✅ |
| [UserEmailVerificationController](./UserEmailVerificationController.md) | Verification link handler | ✅ |
| [UserEmailVerificationNotificationController](./UserEmailVerificationNotificationController.md) | Verification notice, resend verification | ✅ |
| [UserPasswordController](./UserPasswordController.md) | Forgot-password reset, change password | ✅ |
| [UserProfileController](./UserProfileController.md) | Profile edit, update | ✅ |
| [UserTwoFactorAuthenticationController](./UserTwoFactorAuthenticationController.md) | 2FA settings page | ✅ |


