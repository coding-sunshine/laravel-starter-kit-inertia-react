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
 * @property int $driver_id
 * @property string $qualification_type
 * @property string $qualification_name
 * @property string|null $issuing_authority
 * @property string|null $qualification_number
 * @property \Carbon\Carbon|null $issue_date
 * @property \Carbon\Carbon|null $expiry_date
 * @property string $status
 * @property string|null $grade_achieved
 * @property int|null $score_achieved
 * @property string|null $certificate_file_path
 * @property bool $verification_required
 * @property int|null $verified_by
 * @property \Carbon\Carbon|null $verification_date
 * @property string|null $notes
 */
final class DriverQualification extends Model
{
    use BelongsToOrganization;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'driver_id',
        'qualification_type',
        'qualification_name',
        'issuing_authority',
        'qualification_number',
        'issue_date',
        'expiry_date',
        'status',
        'grade_achieved',
        'score_achieved',
        'certificate_file_path',
        'verification_required',
        'verified_by',
        'verification_date',
        'notes',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'verification_required' => 'boolean',
        'verification_date' => 'date',
        'score_achieved' => 'integer',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}
