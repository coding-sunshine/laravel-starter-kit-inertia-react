<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PotentialProperty extends Model
{
    use BelongsToOrganization;
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'projecttype_id',
        'title',
        'developer',
    ];

    /**
     * @return BelongsTo<Projecttype, $this>
     */
    public function projecttype(): BelongsTo
    {
        return $this->belongsTo(Projecttype::class);
    }
}
