<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OnboardingStep;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $user_id
 * @property OnboardingStep $step_key
 * @property CarbonInterface|null $completed_at
 * @property CarbonInterface $created_at
 * @property-read User $user
 */
final class OnboardingProgress extends Model
{
    use LogsActivity;

    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'step_key',
        'completed_at',
        'created_at',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->logAll()
            ->logExcept(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'embedding', 'api_token']);
    }

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'step_key' => OnboardingStep::class,
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
