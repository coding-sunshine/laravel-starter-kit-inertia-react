# CreateUser

## Purpose

Creates a new user, stores the hashed password, and dispatches `Registered`.

## Location

`app/Actions/CreateUser.php`

## Method Signature

```php
public function handle(array $attributes, #[\SensitiveParameter] string $password): User
```

## Dependencies

None.

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$attributes` | `array<string, mixed>` | User fields (e.g. `name`, `email`); password must not be included |
| `$password` | `string` | Plain-text password (hashed internally) |

## Return Value

The created `User` model instance.

## Usage Examples

### From Controller

```php
$user = app(CreateUser::class)->handle($request->safe()->except('password'), $request->string('password')->value());
```

### From Job/Command

```php
(new CreateUser())->handle(['name' => 'Jane', 'email' => 'jane@example.com'], 'secret');
```

## Related Components

- **Controllers**: `UserController` (register flow)
- **Routes**: `register` (GET), `register.store` (POST)
- **Model**: `User`

## Notes

- Fires `Illuminate\Auth\Events\Registered` after create. Listeners (e.g. email verification) can react.
- Used by `UserController::store` during registration.
