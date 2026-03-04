<?php

declare(strict_types=1);

namespace App\Models\Fleet;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $integration_name
 * @property string $integration_type
 * @property string $provider_name
 * @property string|null $api_endpoint
 * @property string|null $authentication_type
 * @property array|null $authentication_config
 * @property string $data_sync_frequency
 * @property \Carbon\Carbon|null $last_sync_timestamp
 * @property string $sync_status
 * @property int $error_count
 * @property string|null $last_error_message
 * @property int|null $rate_limit_per_hour
 * @property int $monthly_usage_count
 * @property int|null $monthly_limit
 * @property string|null $webhook_url
 * @property bool $is_active
 */
final class ApiIntegration extends Model
{
    use BelongsToOrganization;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'integration_name',
        'integration_type',
        'provider_name',
        'api_endpoint',
        'api_key_encrypted',
        'authentication_type',
        'authentication_config',
        'data_sync_frequency',
        'last_sync_timestamp',
        'sync_status',
        'error_count',
        'last_error_message',
        'rate_limit_per_hour',
        'monthly_usage_count',
        'monthly_limit',
        'webhook_url',
        'webhook_secret_encrypted',
        'is_active',
    ];

    protected $casts = [
        'authentication_config' => 'array',
        'last_sync_timestamp' => 'datetime',
        'error_count' => 'integer',
        'rate_limit_per_hour' => 'integer',
        'monthly_usage_count' => 'integer',
        'monthly_limit' => 'integer',
        'is_active' => 'boolean',
    ];

    public function apiLogs(): HasMany
    {
        return $this->hasMany(ApiLog::class, 'integration_id');
    }
}
