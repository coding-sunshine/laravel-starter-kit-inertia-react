<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PasswordResetOtpStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $email
 * @property string $otp_hash
 * @property \Carbon\Carbon $expires_at
 * @property int $attempts
 * @property \Carbon\Carbon|null $verified_at
 * @property string|null $reset_token_hash
 * @property \Carbon\Carbon|null $reset_token_expires_at
 * @property \Carbon\Carbon|null $consumed_at
 * @property PasswordResetOtpStatus $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class PasswordResetOtp extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'otp_hash',
        'expires_at',
        'attempts',
        'verified_at',
        'reset_token_hash',
        'reset_token_expires_at',
        'consumed_at',
        'status',
    ];

    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(mb_trim($email));
    }

    public static function findPendingForEmail(string $email): ?self
    {
        return self::query()
            ->where('email', self::normalizeEmail($email))
            ->where('status', PasswordResetOtpStatus::Pending)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();
    }

    public static function findVerifiedForEmail(string $email): ?self
    {
        return self::query()
            ->where('email', self::normalizeEmail($email))
            ->where('status', PasswordResetOtpStatus::Verified)
            ->whereNull('consumed_at')
            ->where('reset_token_expires_at', '>', now())
            ->latest('id')
            ->first();
    }

    public function isOtpValid(): bool
    {
        return $this->status === PasswordResetOtpStatus::Pending
            && $this->verified_at === null
            && $this->expires_at->isFuture()
            && $this->attempts < (int) config('auth.password_reset_otp.max_attempts', 5);
    }

    public function isResetTokenValid(): bool
    {
        return $this->status === PasswordResetOtpStatus::Verified
            && $this->consumed_at === null
            && $this->reset_token_hash !== null
            && $this->reset_token_expires_at !== null
            && $this->reset_token_expires_at->isFuture();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'reset_token_expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'status' => PasswordResetOtpStatus::class,
            'attempts' => 'integer',
        ];
    }
}
