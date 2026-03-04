<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\UpdateAlertPreferenceRequest;
use App\Models\Fleet\AlertPreference;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class AlertPreferenceController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AlertPreference::class);
        $orgId = TenantContext::id();
        $userId = $request->user()->id;
        $preferences = AlertPreference::query()
            ->where('user_id', $userId)
            ->where('organization_id', $orgId)
            ->orderBy('alert_type')
            ->get();

        return Inertia::render('Fleet/AlertPreferences/Index', [
            'alertPreferences' => $preferences,
        ]);
    }

    public function edit(AlertPreference $alert_preference): Response
    {
        $this->authorize('update', $alert_preference);

        return Inertia::render('Fleet/AlertPreferences/Edit', ['alertPreference' => $alert_preference]);
    }

    public function update(UpdateAlertPreferenceRequest $request, AlertPreference $alert_preference): RedirectResponse
    {
        $this->authorize('update', $alert_preference);
        $alert_preference->update($request->validated());

        return to_route('fleet.alert-preferences.index')->with('flash', ['status' => 'success', 'message' => 'Alert preference updated.']);
    }
}
