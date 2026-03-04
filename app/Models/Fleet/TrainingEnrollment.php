<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $training_session_id
 * @property int $driver_id
 * @property \Carbon\Carbon $enrollment_date
 * @property string $enrollment_status
 * @property bool $attendance_marked
 * @property \Carbon\Carbon|null $start_time
 * @property \Carbon\Carbon|null $end_time
 * @property int $completion_percentage
 * @property int|null $assessment_score
 * @property string $pass_fail
 * @property bool $certificate_issued
 * @property string|null $certificate_number
 * @property int|null $feedback_rating
 * @property string|null $feedback_comments
 * @property int $enrolled_by
 */
final class TrainingEnrollment extends Model
{
    use BelongsToOrganization;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'training_session_id',
        'driver_id',
        'enrollment_date',
        'enrollment_status',
        'attendance_marked',
        'start_time',
        'end_time',
        'completion_percentage',
        'assessment_score',
        'pass_fail',
        'certificate_issued',
        'certificate_number',
        'feedback_rating',
        'feedback_comments',
        'enrolled_by',
    ];

    protected $casts = [
        'enrollment_date' => 'date',
        'attendance_marked' => 'boolean',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'completion_percentage' => 'integer',
        'assessment_score' => 'integer',
        'certificate_issued' => 'boolean',
        'feedback_rating' => 'integer',
    ];

    public function trainingSession(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class, 'training_session_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function enrolledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enrolled_by');
    }
}
