<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class OrganizationMemberRemoved
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Organization $organization,
        public User $user,
        public ?string $previousRole = null,
        public ?User $removedBy = null
    ) {}
}
