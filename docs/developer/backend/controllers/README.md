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
| [SessionController](./SessionController.md) | N/A | ✅ |
| [UserController](./UserController.md) | N/A | ✅ |
| [UserEmailResetNotificationController](./UserEmailResetNotificationController.md) | N/A | ✅ |
| [UserEmailVerificationController](./UserEmailVerificationController.md) | N/A | ✅ |
| [UserEmailVerificationNotificationController](./UserEmailVerificationNotificationController.md) | N/A | ✅ |
| [UserPasswordController](./UserPasswordController.md) | N/A | ✅ |
| [UserProfileController](./UserProfileController.md) | N/A | ✅ |
| [UserTwoFactorAuthenticationController](./UserTwoFactorAuthenticationController.md) | N/A | ✅ |
| [ProfileExportPdfController](./ProfileExportPdfController.md) | N/A | ✅ |
| [ContactSubmissionController](./ContactSubmissionController.md) | N/A | ✅ |
| [CookieConsentController](./CookieConsentController.md) | N/A | ✅ |
| [PersonalDataExportController](./PersonalDataExportController.md) | N/A | ✅ |
| [OnboardingController](./OnboardingController.md) | N/A | ✅ |


