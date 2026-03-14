<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\FlyerTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $organization_id
 * @property string $name
 * @property string|null $html_content
 * @property string|null $css_content
 * @property bool $is_active
 * @property int|null $legacy_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class FlyerTemplate extends Model
{
    /** @use HasFactory<FlyerTemplateFactory> */
    use BelongsToOrganization;

    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'html_content',
        'css_content',
        'is_active',
        'legacy_id',
    ];

    public function flyers(): HasMany
    {
        return $this->hasMany(Flyer::class);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
