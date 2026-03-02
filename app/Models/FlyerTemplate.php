<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class FlyerTemplate extends Model
{
    use BelongsToOrganization;
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'legacy_flyer_template_id',
        'template_id',
        'name',
        'preview_img',
        'is_enabled',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    /**
     * @return HasMany<Flyer, $this>
     */
    public function flyers(): HasMany
    {
        return $this->hasMany(Flyer::class, 'template_id');
    }
}
