<?php

declare(strict_types=1);

use App\Actions\DeleteUser;
use App\Models\User;

it('may delete a user', function (): void {
    $user = User::factory()->create();

    $action = resolve(DeleteUser::class);

    $action->handle($user);

    // User model uses SoftDeletes, so delete() soft-deletes; record still exists but is trashed
    $user->refresh();
    expect($user->trashed())->toBeTrue();
});
