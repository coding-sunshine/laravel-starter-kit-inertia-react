<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\ProjecttypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $organization_id
 * @property string $name
 * @property int|null $legacy_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class Projecttype extends Model
{
    /** @use HasFactory<ProjecttypeFactory> */
    use BelongsToOrganization;

    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'legacy_id',
    ];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
