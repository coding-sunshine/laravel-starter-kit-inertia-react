# Rake Load Broadcasting Setup

This document explains how to set up and test live broadcasting for the rake loading system.

## Overview

The rake loading system now supports real-time updates using Laravel Reverb broadcasting. When any user performs an action (placement, wagon loading, guard inspection, weighment, dispatch), all other users viewing the same rake will see updates in real-time without needing to refresh the page.

## Setup Instructions

### 1. Configure Environment Variables

Add the following to your `.env` file:

```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=your_app_id
REVERB_APP_KEY=your_app_key
REVERB_APP_SECRET=your_app_secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### 2. Start Reverb Server

```bash
php artisan reverb
```

### 3. Start Queue Worker

```bash
php artisan queue:work
```

### 4. Start Vite Dev Server

```bash
npm run dev
```

## Testing

### Method 1: Using the Test Command

1. Find a rake ID: `php artisan tinker --execute="echo App\Models\Rake::first()->id"`
2. Run the test command: `php artisan rake:test-broadcast {rakeId}`
3. Open the rake loading page in your browser
4. Check the browser console for the broadcast event

### Method 2: Manual Testing

1. Open the same rake loading page in two different browser windows
2. Perform an action in one window (e.g., load a wagon)
3. The other window should update automatically within seconds

## Broadcast Events

The system broadcasts the following events:

### `load.updated`
- **Triggered**: When the overall load state changes
- **Data**: Load state, rake load details, trigger type
- **Actions**: Placement confirmed, dispatch confirmed, state transitions

### `wagon-loading.updated`
- **Triggered**: When a wagon is loaded
- **Data**: Wagon loading details, loader info, quantities
- **Actions**: Individual wagon loading operations

### `guard-inspection.updated`
- **Triggered**: When guard inspection is recorded
- **Data**: Inspection details, approval status, remarks
- **Actions**: Guard inspection recording

### `weighment.updated`
- **Triggered**: When weighment is recorded
- **Data**: Weighment details, wagon weights, speed, status
- **Actions**: Weighment recording (passed/failed)

## Channel Authorization

Users can only listen to broadcasts for rakes they have permission to view. The channel authorization is defined in `routes/channels.php`:

```php
Broadcast::channel('rake-load.{rakeId}', function ($user, int $rakeId): bool {
    $rake = Rake::query()->find($rakeId);
    return $rake && $user->can('view', $rake);
});
```

## Frontend Implementation

The frontend uses a custom React hook `useRakeLoadBroadcasting` that:

1. Subscribes to the private channel for the specific rake
2. Listens for all broadcast events
3. Updates the UI in real-time
4. Handles cleanup when component unmounts

## Troubleshooting

### Events Not Received

1. **Check Reverb is running**: `php artisan reverb`
2. **Check queue worker**: `php artisan queue:work`
3. **Check browser console** for WebSocket connection errors
4. **Verify .env configuration** matches Reverb setup

### Permission Errors

1. **Check user permissions** for viewing the rake
2. **Verify channel authorization** in `routes/channels.php`
3. **Check authentication** status in browser

### Performance Issues

1. **Use Redis queue** for better performance in production
2. **Configure proper queue priorities** for broadcast events
3. **Monitor WebSocket connections** in Reverb dashboard

## Production Considerations

1. **Use HTTPS** for Reverb connections in production
2. **Configure proper scaling** for Reverb servers
3. **Set up monitoring** for broadcast events
4. **Use Redis** for queue and caching in production
5. **Configure proper CORS** policies if needed

## Security Notes

1. **Private channels** ensure only authorized users receive updates
2. **Event data** is filtered to include only necessary information
3. **Channel authorization** checks user permissions for each rake
4. **No sensitive data** is broadcasted (passwords, tokens, etc.)
