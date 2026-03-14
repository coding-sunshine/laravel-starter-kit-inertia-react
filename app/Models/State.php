<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\StateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string|null $abbreviation
 * @property string $country
 * @property int|null $legacy_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class State extends Model
{
    /** @use HasFactory<StateFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'abbreviation',
        'country',
        'legacy_id',
    ];

    public function suburbs(): HasMany
    {
        return $this->hasMany(Suburb::class);
    }
}
