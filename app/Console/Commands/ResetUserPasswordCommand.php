<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use SensitiveParameter;

/**
 * Developer-only emergency password reset. Bypasses the web UI and all
 * password validation rules. Do not use for normal user management.
 */
final class ResetUserPasswordCommand extends Command
{
    protected $signature = 'user:reset-password
                            {email : The user email address}
                            {password : The new plain-text password}';

    protected $description = 'Developer emergency password reset (no validation; not for normal use)';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $password = (string) $this->argument('password');

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->error("User not found: {$email}");

            return self::FAILURE;
        }

        $this->resetPasswordForUser($user, $password);

        Log::warning('Emergency password reset via artisan', [
            'user_id' => $user->id,
            'email' => $user->email,
            'command' => 'user:reset-password',
        ]);

        $this->info("Password updated for {$user->email} (ID: {$user->id}).");

        return self::SUCCESS;
    }

    private function resetPasswordForUser(User $user, #[SensitiveParameter] string $password): void
    {
        $user->update([
            'password' => $password,
        ]);
    }
}
