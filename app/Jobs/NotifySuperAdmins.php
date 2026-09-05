<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

final class NotifySuperAdmins implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly string $notificationClass,
        private readonly array $payload,
    ) {}

    public function handle(): void
    {
        $superAdmins = User::query()
            ->select(['id'])
            ->get()
            ->filter(fn (User $u): bool => $u->isSuperAdmin())
            ->values();

        if ($superAdmins->isEmpty()) {
            return;
        }

        foreach ($superAdmins as $user) {
            try {
                $user->notify(new ($this->notificationClass)($this->payload));
            } catch (Throwable $e) {
                Log::warning('Superadmin notification failed', [
                    'user_id' => $user->id,
                    'notification' => $this->notificationClass,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
