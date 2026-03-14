<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PushHistory;
use App\Models\PushSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class AgentPortalController extends Controller
{
    public function index(): Response
    {
        $orgId = auth()->user()?->currentOrganization?->id;

        $pushHistory = PushHistory::query()
            ->when($orgId, fn ($q) => $q->where('organization_id', $orgId))
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $pushSchedules = PushSchedule::query()
            ->when($orgId, fn ($q) => $q->where('organization_id', $orgId))
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at')
            ->get();

        return Inertia::render('agent-portal/index', [
            'push_history' => $pushHistory,
            'push_schedules' => $pushSchedules,
            'channels' => ['php', 'wordpress', 'rea', 'domain', 'homely'],
        ]);
    }

    public function schedule(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'pushable_type' => ['required', 'string', 'in:lot,project'],
            'pushable_id' => ['required', 'integer'],
            'channel' => ['required', 'string'],
            'scheduled_at' => ['required', 'date'],
        ]);

        $orgId = auth()->user()?->currentOrganization?->id;

        PushSchedule::query()->create([
            ...$validated,
            'organization_id' => $orgId,
            'user_id' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Push scheduled successfully.');
    }
}
