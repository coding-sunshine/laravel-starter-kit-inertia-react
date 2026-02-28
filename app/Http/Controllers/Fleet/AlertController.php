<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Fleet\Alert;
use App\Enums\Fleet\AlertSeverity;
use App\Enums\Fleet\AlertStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class AlertController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Alert::class);
        $alerts = Alert::query()
            ->when($request->input('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->input('severity'), fn ($q, $v) => $q->where('severity', $v))
            ->orderByDesc('triggered_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/Alerts/Index', [
            'alerts' => $alerts,
            'filters' => $request->only(['status', 'severity']),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], AlertStatus::cases()),
            'severities' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], AlertSeverity::cases()),
        ]);
    }

    public function show(Alert $alert): Response
    {
        $this->authorize('view', $alert);

        return Inertia::render('Fleet/Alerts/Show', ['alert' => $alert]);
    }

    public function acknowledge(Request $request, Alert $alert): RedirectResponse
    {
        $this->authorize('update', $alert);
        $alert->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
            'acknowledged_by' => $request->user()->id,
        ]);
        return redirect()->back()->with('flash', ['status' => 'success', 'message' => 'Alert acknowledged.']);
    }
}
