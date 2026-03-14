<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int|null $organization_id
 * @property string $name
 * @property string $channel
 * @property string|null $subject
 * @property string $body
 * @property array|null $variants
 * @property array|null $ctas
 * @property bool $ai_generated
 * @property string|null $tone
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class ColdOutreachTemplate extends Model
{
    use HasFactory;
    use LogsActivity;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'name',
        'channel',
        'subject',
        'body',
        'variants',
        'ctas',
        'ai_generated',
        'tone',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logAll()
            ->logExcept(['embedding']);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'variants' => 'array',
            'ctas' => 'array',
            'ai_generated' => 'boolean',
        ];
    }
}
