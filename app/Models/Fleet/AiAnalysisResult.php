<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $analysis_type
 * @property string $entity_type
 * @property int $entity_id
 * @property string $model_name
 * @property float $confidence_score
 * @property string $status
 */
class AiAnalysisResult extends Model
{
    use BelongsToOrganization;
    use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'analysis_type', 'entity_type', 'entity_id', 'model_name', 'model_version',
        'confidence_score', 'risk_score', 'priority', 'primary_finding',
        'detailed_analysis', 'recommendations', 'action_items', 'business_impact',
        'status', 'review_notes', 'expires_at', 'superseded_by', 'training_feedback', 'feedback_rating',
    ];

    protected $casts = [
        'confidence_score' => 'decimal:4',
        'risk_score' => 'decimal:2',
        'detailed_analysis' => 'array',
        'recommendations' => 'array',
        'action_items' => 'array',
        'business_impact' => 'array',
        'reviewed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
