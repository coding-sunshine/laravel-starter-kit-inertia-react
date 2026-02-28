<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $organization_id
 * @property int|null $integration_id
 * @property string $request_method
 * @property string $request_url
 * @property array|null $request_headers
 * @property array|null $request_body
 * @property int|null $response_status_code
 * @property array|null $response_headers
 * @property array|null $response_body
 * @property int|null $response_time_ms
 * @property string|null $error_message
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property int|null $user_id
 * @property \Carbon\Carbon $created_at
 */
class ApiLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'organization_id',
        'integration_id',
        'request_method',
        'request_url',
        'request_headers',
        'request_body',
        'response_status_code',
        'response_headers',
        'response_body',
        'response_time_ms',
        'error_message',
        'ip_address',
        'user_agent',
        'user_id',
    ];

    protected $casts = [
        'request_headers' => 'array',
        'request_body' => 'array',
        'response_headers' => 'array',
        'response_body' => 'array',
        'response_status_code' => 'integer',
        'response_time_ms' => 'integer',
        'user_id' => 'integer',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function apiIntegration(): BelongsTo
    {
        return $this->belongsTo(ApiIntegration::class, 'integration_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
