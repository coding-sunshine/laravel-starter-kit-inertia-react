<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\LoginEvent;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;

final class LoginEventListener
{
    public function __construct(private readonly Request $request) {}

    public function handle(Login $event): void
    {
        LoginEvent::query()->create([
            'user_id' => $event->user->getAuthIdentifier(),
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
        ]);
    }
}
