<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AiBotBox extends Model
{
    use BelongsToOrganization;
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'ai_bot_category_id',
        'title',
        'description',
        'page_overview',
        'type',
        'visibility',
        'status',
        'order_column',
    ];

    /**
     * @return BelongsTo<AiBotCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(AiBotCategory::class, 'ai_bot_category_id');
    }
}
