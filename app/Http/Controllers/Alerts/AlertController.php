<?php

declare(strict_types=1);

namespace App\Http\Controllers\Alerts;

use App\DataTables\AlertDataTable;
use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Siding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class AlertController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('alerts/index', [
            'tableData' => AlertDataTable::makeTable($request),
            'sidings' => $sidings,
        ]);
    }

    public function resolve(Request $request, Alert $alert): RedirectResponse
    {
        $user = $request->user();
        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();
        abort_if($alert->siding_id !== null && ! in_array($alert->siding_id, $sidingIds, true), 403);

        $alert->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => $user->id,
        ]);

        $redirect = $request->input('redirect', route('dashboard'));

        return redirect()->to($redirect)->with('success', 'Alert resolved.');
    }
}
