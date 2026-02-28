<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreApiIntegrationRequest;
use App\Http\Requests\Fleet\UpdateApiIntegrationRequest;
use App\Models\Fleet\ApiIntegration;
use App\Enums\Fleet\ApiIntegrationType;
use App\Enums\Fleet\ApiIntegrationSyncStatus;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class ApiIntegrationController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', ApiIntegration::class);
        $integrations = ApiIntegration::query()->orderBy('integration_name')->paginate(15)->withQueryString();

        return Inertia::render('Fleet/ApiIntegrations/Index', [
            'apiIntegrations' => $integrations,
            'integrationTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], ApiIntegrationType::cases()),
            'syncStatuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], ApiIntegrationSyncStatus::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', ApiIntegration::class);
        return Inertia::render('Fleet/ApiIntegrations/Create', [
            'integrationTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], ApiIntegrationType::cases()),
            'syncStatuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], ApiIntegrationSyncStatus::cases()),
        ]);
    }

    public function store(StoreApiIntegrationRequest $request): RedirectResponse
    {
        $this->authorize('create', ApiIntegration::class);
        ApiIntegration::create($request->validated());
        return to_route('fleet.api-integrations.index')->with('flash', ['status' => 'success', 'message' => 'API integration created.']);
    }

    public function show(ApiIntegration $api_integration): Response
    {
        $this->authorize('view', $api_integration);
        return Inertia::render('Fleet/ApiIntegrations/Show', ['apiIntegration' => $api_integration]);
    }

    public function edit(ApiIntegration $api_integration): Response
    {
        $this->authorize('update', $api_integration);
        return Inertia::render('Fleet/ApiIntegrations/Edit', [
            'apiIntegration' => $api_integration,
            'integrationTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], ApiIntegrationType::cases()),
            'syncStatuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], ApiIntegrationSyncStatus::cases()),
        ]);
    }

    public function update(UpdateApiIntegrationRequest $request, ApiIntegration $api_integration): RedirectResponse
    {
        $this->authorize('update', $api_integration);
        $api_integration->update($request->validated());
        return to_route('fleet.api-integrations.show', $api_integration)->with('flash', ['status' => 'success', 'message' => 'API integration updated.']);
    }

    public function destroy(ApiIntegration $api_integration): RedirectResponse
    {
        $this->authorize('delete', $api_integration);
        $api_integration->delete();
        return to_route('fleet.api-integrations.index')->with('flash', ['status' => 'success', 'message' => 'API integration deleted.']);
    }
}
