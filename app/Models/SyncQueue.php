<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SyncQueue extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $table = 'sync_queue';

    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'payload',
        'status',
        'retry_count',
        'last_attempted_at',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'json',
        'last_attempted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
