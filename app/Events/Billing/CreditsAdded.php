<?php

declare(strict_types=1);

namespace App\Events\Billing;

use App\Models\Billing\Credit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final readonly class CreditsAdded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public object $organization,
        public Credit $credit
    ) {}
}
