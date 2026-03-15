<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $pushable_type
 * @property int $pushable_id
 * @property string $channel
 * @property \Carbon\Carbon $pushed_at
 * @property int|null $user_id
 * @property array|null $response
 * @property string $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class PushHistory extends Model
{
    use HasFactory;

    protected $table = 'push_history';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'pushable_type',
        'pushable_id',
        'channel',
        'pushed_at',
        'user_id',
        'response',
        'status',
    ];

    public function pushable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pushed_at' => 'datetime',
            'response' => 'array',
        ];
    }
}
