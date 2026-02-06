# Spatie Laravel Media Library

The app uses [Spatie Laravel Media Library](https://spatie.be/docs/laravel-medialibrary/v11/introduction) to associate files with Eloquent models. User avatars are the primary use case.

## User avatar

- **Model**: `User` implements `HasMedia` and `InteractsWithMedia`.
- **Collection**: `avatar` (single file). Uploaded via profile settings; replaced on each new upload.
- **Conversions**:
  - `thumb` (48×48, crop) – used in nav, header, and shared `auth.user.avatar`.
  - `profile` (192×192, crop) – used on the profile/settings page preview.
- **Access**: `$user->avatar` and `$user->avatar_profile` are appended attributes returning the thumb and profile conversion URLs, or `null` when no avatar is set. The shared Inertia `auth.user` includes these for the frontend.
- **Profile update**: `UpdateUserRequest` accepts an optional `avatar` image (max 2 MB). `UpdateUser` strips `avatar` from attributes and, when the request has a file, clears the `avatar` collection and adds the new file via `addMediaFromRequest('avatar')->toMediaCollection('avatar')`.

## Filament

Avatar is shown in the Users table and user infolist via `ImageColumn` and `ImageEntry` using the `avatar` attribute; when missing, a fallback (e.g. initials or external placeholder) can be used. The [Filament Spatie Media Library plugin](https://filamentphp.com/plugins/filament-spatie-media-library) is not yet compatible with Filament v5; when it is, it can be used to manage avatar (and other media) in the admin panel.

## Storage and config

Media use Laravel’s default filesystem (see `config/media-library.php` and `config/filesystems.php`). Ensure `php artisan storage:link` is run so public conversions are reachable. Migrations for the `media` table are in `database/migrations/`.
