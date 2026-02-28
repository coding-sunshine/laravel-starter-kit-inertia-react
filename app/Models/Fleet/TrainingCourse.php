<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $course_name
 * @property string|null $course_code
 * @property string|null $description
 * @property string $category
 * @property float $duration_hours
 * @property string $delivery_method
 * @property array|null $prerequisites
 * @property array|null $learning_objectives
 * @property bool $assessment_required
 * @property int $pass_mark_percentage
 * @property bool $certificate_awarded
 * @property int|null $validity_period_months
 * @property float|null $cost_per_person
 * @property string|null $provider_name
 * @property string|null $provider_contact
 * @property int|null $max_participants
 * @property array|null $materials_required
 * @property array|null $equipment_required
 * @property bool $is_mandatory
 * @property bool $is_active
 */
class TrainingCourse extends Model
{
    use BelongsToOrganization;
    use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'course_name',
        'course_code',
        'description',
        'category',
        'duration_hours',
        'delivery_method',
        'prerequisites',
        'learning_objectives',
        'assessment_required',
        'pass_mark_percentage',
        'certificate_awarded',
        'validity_period_months',
        'cost_per_person',
        'provider_name',
        'provider_contact',
        'max_participants',
        'materials_required',
        'equipment_required',
        'is_mandatory',
        'is_active',
    ];

    protected $casts = [
        'duration_hours' => 'decimal:2',
        'prerequisites' => 'array',
        'learning_objectives' => 'array',
        'assessment_required' => 'boolean',
        'pass_mark_percentage' => 'integer',
        'certificate_awarded' => 'boolean',
        'validity_period_months' => 'integer',
        'cost_per_person' => 'decimal:2',
        'max_participants' => 'integer',
        'materials_required' => 'array',
        'equipment_required' => 'array',
        'is_mandatory' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function trainingSessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class, 'training_course_id');
    }
}
