<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

final class SprRequest extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Userstamps;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'project_id',
        'spr_count',
        'spr_price',
        'is_payment_completed',
        'is_request_completed',
        'transaction_access_code',
        'transaction_id',
        'extra_attributes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_payment_completed' => 'boolean',
        'is_request_completed' => 'boolean',
        'extra_attributes' => 'array',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
