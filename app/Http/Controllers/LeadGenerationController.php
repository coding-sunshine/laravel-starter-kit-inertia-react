<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GenerateLandingPageCopyAction;
use App\Actions\GenerateLeadBriefAction;
use App\Actions\RouteLeadAction;
use App\Models\Contact;
use App\Models\EngagementEvent;
use App\Models\LeadScore;
use App\Models\Lot;
use App\Models\NurtureSequence;
use App\Models\Project;
use App\Services\LeadScoringService;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Lead generation dashboard: landing page AI, lead brief, coaching panel, scoring.
 */
final class LeadGenerationController extends Controller
{
    public function __construct(
        private GenerateLandingPageCopyAction $landingPageAction,
        private GenerateLeadBriefAction $briefAction,
        private RouteLeadAction $routeAction,
        private LeadScoringService $scoringService,
    ) {
        //
    }

    public function index(): Response
    {
        $orgId = TenantContext::id();

        $recentLeads = Contact::query()
            ->where('organization_id', $orgId)
            ->where('type', 'lead')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'first_name', 'last_name', 'stage', 'lead_score', 'contact_origin', 'created_at']);

        $totalLeads = Contact::query()->where('organization_id', $orgId)->where('type', 'lead')->count();
        $hotLeads = Contact::query()->where('organization_id', $orgId)->where('stage', 'hot')->count();
        $engagementToday = EngagementEvent::query()
            ->whereHas('contact', fn ($q) => $q->where('organization_id', $orgId))
            ->whereDate('occurred_at', today())
            ->count();

        $activeSequences = NurtureSequence::query()
            ->where(fn ($q) => $q->whereNull('organization_id')->orWhere('organization_id', $orgId))
            ->where('is_active', true)
            ->withCount('enrollments')
            ->get(['id', 'name', 'trigger_stage', 'enrollments_count']);

        return Inertia::render('lead-generation/index', [
            'stats' => [
                'total_leads' => $totalLeads,
                'hot_leads' => $hotLeads,
                'engagement_today' => $engagementToday,
                'active_sequences' => $activeSequences->count(),
            ],
            'recent_leads' => $recentLeads,
            'active_sequences' => $activeSequences,
        ]);
    }

    /**
     * Generate landing page copy from a project or lot listing.
     */
    public function landingPageCopy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'in:project,lot'],
            'id' => ['required', 'integer'],
        ]);

        $listing = $data['type'] === 'project'
            ? Project::query()->findOrFail($data['id'])
            : Lot::query()->findOrFail($data['id']);

        $copy = $this->landingPageAction->handle($listing);

        return response()->json(['success' => true, 'copy' => $copy]);
    }

    /**
     * Generate a lead brief for a specific contact.
     */
    public function leadBrief(Contact $contact): JsonResponse
    {
        $brief = $this->briefAction->handle($contact);

        return response()->json(['success' => true, 'brief' => $brief]);
    }

    /**
     * Score and route a lead.
     */
    public function scoreAndRoute(Contact $contact): JsonResponse
    {
        $score = $this->scoringService->score($contact);
        $contact->update(['lead_score' => $score]);

        LeadScore::query()->updateOrCreate(
            ['contact_id' => $contact->id],
            [
                'score' => $score,
                'factors_json' => ['computed_by' => 'LeadScoringService', 'stage' => $contact->stage],
                'model_version' => 'rule-based-v1',
                'scored_at' => now(),
            ]
        );

        $assignedAgent = $this->routeAction->handle($contact);

        return response()->json([
            'success' => true,
            'score' => $score,
            'assigned_agent' => $assignedAgent
                ? ['id' => $assignedAgent->id, 'name' => $assignedAgent->name]
                : null,
        ]);
    }

    /**
     * Coaching panel: AI suggestions for agents working a specific contact.
     */
    public function coaching(Contact $contact): Response
    {
        $brief = $this->briefAction->handle($contact);
        $score = $this->scoringService->score($contact);

        $contact->loadMissing(['emails', 'phones', 'tasks', 'propertySearches']);

        return Inertia::render('lead-generation/coaching', [
            'contact' => $contact,
            'brief' => $brief,
            'score' => $score,
            'coaching_tips' => $this->getCoachingTips($contact, $score),
        ]);
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function getCoachingTips(Contact $contact, int $score): array
    {
        $tips = [];

        if ($score >= 70) {
            $tips[] = ['type' => 'action', 'text' => 'High-priority lead! Call within 1 hour for best conversion.'];
        } elseif ($score >= 40) {
            $tips[] = ['type' => 'info', 'text' => 'Warm lead — send a personalised property shortlist within 24h.'];
        } else {
            $tips[] = ['type' => 'info', 'text' => 'Nurture this lead with valuable content over the next 2 weeks.'];
        }

        if (empty($contact->last_contacted_at)) {
            $tips[] = ['type' => 'warning', 'text' => 'First contact not recorded yet — log your interaction after the call.'];
        }

        if ($contact->propertySearches?->isEmpty()) {
            $tips[] = ['type' => 'info', 'text' => 'Ask about their property preferences to build a search profile.'];
        }

        $tips[] = ['type' => 'script', 'text' => "Hi {$contact->first_name}, I noticed you registered interest in one of our properties. I'd love to help you find your perfect home — do you have 5 minutes for a quick chat?"];

        return $tips;
    }
}
