<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Maintenance') }} — {{ config('app.name') }}</title>
    <style>
        body { font-family: system-ui, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 1rem; background: #f8fafc; color: #1e293b; }
        .card { max-width: 28rem; text-align: center; }
        h1 { font-size: 1.5rem; margin-bottom: 0.5rem; }
        p { color: #64748b; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="card">
        <h1>{{ __('We'll be back soon') }}</h1>
        <p>{{ __('Sorry for the inconvenience. We're performing some maintenance. Please check back in a few minutes.') }}</p>
    </div>
</body>
</html>
