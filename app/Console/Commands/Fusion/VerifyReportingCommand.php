<?php

declare(strict_types=1);

namespace App\Console\Commands\Fusion;

use App\Models\Contact;
use App\Models\PropertyReservation;
use App\Models\Sale;
use App\Models\Task;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Console\Command;

final class VerifyReportingCommand extends Command
{
    protected $signature = 'fusion:verify-reporting
                            {--organization-id= : Scope to this organization (optional)}';

    protected $description = 'Verify Step 7 reporting: output KPI aggregates for spot-check against dashboard.';

    public function handle(): int
    {
        $orgId = $this->option('organization-id') !== null ? (int) $this->option('organization-id') : null;

        $contacts = $this->count(Contact::class, $orgId);
        $tasksOpen = $this->count(Task::class, $orgId, fn ($q) => $q->whereNull('completed_at'));
        $reservations = $this->count(PropertyReservation::class, $orgId);
        $sales = $this->count(Sale::class, $orgId);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Contacts', $contacts],
                ['Tasks (open)', $tasksOpen],
                ['Property reservations', $reservations],
                ['Sales', $sales],
            ],
        );

        $this->info('Dashboard and report pages should show these totals when tenant is set. Run with --organization-id=<id> to scope.');

        return self::SUCCESS;
    }

    private function count(string $model, ?int $orgId, ?\Closure $extra = null): int
    {
        $query = $model::withoutGlobalScope(OrganizationScope::class)
            ->when($orgId !== null, fn ($q) => $q->where('organization_id', $orgId));
        if ($extra !== null) {
            $query = $extra($query);
        }

        return $query->count();
    }
}
