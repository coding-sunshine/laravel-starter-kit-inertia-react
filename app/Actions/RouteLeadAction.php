<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contact;
use App\Models\User;
use App\Services\LeadScoringService;
use Illuminate\Support\Facades\DB;

/**
 * Auto-assign a contact to an agent based on lead score and routing rules.
 */
final readonly class RouteLeadAction
{
    public function __construct(private LeadScoringService $leadScoring)
    {
        //
    }

    /**
     * Route a lead to an agent based on score, round-robin, and geography.
     * Returns the assigned user or null if no assignment could be made.
     */
    public function handle(Contact $contact): ?User
    {
        $score = $this->leadScoring->score($contact);

        // Update lead_score on contact
        $contact->update(['lead_score' => $score]);

        // Find agents in the organization (role = agent or sales_agent)
        $agents = User::query()
            ->whereHas('organizations', fn ($q) => $q->where('organizations.id', $contact->organization_id))
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['agent', 'sales_agent']))
            ->orderBy('id')
            ->get();

        if ($agents->isEmpty()) {
            return null;
        }

        // Round-robin: find agent with fewest active contacts
        $assigned = $agents->sortBy(function (User $agent): int {
            return Contact::query()
                ->where('organization_id', $contact->organization_id)
                ->where('assigned_to_user_id', $agent->id)
                ->whereIn('stage', ['new', 'warm', 'hot', 'qualified'])
                ->count();
        })->first();

        if ($assigned) {
            DB::table('contacts')
                ->where('id', $contact->id)
                ->update(['assigned_to_user_id' => $assigned->id]);

            $contact->refresh();
        }

        return $assigned;
    }
}
