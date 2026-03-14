<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\PushHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

final readonly class PushListingAction
{
    public function handle(Model $listing, string $channel, ?User $user = null): void
    {
        $pushHistory = PushHistory::query()->create([
            'pushable_type' => $listing::class,
            'pushable_id' => $listing->getKey(),
            'channel' => $channel,
            'pushed_at' => now(),
            'user_id' => $user?->id,
            'response' => null,
            'status' => 'success',
        ]);

        Log::info('Listing pushed to channel', [
            'pushable_type' => $listing::class,
            'pushable_id' => $listing->getKey(),
            'channel' => $channel,
            'push_history_id' => $pushHistory->id,
        ]);
    }
}
