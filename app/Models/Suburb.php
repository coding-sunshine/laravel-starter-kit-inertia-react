<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SuburbFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $name
 * @property string|null $postcode
 * @property int|null $state_id
 * @property float|null $lat
 * @property float|null $lng
 * @property int|null $legacy_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class Suburb extends Model
{
    /** @use HasFactory<SuburbFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'postcode',
        'state_id',
        'lat',
        'lng',
        'legacy_id',
    ];

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    protected function casts(): array
    {
        return [
            'lat' => 'decimal:8',
            'lng' => 'decimal:8',
        ];
    }
}
