<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AiCreditPool;
use App\Models\AiCreditUsage;
use App\Models\AiUserByok;
use App\Models\AiUserCreditLimit;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;

final class AiCreditService
{
    /**
     * Check if a user can perform an AI action.
     * Returns true if BYOK is active (bypasses credit check) or credits are sufficient.
     */
    public function canUse(User $user, string $action): bool
    {
        if ($this->hasByok($user)) {
            return true;
        }

        /** @var Organization|null $org */
        $org = $user->organization;
        if ($org === null) {
            return false;
        }

        $cost = $this->costFor($action);
        $pool = $this->currentPool($org);
        if ($pool === null || ($pool->credits_used + $cost) > $pool->credits_total) {
            return false;
        }

        // Check per-user limit if set
        $userLimit = $this->currentUserLimit($user);
        if ($userLimit !== null && ($userLimit->credits_used + $cost) > $userLimit->credits_limit) {
            return false;
        }

        return true;
    }

    /**
     * Deduct credits after an AI action is performed.
     */
    public function deduct(User $user, string $action, ?string $model = null): void
    {
        $cost = $this->costFor($action);
        $byok = $this->hasByok($user);

        /** @var Organization|null $org */
        $org = $user->organization;

        AiCreditUsage::create([
            'organization_id' => $org?->id ?? 0,
            'user_id' => $user->id,
            'action' => $action,
            'credits' => $cost,
            'model' => $model,
            'byok' => $byok,
            'created_at' => Carbon::now(),
        ]);

        if ($byok || $org === null) {
            return;
        }

        $pool = $this->currentPool($org);
        if ($pool !== null) {
            $pool->increment('credits_used', $cost);
        }

        $userLimit = $this->currentUserLimit($user);
        if ($userLimit !== null) {
            $userLimit->increment('credits_used', $cost);
        }
    }

    /**
     * Get the credit balance for an organization.
     *
     * @return array{total: int, used: int, remaining: int}
     */
    public function getBalance(Organization $org): array
    {
        $pool = $this->currentPool($org);

        return [
            'total' => $pool?->credits_total ?? 0,
            'used' => $pool?->credits_used ?? 0,
            'remaining' => $pool?->remaining() ?? 0,
        ];
    }

    /**
     * Get the credit balance for a specific user (respects per-user limit).
     *
     * @return array{total: int, used: int, remaining: int, byok: bool}
     */
    public function getUserBalance(User $user): array
    {
        $byok = $this->hasByok($user);

        if ($byok) {
            return ['total' => -1, 'used' => 0, 'remaining' => -1, 'byok' => true];
        }

        $userLimit = $this->currentUserLimit($user);
        if ($userLimit !== null) {
            return [
                'total' => $userLimit->credits_limit,
                'used' => $userLimit->credits_used,
                'remaining' => max(0, $userLimit->credits_limit - $userLimit->credits_used),
                'byok' => false,
            ];
        }

        /** @var Organization|null $org */
        $org = $user->organization;
        if ($org === null) {
            return ['total' => 0, 'used' => 0, 'remaining' => 0, 'byok' => false];
        }

        return array_merge($this->getBalance($org), ['byok' => false]);
    }

    /**
     * Reset the credit pool for an organization (start a new period).
     */
    public function resetPeriod(Organization $org): void
    {
        $creditsTotal = (int) ($org->plan->ai_credits_per_period ?? config('ai-credits.default_credits_per_period', 100));
        $now = Carbon::now();

        AiCreditPool::updateOrCreate(
            ['organization_id' => $org->id, 'period_start' => $now->copy()->startOfMonth()->toDateString()],
            [
                'credits_total' => $creditsTotal,
                'credits_used' => 0,
                'period_start' => $now->copy()->startOfMonth()->toDateString(),
                'period_end' => $now->copy()->endOfMonth()->toDateString(),
            ]
        );

        // Reset per-user limits for the new period
        AiUserCreditLimit::where('organization_id', $org->id)
            ->where('period_start', '<', $now->copy()->startOfMonth()->toDateString())
            ->update(['credits_used' => 0]);
    }

    /**
     * Add one-time credits to an organization's current pool.
     */
    public function addCredits(Organization $org, int $amount): void
    {
        $pool = $this->currentPool($org);
        if ($pool !== null) {
            $pool->increment('credits_total', $amount);
        }
    }

    /**
     * Get the decrypted BYOK API key for a user, or null.
     */
    public function getByokKey(User $user): ?string
    {
        $byok = AiUserByok::where('user_id', $user->id)->where('is_active', true)->first();
        if ($byok === null) {
            return null;
        }

        return Crypt::decryptString($byok->api_key_enc);
    }

    /**
     * Store a BYOK API key for a user (encrypted).
     */
    public function setByokKey(User $user, string $provider, string $key, ?string $model): void
    {
        AiUserByok::updateOrCreate(
            ['user_id' => $user->id],
            [
                'provider' => $provider,
                'api_key_enc' => Crypt::encryptString($key),
                'model_override' => $model,
                'is_active' => true,
            ]
        );
    }

    private function hasByok(User $user): bool
    {
        return AiUserByok::where('user_id', $user->id)->where('is_active', true)->exists();
    }

    private function costFor(string $action): int
    {
        $costs = config('ai-credits.costs', []);

        return (int) ($costs[$action] ?? 1);
    }

    private function currentPool(Organization $org): ?AiCreditPool
    {
        $now = Carbon::now();

        return AiCreditPool::where('organization_id', $org->id)
            ->where('period_start', '<=', $now->toDateString())
            ->where('period_end', '>=', $now->toDateString())
            ->first();
    }

    private function currentUserLimit(User $user): ?AiUserCreditLimit
    {
        $now = Carbon::now();

        return AiUserCreditLimit::where('user_id', $user->id)
            ->where('period_start', '<=', $now->toDateString())
            ->first();
    }
}
