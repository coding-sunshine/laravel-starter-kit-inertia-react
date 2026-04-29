<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\LoadriteTokenManager;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Throwable;

final class LoadriteStoreToken extends Command
{
    protected $signature = 'loadrite:store-token
                            {--siding= : Siding ID (leave blank for global token)}';

    protected $description = 'Interactively store an encrypted Loadrite API token.';

    public function handle(LoadriteTokenManager $manager): int
    {
        $sidingId = $this->option('siding') ? (int) $this->option('siding') : null;

        $accessToken = $this->secret('Paste the Loadrite access token:');
        $refreshToken = $this->secret('Paste the Loadrite refresh token:');

        if (! $accessToken || ! $refreshToken) {
            $this->error('Access token and refresh token are required.');

            return Command::FAILURE;
        }

        $expiresRaw = $this->ask('Token expiry datetime (ISO8601):');

        try {
            $expiresAt = Carbon::parse($expiresRaw);
        } catch (Throwable) {
            $this->error("Invalid datetime: {$expiresRaw}");

            return Command::FAILURE;
        }

        $manager->store($sidingId, $accessToken, $refreshToken, $expiresAt);

        $this->info('Token stored and encrypted successfully.');

        return Command::SUCCESS;
    }
}
