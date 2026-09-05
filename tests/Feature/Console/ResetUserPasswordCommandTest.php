<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

it('resets a user password by email', function (): void {
    $user = User::factory()->create([
        'email' => 'locked-out@example.com',
        'password' => Hash::make('old-password'),
    ]);

    $this->artisan('user:reset-password', [
        'email' => 'locked-out@example.com',
        'password' => 'emergency-password',
    ])
        ->assertSuccessful()
        ->expectsOutputToContain('Password updated for locked-out@example.com');

    expect(Hash::check('emergency-password', $user->refresh()->password))->toBeTrue();
});

it('fails when the user email does not exist', function (): void {
    $this->artisan('user:reset-password', [
        'email' => 'missing@example.com',
        'password' => 'emergency-password',
    ])
        ->assertFailed()
        ->expectsOutputToContain('User not found: missing@example.com');
});

it('logs an emergency password reset', function (): void {
    Log::spy();

    $user = User::factory()->create([
        'email' => 'audit@example.com',
    ]);

    $this->artisan('user:reset-password', [
        'email' => 'audit@example.com',
        'password' => 'emergency-password',
    ])->assertSuccessful();

    Log::shouldHaveReceived('warning')
        ->once()
        ->with('Emergency password reset via artisan', [
            'user_id' => $user->id,
            'email' => 'audit@example.com',
            'command' => 'user:reset-password',
        ]);
});
