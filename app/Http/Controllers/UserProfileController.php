<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class UserProfileController
{
    public function edit(Request $request): Response
    {
        return Inertia::render('user-profile/edit', [
            'status' => $request->session()->get('status'),
        ]);
    }

    public function update(): never
    {
        abort(403, 'Profile updates are disabled.');
    }
}
