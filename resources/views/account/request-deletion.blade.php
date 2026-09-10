<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="index,follow">
        <title>Request Account Deletion - {{ config('app.name') }}</title>
        <link rel="icon" href="/logo.png" type="image/png">
        <link rel="apple-touch-icon" href="/logo.png">
        <style>
            :root {
                color-scheme: light;
            }

            body {
                margin: 0;
                font-family: "Instrument Sans", "Segoe UI", Arial, sans-serif;
                background: #f5f7fb;
                color: #0f172a;
            }

            .container {
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 24px 16px;
            }

            .card {
                width: 100%;
                max-width: 560px;
                background: #ffffff;
                border: 1px solid #dbe3ee;
                border-radius: 16px;
                padding: 24px;
                box-shadow: 0 8px 28px rgba(15, 23, 42, 0.08);
            }

            h1 {
                margin: 0 0 12px;
                font-size: 1.5rem;
                line-height: 1.3;
            }

            p {
                margin: 0 0 12px;
                line-height: 1.6;
                color: #334155;
            }

            .warning {
                margin-top: 20px;
                margin-bottom: 20px;
                background: #fff7ed;
                border: 1px solid #fdba74;
                color: #9a3412;
                border-radius: 12px;
                padding: 12px 14px;
                font-size: 0.95rem;
                line-height: 1.5;
            }

            label {
                display: block;
                margin-bottom: 8px;
                font-weight: 600;
                color: #0f172a;
            }

            input[type="email"] {
                width: 100%;
                box-sizing: border-box;
                border: 1px solid #cbd5e1;
                border-radius: 10px;
                padding: 12px;
                font-size: 1rem;
                margin-bottom: 10px;
            }

            input[type="email"]:focus {
                outline: 2px solid #93c5fd;
                border-color: #60a5fa;
            }

            .error {
                margin: 0 0 12px;
                color: #b91c1c;
                font-size: 0.9rem;
            }

            button {
                width: 100%;
                border: 0;
                border-radius: 10px;
                padding: 12px 16px;
                background: #b91c1c;
                color: #ffffff;
                font-size: 1rem;
                font-weight: 600;
                cursor: pointer;
            }

            button:hover {
                background: #991b1b;
            }

            .muted {
                margin-top: 12px;
                font-size: 0.88rem;
                color: #64748b;
                text-align: center;
            }

            @media (max-width: 640px) {
                .card {
                    padding: 20px;
                }
            }
        </style>
    </head>
    <body>
        <main class="container">
            <section class="card" aria-labelledby="request-deletion-title">
                <h1 id="request-deletion-title">Request Account Deletion</h1>
                <p>
                    If you continue, we will process your account deletion request. Deleting your account removes your access and
                    permanently deletes associated account data based on our retention policy.
                </p>
                <p>
                    Please review this carefully before continuing. This page exists so you can request account deletion directly in
                    a web browser.
                </p>

                <div class="warning" role="alert">
                    Warning: This action is permanent and cannot be undone.
                </div>

                <form method="POST" action="{{ route('account.request-deletion.store') }}">
                    @csrf
                    <label for="email">Email address linked to your account</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        placeholder="you@example.com"
                        required
                    >
                    @error('email')
                        <p class="error">{{ $message }}</p>
                    @enderror
                    <button type="submit">Yes, Request Account Deletion</button>
                </form>

                <p class="muted">Need help instead? Contact support before submitting this request.</p>
            </section>
        </main>
    </body>
</html>
