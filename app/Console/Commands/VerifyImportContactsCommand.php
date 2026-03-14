<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class VerifyImportContactsCommand extends Command
{
    protected $signature = 'fusion:verify-import-contacts
                            {--expected-contacts=9678 : Expected contacts count}
                            {--expected-sources=17 : Expected sources count}
                            {--expected-companies=969 : Expected companies count}
                            {--json : Output as JSON}';

    protected $description = 'Verify the contacts import: check row counts and mapping integrity';

    public function handle(): int
    {
        $contacts = (int) DB::table('contacts')->count();
        $contactEmails = (int) DB::table('contact_emails')->count();
        $contactPhones = (int) DB::table('contact_phones')->count();
        $sources = (int) DB::table('sources')->count();
        $companies = (int) DB::table('companies')->count();

        // Check map completeness: every contact has a legacy_lead_id
        $contactsWithLegacyId = (int) DB::table('contacts')->whereNotNull('legacy_lead_id')->count();
        $mapComplete = $contacts > 0 && $contactsWithLegacyId === $contacts;

        // Check for orphan emails/phones
        $orphanEmails = (int) DB::table('contact_emails')
            ->leftJoin('contacts', 'contact_emails.contact_id', '=', 'contacts.id')
            ->whereNull('contacts.id')
            ->count();

        $orphanPhones = (int) DB::table('contact_phones')
            ->leftJoin('contacts', 'contact_phones.contact_id', '=', 'contacts.id')
            ->whereNull('contacts.id')
            ->count();

        $expectedContacts = (int) $this->option('expected-contacts');
        $expectedSources = (int) $this->option('expected-sources');
        $expectedCompanies = (int) $this->option('expected-companies');

        $contactsOk = $contacts >= $expectedContacts;
        $sourcesOk = $sources >= $expectedSources;
        $companiesOk = $companies >= $expectedCompanies;
        $orphansOk = $orphanEmails === 0 && $orphanPhones === 0;

        $result = ($contactsOk && $sourcesOk && $companiesOk && $orphansOk) ? 'PASS' : 'FAIL';

        if ($this->option('json')) {
            $this->line(json_encode([
                'contacts' => $contacts,
                'contact_emails' => $contactEmails,
                'contact_phones' => $contactPhones,
                'sources' => $sources,
                'companies' => $companies,
                'map_complete' => $mapComplete,
                'orphan_emails' => $orphanEmails,
                'orphan_phones' => $orphanPhones,
                'result' => $result,
            ], JSON_THROW_ON_ERROR));
        } else {
            $this->line("contacts: {$contacts}, contact_emails: {$contactEmails}, contact_phones: {$contactPhones}, sources: {$sources}, companies: {$companies}, map_complete: ".($mapComplete ? 'true' : 'false').", orphan_emails: {$orphanEmails}, orphan_phones: {$orphanPhones}, RESULT: {$result}");
        }

        if (! $contactsOk) {
            $this->warn("contacts mismatch: got {$contacts}, expected >= {$expectedContacts}");
        }

        if (! $sourcesOk) {
            $this->warn("sources mismatch: got {$sources}, expected >= {$expectedSources}");
        }

        if (! $companiesOk) {
            $this->warn("companies mismatch: got {$companies}, expected >= {$expectedCompanies}");
        }

        if (! $orphansOk) {
            $this->warn("orphan emails: {$orphanEmails}, orphan phones: {$orphanPhones}");
        }

        return $result === 'PASS' ? self::SUCCESS : self::FAILURE;
    }
}
