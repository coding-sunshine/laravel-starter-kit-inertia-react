<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AiBotPromptCommand extends Model
{
    use BelongsToOrganization;
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'ai_bot_category_id',
        'name',
        'slug',
        'prompt',
        'description',
        'type',
        'is_active',
        'order_column',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsTo<AiBotCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(AiBotCategory::class, 'ai_bot_category_id');
    }
}
