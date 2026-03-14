<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $category_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $icon
 * @property bool $is_system
 * @property bool $is_active
 * @property int|null $created_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class AiBot extends Model
{
    use HasFactory;
    use LogsActivity;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'icon',
        'is_system',
        'is_active',
        'created_by',
    ];

    /**
     * @return BelongsTo<AiBotCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(AiBotCategory::class);
    }

    /**
     * @return HasMany<AiBotPrompt, $this>
     */
    public function prompts(): HasMany
    {
        return $this->hasMany(AiBotPrompt::class, 'bot_id');
    }

    /**
     * @return HasMany<AiBotRun, $this>
     */
    public function runs(): HasMany
    {
        return $this->hasMany(AiBotRun::class, 'bot_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logAll()
            ->logExcept(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'embedding', 'api_token']);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
