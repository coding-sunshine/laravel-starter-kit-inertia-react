<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

final class Flyer extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Userstamps;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'template_id',
        'project_id',
        'lot_id',
        'page_html',
        'page_css',
        'poster_img_id',
        'floorplan_img_id',
        'notes',
        'is_custom',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_custom' => 'boolean',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<FlyerTemplate, $this>
     */
    public function flyerTemplate(): BelongsTo
    {
        return $this->belongsTo(FlyerTemplate::class, 'template_id');
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<Lot, $this>
     */
    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }
}
