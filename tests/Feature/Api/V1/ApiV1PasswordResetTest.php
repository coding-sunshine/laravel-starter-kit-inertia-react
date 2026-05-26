<?php

declare(strict_types=1);

use App\Enums\PasswordResetOtpStatus;
use App\Models\PasswordResetOtp;
use App\Models\User;
use App\Notifications\PasswordResetOtpNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\postJson;

function extractOtpFromNotification(PasswordResetOtpNotification $notification): string
{
    $property = new ReflectionProperty(PasswordResetOtpNotification::class, 'otp');

    return $property->getValue($notification);
}

function createPendingPasswordResetOtp(User $user, string $otp = '123456'): PasswordResetOtp
{
    return PasswordResetOtp::query()->create([
        'email' => PasswordResetOtp::normalizeEmail($user->email),
        'otp_hash' => Hash::make($otp),
        'expires_at' => now()->addMinutes(10),
        'attempts' => 0,
        'status' => PasswordResetOtpStatus::Pending,
    ]);
}

test('forgot password sends otp for existing user with generic message', function (): void {
    Notification::fake();

    $user = User::factory()->withoutTwoFactor()->create([
        'email' => 'reset-user@example.com',
    ]);

    $response = postJson('/api/v1/auth/forgot-password', [
        'email' => $user->email,
    ]);

    $response->assertOk()
        ->assertJson([
            'message' => 'If the account exists, an OTP was sent.',
        ]);

    Notification::assertSentTo($user, PasswordResetOtpNotification::class);

    expect(PasswordResetOtp::query()->where('email', $user->email)->count())->toBe(1);
});

test('forgot password returns generic message for unknown email', function (): void {
    Notification::fake();

    $response = postJson('/api/v1/auth/forgot-password', [
        'email' => 'unknown@example.com',
    ]);

    $response->assertOk()
        ->assertJson([
            'message' => 'If the account exists, an OTP was sent.',
        ]);

    Notification::assertNothingSent();
    expect(PasswordResetOtp::query()->count())->toBe(0);
});

test('verify otp returns reset token for valid code', function (): void {
    $user = User::factory()->withoutTwoFactor()->create([
        'email' => 'verify-otp@example.com',
    ]);

    createPendingPasswordResetOtp($user);

    $response = postJson('/api/v1/auth/verify-reset-otp', [
        'email' => $user->email,
        'otp' => '123456',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['data' => ['reset_token'], 'message']);

    expect(strlen((string) $response->json('data.reset_token')))->toBe(64);

    $record = PasswordResetOtp::query()->where('email', $user->email)->first();
    expect($record?->status)->toBe(PasswordResetOtpStatus::Verified)
        ->and($record?->verified_at)->not->toBeNull();
});

test('verify otp rejects invalid code and increments attempts', function (): void {
    $user = User::factory()->withoutTwoFactor()->create([
        'email' => 'invalid-otp@example.com',
    ]);

    createPendingPasswordResetOtp($user);

    $response = postJson('/api/v1/auth/verify-reset-otp', [
        'email' => $user->email,
        'otp' => '999999',
    ]);

    $response->assertUnprocessable()
        ->assertJsonPath('error.code', 'otp_invalid');

    expect(PasswordResetOtp::query()->where('email', $user->email)->value('attempts'))->toBe(1);
});

test('verify otp locks after max attempts', function (): void {
    $user = User::factory()->withoutTwoFactor()->create([
        'email' => 'locked-otp@example.com',
    ]);

    $record = createPendingPasswordResetOtp($user);
    $record->update(['attempts' => 4]);

    $response = postJson('/api/v1/auth/verify-reset-otp', [
        'email' => $user->email,
        'otp' => '999999',
    ]);

    $response->assertUnprocessable()
        ->assertJsonPath('error.code', 'otp_locked');

    expect($record->fresh()?->status)->toBe(PasswordResetOtpStatus::Locked);
});

test('verify otp rejects expired code', function (): void {
    $user = User::factory()->withoutTwoFactor()->create([
        'email' => 'expired-otp@example.com',
    ]);

    PasswordResetOtp::query()->create([
        'email' => PasswordResetOtp::normalizeEmail($user->email),
        'otp_hash' => Hash::make('123456'),
        'expires_at' => now()->subMinute(),
        'attempts' => 0,
        'status' => PasswordResetOtpStatus::Pending,
    ]);

    $response = postJson('/api/v1/auth/verify-reset-otp', [
        'email' => $user->email,
        'otp' => '123456',
    ]);

    $response->assertUnprocessable()
        ->assertJsonPath('error.code', 'otp_expired');
});

test('reset password updates password and allows login with new credentials', function (): void {
    $user = User::factory()->withoutTwoFactor()->create([
        'email' => 'reset-flow@example.com',
        'password' => 'OldPassword1!',
    ]);

    createPendingPasswordResetOtp($user);

    $verifyResponse = postJson('/api/v1/auth/verify-reset-otp', [
        'email' => $user->email,
        'otp' => '123456',
    ]);

    $resetToken = (string) $verifyResponse->json('data.reset_token');

    $resetResponse = postJson('/api/v1/auth/reset-password', [
        'email' => $user->email,
        'reset_token' => $resetToken,
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ]);

    $resetResponse->assertOk()
        ->assertJson(['message' => 'Password reset successful.']);

    $oldLogin = postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'OldPassword1!',
    ]);
    $oldLogin->assertUnprocessable();

    $newLogin = postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'NewPassword1!',
    ]);
    $newLogin->assertOk()
        ->assertJsonStructure(['data' => ['access_token', 'refresh_token']]);
});

test('reset token is single use', function (): void {
    $user = User::factory()->withoutTwoFactor()->create([
        'email' => 'single-use@example.com',
    ]);

    createPendingPasswordResetOtp($user);

    $verifyResponse = postJson('/api/v1/auth/verify-reset-otp', [
        'email' => $user->email,
        'otp' => '123456',
    ]);

    $resetToken = (string) $verifyResponse->json('data.reset_token');

    postJson('/api/v1/auth/reset-password', [
        'email' => $user->email,
        'reset_token' => $resetToken,
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ])->assertOk();

    $secondAttempt = postJson('/api/v1/auth/reset-password', [
        'email' => $user->email,
        'reset_token' => $resetToken,
        'password' => 'AnotherPassword1!',
        'password_confirmation' => 'AnotherPassword1!',
    ]);

    $secondAttempt->assertUnprocessable()
        ->assertJsonPath('error.code', 'reset_token_expired');
});

test('new forgot password request supersedes previous pending otp', function (): void {
    Notification::fake();

    $user = User::factory()->withoutTwoFactor()->create([
        'email' => 'supersede@example.com',
    ]);

    postJson('/api/v1/auth/forgot-password', ['email' => $user->email])->assertOk();

    $firstOtp = extractOtpFromNotification(
        Notification::sent($user, PasswordResetOtpNotification::class)[0],
    );

    $this->travel(61)->seconds();

    postJson('/api/v1/auth/forgot-password', ['email' => $user->email])->assertOk();

    $verifyFirst = postJson('/api/v1/auth/verify-reset-otp', [
        'email' => $user->email,
        'otp' => $firstOtp,
    ]);

    $verifyFirst->assertUnprocessable()
        ->assertJsonPath('error.code', 'otp_invalid');
});

test('reset password revokes mobile sanctum tokens', function (): void {
    $user = User::factory()->withoutTwoFactor()->create([
        'email' => 'revoke-tokens@example.com',
    ]);

    $user->createToken('mobile-access');
    $user->createToken('mobile-refresh', ['refresh-access-token']);

    expect($user->tokens()->count())->toBe(2);

    createPendingPasswordResetOtp($user);

    $verifyResponse = postJson('/api/v1/auth/verify-reset-otp', [
        'email' => $user->email,
        'otp' => '123456',
    ]);

    postJson('/api/v1/auth/reset-password', [
        'email' => $user->email,
        'reset_token' => (string) $verifyResponse->json('data.reset_token'),
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ])->assertOk();

    expect($user->tokens()->count())->toBe(0);
});

test('full otp flow captures notification otp end to end', function (): void {
    Notification::fake();

    $user = User::factory()->withoutTwoFactor()->create([
        'email' => 'full-flow@example.com',
        'password' => 'OldPassword1!',
    ]);

    postJson('/api/v1/auth/forgot-password', ['email' => $user->email])->assertOk();

    $otp = extractOtpFromNotification(
        Notification::sent($user, PasswordResetOtpNotification::class)[0],
    );

    $verifyResponse = postJson('/api/v1/auth/verify-reset-otp', [
        'email' => $user->email,
        'otp' => $otp,
    ])->assertOk();

    postJson('/api/v1/auth/reset-password', [
        'email' => $user->email,
        'reset_token' => (string) $verifyResponse->json('data.reset_token'),
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ])->assertOk();

    postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'NewPassword1!',
    ])->assertOk();
});
