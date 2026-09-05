<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

final class Siding extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use SoftDeletes, Userstamps;

    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'location',
        'station_code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Resolve a route-bound siding from id, code (e.g. "PKUR"), or case-insensitive name.
     * Lets links like /sidings/pakur/quick-placement work without forcing the numeric id.
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        if ($field !== null) {
            return parent::resolveRouteBinding($value, $field);
        }

        if (is_numeric($value)) {
            return $this->newQuery()->whereKey((int) $value)->first();
        }

        $needle = mb_strtolower((string) $value);

        return $this->newQuery()
            ->where(function ($q) use ($needle): void {
                $q->whereRaw('LOWER(code) = ?', [$needle])
                    ->orWhereRaw('LOWER(name) = ?', [$needle])
                    ->orWhereRaw('LOWER(name) LIKE ?', [$needle.' %'])
                    ->orWhereRaw('LOWER(name) LIKE ?', [$needle.'%']);
            })
            ->first();
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function routes(): HasMany
    {
        return $this->hasMany(Route::class);
    }

    public function loaders(): HasMany
    {
        return $this->hasMany(Loader::class);
    }

    public function rakes(): HasMany
    {
        return $this->hasMany(Rake::class);
    }

    public function indents(): HasMany
    {
        return $this->hasMany(Indent::class);
    }

    public function vehicleUnloads(): HasMany
    {
        return $this->hasMany(VehicleUnload::class);
    }

    public function coalStock(): HasMany
    {
        return $this->hasMany(CoalStock::class);
    }

    /**
     * @return HasMany<SidingShift, $this>
     */
    public function shifts(): HasMany
    {
        return $this->hasMany(SidingShift::class)->orderBy('sort_order');
    }

    /**
     * @return HasOne<SidingOpeningBalance, $this>
     */
    public function openingBalance(): HasOne
    {
        return $this->hasOne(SidingOpeningBalance::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_siding', 'siding_id', 'user_id')
            ->withPivot('is_primary')
            ->withTimestamps();
    }
}
