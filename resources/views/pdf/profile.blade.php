<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile export</title>
    <style>
        body { font-family: system-ui, sans-serif; padding: 2rem; color: #1f2937; }
        h1 { font-size: 1.5rem; margin-bottom: 1rem; }
        dl { margin: 0; }
        dt { font-weight: 600; margin-top: 0.75rem; }
        dd { margin-left: 0; margin-top: 0.25rem; }
        .meta { margin-top: 2rem; font-size: 0.875rem; color: #6b7280; }
    </style>
</head>
<body>
    <h1>Profile export</h1>
    <dl>
        <dt>Name</dt>
        <dd>{{ $user->name }}</dd>
        <dt>Email</dt>
        <dd>{{ $user->email }}</dd>
        @if($user->email_verified_at)
            <dt>Email verified</dt>
            <dd>{{ $user->email_verified_at->format('F j, Y') }}</dd>
        @endif
    </dl>
    <p class="meta">Generated on {{ now()->format('F j, Y \a\t g:i A') }}.</p>
</body>
</html>
