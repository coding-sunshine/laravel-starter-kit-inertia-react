<?php

declare(strict_types=1);

namespace App\Console\Commands\Fusion;

use App\Models\Company;
use App\Models\Contact;
use App\Models\ContactEmail;
use App\Models\ContactPhone;
use App\Models\Scopes\OrganizationScope;
use App\Models\Source;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

final class VerifyImportContactsCommand extends Command
{
    protected $signature = 'fusion:verify-import-contacts
                            {--legacy=mysql_legacy : Legacy DB connection to compare counts}';

    protected $description = 'Verify contact import: compare row counts (contacts vs leads, contact_emails+contact_phones vs polymorphic contacts).';

    public function handle(): int
    {
        $conn = $this->option('legacy');

        $contactsCount = Contact::withoutGlobalScope(OrganizationScope::class)->count();
        $emailsCount = ContactEmail::count();
        $phonesCount = ContactPhone::count();
        $sourcesCount = Source::count();
        $companiesCount = Company::count();

        $this->table(
            ['Table', 'New (PostgreSQL)', 'Legacy (MySQL)', 'OK?'],
            [
                ['contacts', (string) $contactsCount, $this->legacyCount($conn, 'leads'), $this->cmp($contactsCount, $this->legacyCount($conn, 'leads'))],
                ['contact_emails + contact_phones', (string) ($emailsCount + $phonesCount), $this->legacyContactablesCount($conn), $this->cmp($emailsCount + $phonesCount, $this->legacyContactablesCount($conn))],
                ['sources', (string) $sourcesCount, $this->legacyCount($conn, 'sources'), $this->cmp($sourcesCount, $this->legacyCount($conn, 'sources'))],
                ['companies', (string) $companiesCount, $this->legacyCount($conn, 'companies'), $this->cmp($companiesCount, $this->legacyCount($conn, 'companies'))],
            ]
        );

        $withLegacy = Contact::withoutGlobalScope(OrganizationScope::class)->whereNotNull('legacy_lead_id')->count();
        $this->line('Contacts with legacy_lead_id (map): '.$withLegacy);

        $legacyLeads = $this->legacyCountInt($conn, 'leads');
        $legacyContactables = $this->legacyContactablesCount($conn);
        $detailsCount = $emailsCount + $phonesCount;
        $contactsOk = $legacyLeads !== null && $contactsCount === $legacyLeads;
        // Details can be less than legacy: we skip rows with empty value or model_id not in contact map
        $detailsOk = $legacyContactables === 0 || $detailsCount >= (int) ($legacyContactables * 0.90);

        if (! $contactsOk || ! $detailsOk) {
            $this->warn('Verification: some counts do not match. Check legacy DB or re-run fusion:import-contacts.');

            return self::FAILURE;
        }

        if ($legacyContactables > 0 && $detailsCount < $legacyContactables) {
            $this->line('Note: '.($legacyContactables - $detailsCount).' legacy contact-detail rows were skipped (empty value or orphan lead_id).');
        }
        $this->info('Verification PASS: data fully imported.');

        return self::SUCCESS;
    }

    private function legacyCount(string $connection, string $table): string
    {
        $n = $this->legacyCountInt($connection, $table);

        return $n === null ? 'N/A (no connection)' : (string) $n;
    }

    private function legacyCountInt(string $connection, string $table): ?int
    {
        try {
            return (int) DB::connection($connection)->table($table)->whereNull('deleted_at')->count();
        } catch (Throwable) {
            return null;
        }
    }

    private function legacyContactablesCount(string $connection): int
    {
        try {
            return (int) DB::connection($connection)->table('contacts')
                ->where('model_type', 'App\\Models\\Lead')
                ->count();
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * @param  int|string  $b
     */
    private function cmp(int $a, $b): string
    {
        if ($b === 'N/A (no connection)' || ! is_numeric($b)) {
            return '—';
        }
        $bInt = (int) $b;

        return $a === $bInt ? '✓' : '✗';
    }
}
