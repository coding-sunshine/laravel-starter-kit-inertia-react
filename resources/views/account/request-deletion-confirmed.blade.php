<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="index,follow">
        <title>Account Deletion Request Received - {{ config('app.name') }}</title>
        <link rel="icon" href="/logo.png" type="image/png">
        <link rel="apple-touch-icon" href="/logo.png">
        <style>
            body {
                margin: 0;
                font-family: "Instrument Sans", "Segoe UI", Arial, sans-serif;
                background: #f8fafc;
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
                box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
            }

            h1 {
                margin: 0 0 12px;
                font-size: 1.5rem;
            }

            p {
                margin: 0 0 12px;
                line-height: 1.6;
                color: #334155;
            }
        </style>
    </head>
    <body>
        <main class="container">
            <section class="card" aria-labelledby="request-confirmed-title">
                <h1 id="request-confirmed-title">Request Submitted</h1>
                <p>Your delete request has been sent to admin for <strong>{{ $email }}</strong>.</p>
                <p>Your account will be deleted in a few days after verification.</p>
            </section>
        </main>
    </body>
</html>
