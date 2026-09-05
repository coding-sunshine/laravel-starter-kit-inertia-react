<?php

declare(strict_types=1);

namespace App\Http\Responses\Filament;

use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

final class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        $panel = Filament::getCurrentPanel();

        if ($panel && $panel->getId() === 'admin') {
            return redirect()->intended('/admin/users');
        }

        return redirect()->intended(Filament::getUrl());
    }
}
