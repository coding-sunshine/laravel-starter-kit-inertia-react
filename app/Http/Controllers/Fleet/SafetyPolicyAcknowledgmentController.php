<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreSafetyPolicyAcknowledgmentRequest;
use App\Models\Fleet\Driver;
use App\Models\Fleet\SafetyPolicyAcknowledgment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class SafetyPolicyAcknowledgmentController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', SafetyPolicyAcknowledgment::class);
        $acknowledgments = SafetyPolicyAcknowledgment::query()
            ->with(['user', 'driver'])
            ->latest('acknowledged_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/SafetyPolicyAcknowledgments/Index', [
            'safetyPolicyAcknowledgments' => $acknowledgments,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', SafetyPolicyAcknowledgment::class);
        $users = User::query()->orderBy('name')->get(['id', 'name'])->map(fn ($u): array => ['id' => $u->id, 'name' => $u->name]);
        $drivers = Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name'])->map(fn ($d): array => ['id' => $d->id, 'name' => $d->first_name.' '.$d->last_name]);

        return Inertia::render('Fleet/SafetyPolicyAcknowledgments/Create', [
            'users' => $users,
            'drivers' => $drivers,
        ]);
    }

    public function store(StoreSafetyPolicyAcknowledgmentRequest $request): RedirectResponse
    {
        $this->authorize('create', SafetyPolicyAcknowledgment::class);
        SafetyPolicyAcknowledgment::query()->create($request->validated());

        return to_route('fleet.safety-policy-acknowledgments.index')->with('flash', ['status' => 'success', 'message' => 'Safety policy acknowledgment created.']);
    }
}
