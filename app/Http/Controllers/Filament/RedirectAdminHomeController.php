<?php

declare(strict_types=1);

namespace App\Http\Controllers\Filament;

use App\Filament\Resources\Users\UserResource;
use Illuminate\Http\RedirectResponse;

/**
 * Swapped in for Filament's RedirectToHomeController so GET /admin lands on Users.
 */
final class RedirectAdminHomeController
{
    public function __invoke(): RedirectResponse
    {
        return redirect(UserResource::getUrl('index', panel: 'admin'));
    }
}
