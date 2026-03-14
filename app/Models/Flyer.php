<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\FlyerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property int|null $flyer_template_id
 * @property int|null $project_id
 * @property int|null $lot_id
 * @property int|null $poster_img_id
 * @property int|null $floorplan_img_id
 * @property string|null $notes
 * @property bool $is_custom
 * @property string|null $custom_html
 * @property string|null $custom_css
 * @property int|null $legacy_id
 * @property int|null $created_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
final class Flyer extends Model implements HasMedia
{
    /** @use HasFactory<FlyerFactory> */
    use HasFactory;

    use InteractsWithMedia;
    use SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'flyer_template_id',
        'project_id',
        'lot_id',
        'poster_img_id',
        'floorplan_img_id',
        'notes',
        'is_custom',
        'custom_html',
        'custom_css',
        'legacy_id',
        'created_by',
    ];

    public function flyerTemplate(): BelongsTo
    {
        return $this->belongsTo(FlyerTemplate::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function casts(): array
    {
        return [
            'is_custom' => 'boolean',
        ];
    }
}
