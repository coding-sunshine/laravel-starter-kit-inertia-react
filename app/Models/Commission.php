<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int|null $legacy_id
 * @property int|null $sale_id
 * @property string|null $commissionable_type
 * @property int|null $commissionable_id
 * @property string $commission_type
 * @property int|null $agent_user_id
 * @property float|null $rate_percentage
 * @property float $amount
 * @property bool $override_amount
 * @property string|null $notes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class Commission extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    /**
     * Commission type constants.
     */
    public const string TYPE_PIAB = 'piab';

    public const string TYPE_SUBSCRIBER = 'subscriber';

    public const string TYPE_AFFILIATE = 'affiliate';

    public const string TYPE_SALES_AGENT = 'sales_agent';

    public const string TYPE_REFERRAL_PARTNER = 'referral_partner';

    public const string TYPE_BDM = 'bdm';

    public const string TYPE_SUB_AGENT = 'sub_agent';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'legacy_id',
        'sale_id',
        'commissionable_type',
        'commissionable_id',
        'commission_type',
        'agent_user_id',
        'rate_percentage',
        'amount',
        'override_amount',
        'notes',
    ];

    /**
     * @return BelongsTo<Sale, $this>
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function commissionable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function agentUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_user_id');
    }

    /**
     * @param  Builder<Commission>  $query
     */
    public function scopeSaleBased(Builder $query): void
    {
        $query->whereNotNull('sale_id');
    }

    /**
     * @param  Builder<Commission>  $query
     */
    public function scopeStandalone(Builder $query): void
    {
        $query->whereNull('sale_id')->whereNotNull('commissionable_type');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logAll()
            ->logExcept(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'embedding', 'api_token']);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'override_amount' => 'boolean',
            'amount' => 'decimal:2',
            'rate_percentage' => 'decimal:2',
        ];
    }
}
