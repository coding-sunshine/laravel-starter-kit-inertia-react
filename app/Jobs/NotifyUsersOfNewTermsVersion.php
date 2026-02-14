<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\TermsVersion;
use App\Models\User;
use App\Notifications\NewTermsVersionPublished;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

final class NotifyUsersOfNewTermsVersion implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly TermsVersion $termsVersion
    ) {}

    public function handle(): void
    {
        $users = User::query()->whereNotNull('email_verified_at')->get();
        Notification::send($users, new NewTermsVersionPublished($this->termsVersion));
    }
}
