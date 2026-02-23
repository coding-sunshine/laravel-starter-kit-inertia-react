<?php

declare(strict_types=1);

use App\Models\VehicleUnload;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', fn ($user, $id): bool => (int) $user->id === (int) $id);

Broadcast::channel('unload.{unloadId}', function ($user, int $unloadId): bool {
    $unload = VehicleUnload::query()->find($unloadId);

    return $unload && $user->can('view', $unload);
});
