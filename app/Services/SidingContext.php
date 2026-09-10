<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Siding;
use App\Models\User;
use Throwable;

/**
 * Service for managing the current siding context within RRMCS.
 *
 * Mirrors TenantContext — users pick an "active siding" and all RRMCS pages
 * automatically scope to it.  Super admins / management may choose "All sidings"
 * (null context) to see cross-siding data.
 *
 * Session convention for `current_siding_id`:
 * - Missing/null: no explicit choice yet → fall back to defaults
 * - 0: user explicitly chose "All sidings"
 * - >0: user chose a specific siding
 *
 * OCTANE COMPATIBILITY:
 * Register the flush listener when using Laravel Octane:
 * Octane::on('request-received', fn () => SidingContext::flush());
 */
final class SidingContext
{
    /**
     * Sentinel value stored in session when user explicitly chose "All sidings".
     */
    private const int ALL_SIDINGS = 0;

    private static ?Siding $current = null;

    /**
     * Whether the context has been explicitly initialized for this request.
     * Distinguishes "not yet initialized" from "initialized to null (All sidings)".
     */
    private static bool $initialized = false;

    public static function set(?Siding $siding): void
    {
        self::$current = $siding;
        self::$initialized = true;

        if (self::hasSession()) {
            session(['current_siding_id' => $siding instanceof Siding ? $siding->id : self::ALL_SIDINGS]);
        }
    }

    public static function get(): ?Siding
    {
        return self::$current;
    }

    public static function id(): ?int
    {
        return self::$current?->id;
    }

    public static function check(): bool
    {
        return self::$current instanceof Siding;
    }

    /**
     * Whether the siding context has been explicitly set (even to null / "All sidings").
     */
    public static function isInitialized(): bool
    {
        return self::$initialized;
    }

    public static function forget(): void
    {
        self::$current = null;
        self::$initialized = false;
        if (self::hasSession()) {
            session()->forget('current_siding_id');
        }
    }

    public static function flush(): void
    {
        self::$current = null;
        self::$initialized = false;
    }

    /**
     * Restore siding context from session or fall back to the user's primary siding.
     * Single-siding users are auto-locked to their only siding.
     */
    public static function initForUser(User $user): void
    {
        // 1. Try restoring from session
        if (self::hasSession()) {
            $sessionSidingId = session('current_siding_id');

            // User explicitly chose "All sidings" — keep null context
            if ($sessionSidingId === self::ALL_SIDINGS) {
                self::$current = null;
                self::$initialized = true;

                return;
            }

            if (is_numeric($sessionSidingId) && $user->canAccessSiding((int) $sessionSidingId)) {
                $siding = Siding::query()->find($sessionSidingId);
                if ($siding instanceof Siding) {
                    self::$current = $siding;
                    self::$initialized = true;

                    return;
                }
            }
        }

        // 2. Auto-lock single-siding users
        $userSidings = $user->sidings()->get();
        if ($userSidings->count() === 1) {
            self::set($userSidings->first());

            return;
        }

        // 3. Fall back to primary siding (if any)
        $primary = $user->getPrimarySiding();
        if ($primary instanceof Siding) {
            self::set($primary);

            return;
        }

        // Super admins / management with no explicit siding stay null ("All sidings")
        self::$initialized = true;
    }

    /**
     * Return the siding IDs that should be used for RRMCS queries.
     *
     * - If a specific siding is set → return [that siding ID]
     * - If no siding set (super admin / management viewing "All") → return all accessible IDs
     *
     * @return array<int>
     */
    public static function activeSidingIds(User $user): array
    {
        if (self::$current instanceof Siding) {
            return [self::$current->id];
        }

        // "All sidings" mode — return everything the user can access
        if ($user->isSuperAdmin()) {
            return Siding::query()->pluck('id')->all();
        }

        return $user->accessibleSidings()->get()->pluck('id')->all();
    }

    private static function hasSession(): bool
    {
        try {
            return app()->bound('session') && session()->isStarted();
        } catch (Throwable) {
            return false;
        }
    }
}
