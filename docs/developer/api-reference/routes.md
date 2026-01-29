# API Reference

This document lists all available routes in the application.

**Last Updated**: 2026-01-29

## Filament Admin Panel

Filament registers the admin panel and auth routes under `/admin` (e.g. `GET /admin`, `GET /admin/login`, `POST /admin/logout`). See [Filament Admin Panel](../backend/filament.md) for details.

## Closure

| Method | URI | Route Name | Middleware |
|--------|-----|------------|------------|
| POST | `_boost/browser-logs` | boost.browser-logs | - |
| GET | `/` | home | web |
| GET | `dashboard` | dashboard | web, auth, verified |
| GET, POST, PUT, PATCH, DELETE | `settings` | - | web, auth |
| GET | `settings/appearance` | appearance.edit | web, auth |
| GET | `storage/{path}` | storage.local | - |


## SessionController

**Controller**: `App\Http\Controllers\SessionController`

| Method | URI | Route Name | Middleware |
|--------|-----|------------|------------|
| GET | `login` | login | web, guest |
| POST | `login` | login.store | web, guest |
| POST | `logout` | logout | web, auth |

### create

**Route**: `login`

**URI**: `login`

**Methods**: GET

**Middleware**: web, guest

**Method Parameters**:
- `request`: `Illuminate\Http\Request`

### store

**Route**: `login.store`

**URI**: `login`

**Methods**: POST

**Middleware**: web, guest

**Method Parameters**:
- `request`: `App\Http\Requests\CreateSessionRequest`

### destroy

**Route**: `logout`

**URI**: `logout`

**Methods**: POST

**Middleware**: web, auth

**Method Parameters**:
- `request`: `Illuminate\Http\Request`


## ConfirmablePasswordController

**Controller**: `Laravel\Fortify\Http\Controllers\ConfirmablePasswordController`

| Method | URI | Route Name | Middleware |
|--------|-----|------------|------------|
| GET | `user/confirm-password` | password.confirm | web, auth:web |
| POST | `user/confirm-password` | password.confirm.store | web, auth:web |

### show

**Route**: `password.confirm`

**URI**: `user/confirm-password`

**Methods**: GET

**Middleware**: web, auth:web

**Method Parameters**:
- `request`: `Illuminate\Http\Request`

### store

**Route**: `password.confirm.store`

**URI**: `user/confirm-password`

**Methods**: POST

**Middleware**: web, auth:web

**Method Parameters**:
- `request`: `Illuminate\Http\Request`


## ConfirmedPasswordStatusController

**Controller**: `Laravel\Fortify\Http\Controllers\ConfirmedPasswordStatusController`

| Method | URI | Route Name | Middleware |
|--------|-----|------------|------------|
| GET | `user/confirmed-password-status` | password.confirmation | web, auth:web |

### show

**Route**: `password.confirmation`

**URI**: `user/confirmed-password-status`

**Methods**: GET

**Middleware**: web, auth:web

**Method Parameters**:
- `request`: `Illuminate\Http\Request`


## TwoFactorAuthenticatedSessionController

**Controller**: `Laravel\Fortify\Http\Controllers\TwoFactorAuthenticatedSessionController`

| Method | URI | Route Name | Middleware |
|--------|-----|------------|------------|
| GET | `two-factor-challenge` | two-factor.login | web, guest:web |
| POST | `two-factor-challenge` | two-factor.login.store | web, guest:web, throttle:two-factor |

### create

**Route**: `two-factor.login`

**URI**: `two-factor-challenge`

**Methods**: GET

**Middleware**: web, guest:web

**Method Parameters**:
- `request`: `Laravel\Fortify\Http\Requests\TwoFactorLoginRequest`

### store

**Route**: `two-factor.login.store`

**URI**: `two-factor-challenge`

**Methods**: POST

**Middleware**: web, guest:web, throttle:two-factor

**Method Parameters**:
- `request`: `Laravel\Fortify\Http\Requests\TwoFactorLoginRequest`


## TwoFactorAuthenticationController

**Controller**: `Laravel\Fortify\Http\Controllers\TwoFactorAuthenticationController`

| Method | URI | Route Name | Middleware |
|--------|-----|------------|------------|
| POST | `user/two-factor-authentication` | two-factor.enable | web, auth:web, password.confirm |
| DELETE | `user/two-factor-authentication` | two-factor.disable | web, auth:web, password.confirm |

### store

**Route**: `two-factor.enable`

**URI**: `user/two-factor-authentication`

**Methods**: POST

**Middleware**: web, auth:web, password.confirm

**Method Parameters**:
- `request`: `Illuminate\Http\Request`
- `enable`: `Laravel\Fortify\Actions\EnableTwoFactorAuthentication`

### destroy

**Route**: `two-factor.disable`

**URI**: `user/two-factor-authentication`

**Methods**: DELETE

**Middleware**: web, auth:web, password.confirm

**Method Parameters**:
- `request`: `Illuminate\Http\Request`
- `disable`: `Laravel\Fortify\Actions\DisableTwoFactorAuthentication`


## ConfirmedTwoFactorAuthenticationController

**Controller**: `Laravel\Fortify\Http\Controllers\ConfirmedTwoFactorAuthenticationController`

| Method | URI | Route Name | Middleware |
|--------|-----|------------|------------|
| POST | `user/confirmed-two-factor-authentication` | two-factor.confirm | web, auth:web, password.confirm |

### store

**Route**: `two-factor.confirm`

**URI**: `user/confirmed-two-factor-authentication`

**Methods**: POST

**Middleware**: web, auth:web, password.confirm

**Method Parameters**:
- `request`: `Illuminate\Http\Request`
- `confirm`: `Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication`


## TwoFactorQrCodeController

**Controller**: `Laravel\Fortify\Http\Controllers\TwoFactorQrCodeController`

| Method | URI | Route Name | Middleware |
|--------|-----|------------|------------|
| GET | `user/two-factor-qr-code` | two-factor.qr-code | web, auth:web, password.confirm |

### show

**Route**: `two-factor.qr-code`

**URI**: `user/two-factor-qr-code`

**Methods**: GET

**Middleware**: web, auth:web, password.confirm

**Method Parameters**:
- `request`: `Illuminate\Http\Request`


## TwoFactorSecretKeyController

**Controller**: `Laravel\Fortify\Http\Controllers\TwoFactorSecretKeyController`

| Method | URI | Route Name | Middleware |
|--------|-----|------------|------------|
| GET | `user/two-factor-secret-key` | two-factor.secret-key | web, auth:web, password.confirm |

### show

**Route**: `two-factor.secret-key`

**URI**: `user/two-factor-secret-key`

**Methods**: GET

**Middleware**: web, auth:web, password.confirm

**Method Parameters**:
- `request`: `Illuminate\Http\Request`


## RecoveryCodeController

**Controller**: `Laravel\Fortify\Http\Controllers\RecoveryCodeController`

| Method | URI | Route Name | Middleware |
|--------|-----|------------|------------|
| GET | `user/two-factor-recovery-codes` | two-factor.recovery-codes | web, auth:web, password.confirm |
| POST | `user/two-factor-recovery-codes` | two-factor.regenerate-recovery-codes | web, auth:web, password.confirm |

### index

**Route**: `two-factor.recovery-codes`

**URI**: `user/two-factor-recovery-codes`

**Methods**: GET

**Middleware**: web, auth:web, password.confirm

**Method Parameters**:
- `request`: `Illuminate\Http\Request`

### store

**Route**: `two-factor.regenerate-recovery-codes`

**URI**: `user/two-factor-recovery-codes`

**Methods**: POST

**Middleware**: web, auth:web, password.confirm

**Method Parameters**:
- `request`: `Illuminate\Http\Request`
- `generate`: `Laravel\Fortify\Actions\GenerateNewRecoveryCodes`


## UserController

**Controller**: `App\Http\Controllers\UserController`

| Method | URI | Route Name | Middleware |
|--------|-----|------------|------------|
| DELETE | `user` | user.destroy | web, auth |
| GET | `register` | register | web, guest |
| POST | `register` | register.store | web, guest |

### destroy

**Route**: `user.destroy`

**URI**: `user`

**Methods**: DELETE

**Middleware**: web, auth

**Method Parameters**:
- `request`: `App\Http\Requests\DeleteUserRequest`
- `user`: `App\Models\User`
- `action`: `App\Actions\DeleteUser`

### create

**Route**: `register`

**URI**: `register`

**Methods**: GET

**Middleware**: web, guest

### store

**Route**: `register.store`

**URI**: `register`

**Methods**: POST

**Middleware**: web, guest

**Method Parameters**:
- `request`: `App\Http\Requests\CreateUserRequest`
- `action`: `App\Actions\CreateUser`


## UserProfileController

**Controller**: `App\Http\Controllers\UserProfileController`

| Method | URI | Route Name | Middleware |
|--------|-----|------------|------------|
| GET | `settings/profile` | user-profile.edit | web, auth |
| PATCH | `settings/profile` | user-profile.update | web, auth |

### edit

**Route**: `user-profile.edit`

**URI**: `settings/profile`

**Methods**: GET

**Middleware**: web, auth

**Method Parameters**:
- `request`: `Illuminate\Http\Request`

### update

**Route**: `user-profile.update`

**URI**: `settings/profile`

**Methods**: PATCH

**Middleware**: web, auth

**Method Parameters**:
- `request`: `App\Http\Requests\UpdateUserRequest`
- `user`: `App\Models\User`
- `action`: `App\Actions\UpdateUser`


## UserPasswordController

**Controller**: `App\Http\Controllers\UserPasswordController`

| Method | URI | Route Name | Middleware |
|--------|-----|------------|------------|
| GET | `settings/password` | password.edit | web, auth |
| PUT | `settings/password` | password.update | web, auth, throttle:6,1 |
| GET | `reset-password/{token}` | password.reset | web, guest |
| POST | `reset-password` | password.store | web, guest |

### edit

**Route**: `password.edit`

**URI**: `settings/password`

**Methods**: GET

**Middleware**: web, auth

### update

**Route**: `password.update`

**URI**: `settings/password`

**Methods**: PUT

**Middleware**: web, auth, throttle:6,1

**Method Parameters**:
- `request`: `App\Http\Requests\UpdateUserPasswordRequest`
- `user`: `App\Models\User`
- `action`: `App\Actions\UpdateUserPassword`

### create

**Route**: `password.reset`

**URI**: `reset-password/{token}`

**Methods**: GET

**Parameters**:
- `token`

**Middleware**: web, guest

**Method Parameters**:
- `request`: `Illuminate\Http\Request`

### store

**Route**: `password.store`

**URI**: `reset-password`

**Methods**: POST

**Middleware**: web, guest

**Method Parameters**:
- `request`: `App\Http\Requests\CreateUserPasswordRequest`
- `action`: `App\Actions\CreateUserPassword`


## UserTwoFactorAuthenticationController

**Controller**: `App\Http\Controllers\UserTwoFactorAuthenticationController`

| Method | URI | Route Name | Middleware |
|--------|-----|------------|------------|
| GET | `settings/two-factor` | two-factor.show | web, auth, password.confirm |

### show

**Route**: `two-factor.show`

**URI**: `settings/two-factor`

**Methods**: GET

**Middleware**: web, auth, password.confirm

**Method Parameters**:
- `request`: `App\Http\Requests\ShowUserTwoFactorAuthenticationRequest`
- `user`: `App\Models\User`


## UserEmailResetNotificationController

**Controller**: `App\Http\Controllers\UserEmailResetNotificationController`

| Method | URI | Route Name | Middleware |
|--------|-----|------------|------------|
| GET | `forgot-password` | password.request | web, guest |
| POST | `forgot-password` | password.email | web, guest |

### create

**Route**: `password.request`

**URI**: `forgot-password`

**Methods**: GET

**Middleware**: web, guest

**Method Parameters**:
- `request`: `Illuminate\Http\Request`

### store

**Route**: `password.email`

**URI**: `forgot-password`

**Methods**: POST

**Middleware**: web, guest

**Method Parameters**:
- `request`: `App\Http\Requests\CreateUserEmailResetNotificationRequest`
- `action`: `App\Actions\CreateUserEmailResetNotification`


## UserEmailVerificationNotificationController

**Controller**: `App\Http\Controllers\UserEmailVerificationNotificationController`

| Method | URI | Route Name | Middleware |
|--------|-----|------------|------------|
| GET | `verify-email` | verification.notice | web, auth |
| POST | `email/verification-notification` | verification.send | web, auth, throttle:6,1 |

### create

**Route**: `verification.notice`

**URI**: `verify-email`

**Methods**: GET

**Middleware**: web, auth

**Method Parameters**:
- `request`: `Illuminate\Http\Request`
- `user`: `App\Models\User`

### store

**Route**: `verification.send`

**URI**: `email/verification-notification`

**Methods**: POST

**Middleware**: web, auth, throttle:6,1

**Method Parameters**:
- `user`: `App\Models\User`
- `action`: `App\Actions\CreateUserEmailVerificationNotification`


## UserEmailVerificationController

**Controller**: `App\Http\Controllers\UserEmailVerificationController`

| Method | URI | Route Name | Middleware |
|--------|-----|------------|------------|
| GET | `verify-email/{id}/{hash}` | verification.verify | web, auth, signed |

### update

**Route**: `verification.verify`

**URI**: `verify-email/{id}/{hash}`

**Methods**: GET

**Parameters**:
- `id`
- `hash`

**Middleware**: web, auth, signed, throttle:6,1

**Method Parameters**:
- `request`: `Illuminate\Foundation\Auth\EmailVerificationRequest`
- `user`: `App\Models\User`


