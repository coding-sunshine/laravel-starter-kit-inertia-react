<?php

declare(strict_types=1);

namespace App\Console\Commands\Fusion;

use App\Models\Commission;
use App\Models\PropertyEnquiry;
use App\Models\PropertyReservation;
use App\Models\PropertySearch;
use App\Models\Sale;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

final class VerifyImportReservationsSalesCommand extends Command
{
    protected $signature = 'fusion:verify-import-reservations-sales
                            {--legacy=mysql_legacy : Legacy DB connection to compare counts}';

    protected $description = 'Verify reservations/sales import: compare row counts and ensure contact FKs resolve.';

    public function handle(): int
    {
        $conn = $this->option('legacy');

        $reservations = PropertyReservation::withoutGlobalScope(OrganizationScope::class)->count();
        $enquiries = PropertyEnquiry::withoutGlobalScope(OrganizationScope::class)->count();
        $searches = PropertySearch::withoutGlobalScope(OrganizationScope::class)->count();
        $sales = Sale::withoutGlobalScope(OrganizationScope::class)->count();
        $commCount = Commission::count();

        $legacyRes = $this->legacyCount($conn, 'property_reservations');
        $legacyEnq = $this->legacyCount($conn, 'property_enquiries');
        $legacySearch = $this->legacyCount($conn, 'property_searches');
        $legacySales = $this->legacyCount($conn, 'sales');
        $legacyComm = $this->legacyCommissionCount($conn);

        $this->table(
            ['Table', 'New (PostgreSQL)', 'Legacy (MySQL)', 'OK?'],
            [
                ['property_reservations', (string) $reservations, $legacyRes, $this->cmp($reservations, $legacyRes)],
                ['property_enquiries', (string) $enquiries, $legacyEnq, $this->cmp($enquiries, $legacyEnq)],
                ['property_searches', (string) $searches, $legacySearch, $this->cmp($searches, $legacySearch)],
                ['sales', (string) $sales, $legacySales, $this->cmp($sales, $legacySales)],
                ['commissions (Sale)', (string) $commCount, $legacyComm, $this->cmp($commCount, $legacyComm)],
            ]
        );

        $orphanReservations = DB::table('property_reservations')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('contacts')->whereColumn('contacts.id', 'property_reservations.agent_contact_id'))
            ->count();
        $orphanSales = DB::table('sales')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('contacts')->whereColumn('contacts.id', 'sales.client_contact_id'))
            ->count();

        if ($orphanReservations > 0 || $orphanSales > 0) {
            $this->error("Orphan rows: reservations with invalid contact={$orphanReservations}, sales with invalid client_contact={$orphanSales}");
            return self::FAILURE;
        }
        $this->info('All contact FKs resolve (no orphan reservations/sales).');

        $expected = [
            'property_reservations' => 119,
            'property_enquiries' => 701,
            'property_searches' => 312,
            'sales' => 443,
            'commissions' => 19416,
        ];
        $ok = true;
        foreach ($expected as $table => $exp) {
            $actual = match ($table) {
                'property_reservations' => $reservations,
                'property_enquiries' => $enquiries,
                'property_searches' => $searches,
                'sales' => $sales,
                'commissions' => $commCount,
            };
            if ($actual !== $exp) {
                $this->warn("{$table}: expected {$exp}, got {$actual}");
                $ok = false;
            }
        }
        if (! $ok) {
            return self::FAILURE;
        }
        $this->info('Verification PASS: counts match expected (reservations 119, enquiries 701, searches 312, sales 443, commissions 19,416).');
        return self::SUCCESS;
    }

    private function legacyCount(string $connection, string $table): string
    {
        try {
            $n = (int) DB::connection($connection)->table($table)->whereNull('deleted_at')->count();
            return (string) $n;
        } catch (Throwable) {
            return 'N/A';
        }
    }

    private function legacyCommissionCount(string $connection): string
    {
        try {
            $n = (int) DB::connection($connection)->table('commissions')
                ->where('commissionable_type', 'App\\Models\\Sale')
                ->whereNull('deleted_at')
                ->count();
            return (string) $n;
        } catch (Throwable) {
            return 'N/A';
        }
    }

    /**
     * @param int|string $b
     */
    private function cmp(int $a, $b): string
    {
        if ($b === 'N/A' || ! is_numeric($b)) {
            return '—';
        }
        return $a === (int) $b ? '✓' : '✗';
    }
}
