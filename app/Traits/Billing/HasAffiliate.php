<?php

declare(strict_types=1);

namespace App\Traits\Billing;

use App\Models\Billing\Affiliate;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait HasAffiliate
{
    public function affiliate(): HasOne
    {
        return $this->hasOne(Affiliate::class);
    }

    public function hasAffiliate(): bool
    {
        return $this->affiliate()->exists();
    }

    public function createAffiliate(array $attributes = []): Affiliate
    {
        return Affiliate::query()->create(array_merge(
            ['user_id' => $this->id],
            $attributes
        ));
    }
}
