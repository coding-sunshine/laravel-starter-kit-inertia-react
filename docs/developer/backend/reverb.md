# Laravel Reverb (WebSockets)

Real-time broadcasting is provided by **laravel/reverb**. The app uses the Pusher protocol; the frontend can listen with **Laravel Echo** and **pusher-js**.

## Configuration

- **Config**: `config/reverb.php` — server host/port, apps (key/secret/app_id), scaling (Redis).
- **Broadcasting**: `config/broadcasting.php` — default connection and `reverb` connection (key, secret, app_id, host, port, scheme).
- **Channels**: `routes/channels.php` — e.g. private channel `App.Models.User.{id}` for user-specific events.

## Environment

In `.env` / `.env.example`:

- `BROADCAST_CONNECTION=log` by default; set to `reverb` when using Reverb.
- Reverb server: `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET`, `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME`.
- Frontend (Vite): `VITE_REVERB_APP_KEY`, `VITE_REVERB_HOST`, `VITE_REVERB_PORT`, `VITE_REVERB_SCHEME` (so Echo can connect).

## Running Reverb

- **Development**: `php artisan reverb:start` — starts the WebSocket server (default port 8080).
- **Production**: Run Reverb as a long-running process (e.g. Supervisor). Use `reverb:restart` to gracefully restart.

## Frontend (Echo)

- **Packages**: `laravel-echo`, `pusher-js` (see `package.json`).
- **Bootstrap**: `resources/js/echo.ts` initializes `window.Echo` when `VITE_REVERB_APP_KEY` is set. Import it in `resources/js/app.tsx` to enable real-time: `import './echo';`
- **Usage**: In components, use `window.Echo.private('App.Models.User.' + userId).listen(...)` for user-specific events. Ensure the backend broadcasts events that implement `ShouldBroadcast` and use the same channel names.

## References

- [Laravel Reverb](https://laravel.com/docs/reverb)
- [Laravel Broadcasting](https://laravel.com/docs/broadcasting)
