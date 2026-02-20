<?php

declare(strict_types=1);

namespace App\Http\Controllers\Alerts;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Siding;
use App\Services\SidingContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class AlertController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $sidingIds = SidingContext::activeSidingIds($user);

        $query = Alert::query()
            ->with('rake:id,rake_number', 'siding:id,name,code')
            ->forSidings($sidingIds)
            ->latest('created_at');

        if ($request->filled('status')) {
            if ($request->input('status') === 'active') {
                $query->active();
            } elseif ($request->input('status') === 'resolved') {
                $query->where('status', 'resolved');
            }
        }
        if ($request->filled('siding_id')) {
            $query->forSiding((int) $request->input('siding_id'));
        }

        $alerts = $query->paginate(20)->withQueryString();
        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('alerts/index', [
            'alerts' => $alerts,
            'sidings' => $sidings,
        ]);
    }

    public function resolve(Request $request, Alert $alert): RedirectResponse
    {
        $user = $request->user();
        $sidingIds = SidingContext::activeSidingIds($user);
        if ($alert->siding_id !== null && ! in_array($alert->siding_id, $sidingIds, true)) {
            abort(403);
        }

        $alert->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => $user->id,
        ]);

        $redirect = $request->input('redirect', route('dashboard'));

        return redirect()->to($redirect)->with('success', 'Alert resolved.');
    }
}
