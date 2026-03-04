<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Mattiverse\Userstamps\Traits\Userstamps;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property string $trigger_type
 * @property bool $is_active
 */
final class WorkflowDefinition extends Model
{
    use BelongsToOrganization;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use Userstamps;

    protected $fillable = ['name', 'description', 'trigger_type', 'trigger_config', 'steps', 'is_active'];

    protected $casts = [
        'trigger_config' => 'array',
        'steps' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * @return HasMany<WorkflowExecution, $this>
     */
    public function workflowExecutions(): HasMany
    {
        return $this->hasMany(WorkflowExecution::class, 'workflow_definition_id');
    }
}
