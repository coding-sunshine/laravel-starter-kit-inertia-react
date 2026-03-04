<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

/**
 * @property int $id
 * @property int $organization_id
 * @property int|null $vehicle_id
 * @property string $recall_reference
 * @property string|null $make
 * @property string|null $model
 * @property string|null $title
 * @property string|null $description
 * @property \Carbon\Carbon|null $issued_date
 * @property \Carbon\Carbon|null $due_date
 * @property string $status
 * @property \Carbon\Carbon|null $completed_at
 * @property string|null $completion_notes
 */
final class VehicleRecall extends Model
{
    use BelongsToOrganization;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use SoftDeletes;
    use Userstamps;

    protected $table = 'vehicle_recalls';

    protected $fillable = [
        'vehicle_id',
        'recall_reference',
        'make',
        'model',
        'title',
        'description',
        'issued_date',
        'due_date',
        'status',
        'completed_at',
        'completion_notes',
    ];

    protected $casts = [
        'issued_date' => 'date',
        'due_date' => 'date',
        'completed_at' => 'date',
    ];

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
