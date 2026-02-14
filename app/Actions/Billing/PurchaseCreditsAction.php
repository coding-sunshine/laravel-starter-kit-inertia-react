<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Models\Billing\CreditPack;
use App\Models\Organization;

final readonly class PurchaseCreditsAction
{
    public function handle(Organization $organization, CreditPack $pack): void
    {
        $organization->purchaseCreditPack($pack);
    }
}
