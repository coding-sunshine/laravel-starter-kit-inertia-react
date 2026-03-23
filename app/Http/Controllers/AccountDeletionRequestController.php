<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final readonly class AccountDeletionRequestController
{
    public function show(): View
    {
        return view('account.request-deletion');
    }

    public function store(Request $request): View
    {
        /** @var array{email:string} $validated */
        $validated = $request->validate([
            'email' => ['required',  'exists:users,email'],
        ]);

        return view('account.request-deletion-confirmed', [
            'email' => $validated['email'],
        ]);
    }
}
