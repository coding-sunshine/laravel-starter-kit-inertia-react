<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class VerifyImportReservationsSalesCommand extends Command
{
    protected $signature = 'fusion:verify-import-reservations-sales
                            {--reservations=119 : Expected property_reservations count}
                            {--enquiries=701 : Expected property_enquiries count}
                            {--searches=312 : Expected property_searches count}
                            {--sales=443 : Expected sales count}
                            {--commissions=19416 : Expected commissions count}';

    protected $description = 'Verify the fusion:import-reservations-sales import results.';

    public function handle(): int
    {
        $failed = false;

        $failed = $this->verifyTable(
            table: 'property_reservations',
            expected: (int) $this->option('reservations'),
            fkChecks: ['primary_contact_id', 'secondary_contact_id', 'agent_contact_id'],
        ) || $failed;

        $failed = $this->verifyTable(
            table: 'property_enquiries',
            expected: (int) $this->option('enquiries'),
            fkChecks: ['client_contact_id', 'agent_contact_id'],
        ) || $failed;

        $failed = $this->verifyTable(
            table: 'property_searches',
            expected: (int) $this->option('searches'),
            fkChecks: ['client_contact_id', 'agent_contact_id'],
        ) || $failed;

        $failed = $this->verifySales(expected: (int) $this->option('sales')) || $failed;

        $failed = $this->verifyCommissions(expected: (int) $this->option('commissions')) || $failed;

        if ($failed) {
            $this->error('FAIL');

            return self::FAILURE;
        }

        $this->info('PASS');

        return self::SUCCESS;
    }

    private function verifyTable(string $table, int $expected, array $fkChecks): bool
    {
        $failed = false;
        $actual = DB::table($table)->count();

        if ($actual < $expected) {
            $this->warn("[{$table}] count {$actual} < expected {$expected}");
            $failed = true;
        } else {
            $this->info("[{$table}] count OK: {$actual}");
        }

        foreach ($fkChecks as $column) {
            $broken = DB::table($table)
                ->whereNotNull($column)
                ->whereNotExists(function ($q) use ($column) {
                    $q->select(DB::raw(1))
                        ->from('contacts')
                        ->whereColumn('contacts.id', "{$table}.{$column}");
                })
                ->count();

            if ($broken > 0) {
                $this->warn("[{$table}] {$broken} rows with broken FK on {$column}");
                $failed = true;
            } else {
                $this->info("[{$table}] FK {$column} OK");
            }
        }

        return $failed;
    }

    private function verifySales(int $expected): bool
    {
        $failed = false;
        $actual = DB::table('sales')->count();

        if ($actual < $expected) {
            $this->warn("[sales] count {$actual} < expected {$expected}");
            $failed = true;
        } else {
            $this->info("[sales] count OK: {$actual}");
        }

        $contactFks = [
            'client_contact_id',
            'sales_agent_contact_id',
            'subscriber_contact_id',
            'bdm_contact_id',
            'referral_partner_contact_id',
            'affiliate_contact_id',
            'agent_contact_id',
        ];

        foreach ($contactFks as $column) {
            $broken = DB::table('sales')
                ->whereNotNull($column)
                ->whereNotExists(function ($q) use ($column) {
                    $q->select(DB::raw(1))
                        ->from('contacts')
                        ->whereColumn('contacts.id', "sales.{$column}");
                })
                ->count();

            if ($broken > 0) {
                $this->warn("[sales] {$broken} rows with broken FK on {$column}");
                $failed = true;
            } else {
                $this->info("[sales] FK {$column} OK");
            }
        }

        $noLot = DB::table('sales')->whereNull('lot_id')->count();
        $this->info("[sales] rows without lot_id: {$noLot}");

        return $failed;
    }

    private function verifyCommissions(int $expected): bool
    {
        $failed = false;
        $actual = DB::table('commissions')->count();

        if ($actual < $expected) {
            $this->warn("[commissions] count {$actual} < expected {$expected}");
            $failed = true;
        } else {
            $this->info("[commissions] count OK: {$actual}");
        }

        $broken = DB::table('commissions')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('sales')
                    ->whereColumn('sales.id', 'commissions.sale_id');
            })
            ->count();

        if ($broken > 0) {
            $this->warn("[commissions] {$broken} rows with broken sale_id FK");
            $failed = true;
        } else {
            $this->info('[commissions] FK sale_id OK');
        }

        return $failed;
    }
}
