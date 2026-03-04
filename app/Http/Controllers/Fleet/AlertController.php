<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Enums\Fleet\AlertSeverity;
use App\Enums\Fleet\AlertStatus;
use App\Enums\Fleet\AlertType;
use App\Http\Controllers\Controller;
use App\Models\Fleet\Alert;
use App\Models\Fleet\Driver;
use App\Models\Fleet\Vehicle;
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
            ->when($request->input('alert_type'), fn ($q, $v) => $q->where('alert_type', $v))
            ->latest('triggered_at')
            ->paginate(15)
            ->withQueryString();

        $items = $alerts->getCollection()->map(function (Alert $alert): array {
            $entityLabel = $this->resolveEntityLabel($alert);

            return [
                'id' => $alert->id,
                'title' => $alert->title,
                'alert_type' => $alert->alert_type,
                'severity' => $alert->severity,
                'status' => $alert->status,
                'triggered_at' => $alert->triggered_at->toIso8601String(),
                'entity_type' => $alert->entity_type,
                'entity_id' => $alert->entity_id,
                'entity_label' => $entityLabel,
            ];
        });
        $alerts->setCollection($items);

        return Inertia::render('Fleet/Alerts/Index', [
            'alerts' => $alerts,
            'filters' => $request->only(['status', 'severity', 'alert_type']),
            'statuses' => array_map(fn (AlertStatus $c): array => ['value' => $c->value, 'name' => $c->name], AlertStatus::cases()),
            'severities' => array_map(fn (AlertSeverity $c): array => ['value' => $c->value, 'name' => $c->name], AlertSeverity::cases()),
            'alertTypes' => array_map(fn (AlertType $c): array => ['value' => $c->value, 'name' => $c->name], AlertType::cases()),
        ]);
    }

    public function show(Alert $alert): Response
    {
        $this->authorize('view', $alert);

        return Inertia::render('Fleet/Alerts/Show', ['alert' => $alert]);
    }

    public function bulkAcknowledge(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', Alert::class);
        $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer', 'min:1']]);
        $ids = array_values(array_unique($request->input('ids', [])));
        $count = 0;
        foreach ($ids as $id) {
            $alert = Alert::query()->find($id);
            if ($alert !== null && $request->user()->can('update', $alert) && $alert->status === 'active') {
                $alert->update([
                    'status' => 'acknowledged',
                    'acknowledged_at' => now(),
                    'acknowledged_by' => $request->user()->id,
                ]);
                $count++;
            }
        }

        return back()->with('flash', ['status' => 'success', 'message' => "{$count} alert(s) acknowledged."]);
    }

    public function acknowledge(Request $request, Alert $alert): RedirectResponse
    {
        $this->authorize('update', $alert);
        $alert->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
            'acknowledged_by' => $request->user()->id,
        ]);

        return back()->with('flash', ['status' => 'success', 'message' => 'Alert acknowledged.']);
    }

    private function resolveEntityLabel(Alert $alert): ?string
    {
        if (! $alert->entity_type || ! $alert->entity_id) {
            return null;
        }
        $type = $alert->entity_type;
        $id = $alert->entity_id;
        if (str_ends_with($type, 'Vehicle')) {
            $v = Vehicle::query()->find($id);

            return $v ? "Vehicle · {$v->registration}" : null;
        }
        if (str_ends_with($type, 'Driver')) {
            $d = Driver::query()->find($id);

            return $d ? 'Driver · '.mb_trim($d->first_name.' '.$d->last_name) : null;
        }

        return $type.' #'.$id;
    }
}
