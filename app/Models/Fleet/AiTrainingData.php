<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $data_type
 * @property string $source_entity_type
 * @property int $source_entity_id
 * @property array $feature_vector
 */
class AiTrainingData extends Model
{
    use BelongsToOrganization;

    protected $fillable = [
        'data_type', 'source_entity_type', 'source_entity_id', 'training_label',
        'feature_vector', 'ground_truth', 'data_quality_score', 'anonymized', 'consent_obtained',
        'retention_period_days', 'used_for_training', 'model_names',
    ];

    protected $casts = [
        'feature_vector' => 'array',
        'ground_truth' => 'array',
        'data_quality_score' => 'decimal:4',
        'anonymized' => 'boolean',
        'consent_obtained' => 'boolean',
        'used_for_training' => 'boolean',
        'model_names' => 'array',
        'expires_at' => 'datetime',
    ];
}
