<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\DeveloperFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * @property int $id
 * @property int|null $organization_id
 * @property int|null $user_id
 * @property string $name
 * @property string|null $slug
 * @property string|null $website
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $description
 * @property int|null $legacy_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class Developer extends Model
{
    /** @use HasFactory<DeveloperFactory> */
    use BelongsToOrganization;

    use HasFactory;
    use HasSlug;
    use LogsActivity;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'website',
        'phone',
        'email',
        'description',
        'legacy_id',
        'user_id',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logAll()
            ->logExcept([]);
    }
}
