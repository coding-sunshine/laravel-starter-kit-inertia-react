<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Siding;
use App\Models\SidingShift;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class SyncRrmmsUserAssignments extends Command
{
    protected $signature = 'rrmms:sync-user-assignments {--dry-run : Show changes without writing} {--strict : Fail when expected records are missing}';

    protected $description = 'Sync RRMMS user siding/shift assignments for seeded accounts and role groups.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $strict = (bool) $this->option('strict');

        $sidings = Siding::query()->orderBy('id')->get(['id', 'name', 'code']);
        $allSidingIds = $sidings->pluck('id')->all();
        $allShiftIds = SidingShift::query()->where('is_active', true)->pluck('id')->all();

        if ($allSidingIds === [] || $allShiftIds === []) {
            $this->error('Sidings or active siding shifts are missing. Run siding/shift seeders first.');

            return self::FAILURE;
        }

        $writes = 0;
        $failures = 0;

        // Super-admin + dispatch-manage-admin should have access to all sidings and all shifts.
        $allAccessUsers = User::query()
            ->whereIn('email', ['superadmin@rmms.local', 'dispatch.admin@rmms.local'])
            ->orWhereHas('roles', fn ($q) => $q->whereIn('name', ['super-admin', 'dispatch-manage-admin']))
            ->get();

        foreach ($allAccessUsers as $user) {
            $this->line("Sync all-access assignments for {$user->email}");
            $writes += $this->syncSidingAccess($user, $allSidingIds, $dryRun);
            $writes += $this->syncShiftAccess($user, $allShiftIds, $dryRun);
            $writes += $this->syncLegacySidingId($user, null, $dryRun);
        }

        // Shift users from rrmms-accounts naming convention.
        $patternUsers = User::query()
            ->where('email', 'like', '%.shift%@rmms.local')
            ->orWhere('email', 'like', '%.wbshift%@rmms.local')
            ->get();

        foreach ($patternUsers as $user) {
            $parsed = $this->parseShiftEmail($user->email);
            if ($parsed === null) {
                continue;
            }

            $siding = $this->resolveSidingFromSlug($sidings, $parsed['slug']);
            if ($siding === null) {
                $message = "Unable to resolve siding for {$user->email}.";
                if ($strict) {
                    $this->error($message);
                    $failures++;
                } else {
                    $this->warn($message);
                }

                continue;
            }

            $shift = SidingShift::query()
                ->where('siding_id', $siding->id)
                ->where('sort_order', $parsed['shift'])
                ->where('is_active', true)
                ->first(['id']);

            if ($shift === null) {
                $message = "Unable to resolve active shift {$parsed['shift']} for {$user->email}.";
                if ($strict) {
                    $this->error($message);
                    $failures++;
                } else {
                    $this->warn($message);
                }

                continue;
            }

            $this->line("Sync single-access assignments for {$user->email} => {$siding->name} shift {$parsed['shift']}");
            $writes += $this->syncSidingAccess($user, [$siding->id], $dryRun);
            $writes += $this->syncShiftAccess($user, [$shift->id], $dryRun);
            $writes += $this->syncLegacySidingId($user, (int) $siding->id, $dryRun);
        }

        if ($failures > 0) {
            $this->error("Completed with {$failures} strict failure(s).");

            return self::FAILURE;
        }

        $this->info($dryRun
            ? "Dry run complete. {$writes} potential write operations detected."
            : "Sync complete. {$writes} write operations applied.");

        return self::SUCCESS;
    }

    /**
     * @param  list<int>  $sidingIds
     */
    private function syncSidingAccess(User $user, array $sidingIds, bool $dryRun): int
    {
        $now = now();
        $operations = 0;

        if (! $dryRun) {
            DB::table('user_siding')->where('user_id', $user->id)->delete();
            foreach (array_values($sidingIds) as $index => $sidingId) {
                DB::table('user_siding')->insert([
                    'user_id' => $user->id,
                    'siding_id' => $sidingId,
                    'is_primary' => $index === 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $operations += 1 + count($sidingIds);

        return $operations;
    }

    /**
     * @param  list<int>  $shiftIds
     */
    private function syncShiftAccess(User $user, array $shiftIds, bool $dryRun): int
    {
        $now = now();
        $operations = 0;

        if (! $dryRun) {
            DB::table('siding_shift_user')->where('user_id', $user->id)->delete();
            foreach ($shiftIds as $shiftId) {
                DB::table('siding_shift_user')->insert([
                    'user_id' => $user->id,
                    'siding_shift_id' => $shiftId,
                    'assigned_at' => $now,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $operations += 1 + count($shiftIds);

        return $operations;
    }

    private function syncLegacySidingId(User $user, ?int $sidingId, bool $dryRun): int
    {
        if (! $dryRun) {
            $user->forceFill(['siding_id' => $sidingId])->save();
        }

        return 1;
    }

    /**
     * @return array{slug: string, shift: int}|null
     */
    private function parseShiftEmail(string $email): ?array
    {
        if (! preg_match('/^(?<slug>[a-z0-9]+)\.(?:shift|wbshift)(?<shift>[1-3])@rmms\.local$/', $email, $matches)) {
            return null;
        }

        return [
            'slug' => (string) $matches['slug'],
            'shift' => (int) $matches['shift'],
        ];
    }

    private function resolveSidingFromSlug(Collection $sidings, string $slug): ?Siding
    {
        $exactCode = $sidings->first(fn (Siding $siding): bool => Str::lower($siding->code) === $slug);
        if ($exactCode instanceof Siding) {
            return $exactCode;
        }

        $byNamePrefix = $sidings->first(function (Siding $siding) use ($slug): bool {
            $nameSlug = Str::lower((string) Str::before($siding->name, ' '));

            return $nameSlug === $slug;
        });

        return $byNamePrefix instanceof Siding ? $byNamePrefix : null;
    }
}
