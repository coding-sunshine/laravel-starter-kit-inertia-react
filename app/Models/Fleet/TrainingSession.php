<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $training_course_id
 * @property string $session_name
 * @property string|null $instructor_name
 * @property string|null $instructor_contact
 * @property \Carbon\Carbon $scheduled_date
 * @property string $start_time
 * @property string $end_time
 * @property string|null $location
 * @property int|null $max_participants
 * @property int $registered_count
 * @property int $attended_count
 * @property string $status
 * @property float|null $completion_rate
 * @property float|null $average_score
 * @property float|null $feedback_score
 * @property string|null $notes
 * @property array|null $materials_provided
 */
final class TrainingSession extends Model
{
    use BelongsToOrganization;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'training_course_id',
        'session_name',
        'instructor_name',
        'instructor_contact',
        'scheduled_date',
        'start_time',
        'end_time',
        'location',
        'max_participants',
        'registered_count',
        'attended_count',
        'status',
        'completion_rate',
        'average_score',
        'feedback_score',
        'notes',
        'materials_provided',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'max_participants' => 'integer',
        'registered_count' => 'integer',
        'attended_count' => 'integer',
        'completion_rate' => 'decimal:2',
        'average_score' => 'decimal:2',
        'feedback_score' => 'decimal:2',
        'materials_provided' => 'array',
    ];

    public function trainingCourse(): BelongsTo
    {
        return $this->belongsTo(TrainingCourse::class, 'training_course_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(TrainingEnrollment::class, 'training_session_id');
    }
}
