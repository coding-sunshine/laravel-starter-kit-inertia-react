<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\DestroyTransportRegistrationMediaRequest;
use App\Http\Requests\DestroyTransportWorkOrderRegistrationRequest;
use App\Http\Requests\StoreTransportWorkOrderRegistrationRequest;
use App\Http\Requests\UpdateTransportWorkOrderRegistrationRequest;
use App\Models\Siding;
use App\Models\TransportWorkOrderRegistration;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class TransportWorkOrderRegistrationController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('TransportWorkOrderRegistrations/Create', [
            'sidings' => $this->sidingsForRegistrationForms(),
        ]);
    }

    public function store(StoreTransportWorkOrderRegistrationRequest $request): RedirectResponse
    {
        $validated = Arr::except($request->validated(), [
            'pan_documents',
            'gst_documents',
            'transporter_documents',
        ]);

        /** @var TransportWorkOrderRegistration $registration */
        $registration = TransportWorkOrderRegistration::query()->create($validated);

        $this->attachUploadedMedia($registration, $request);

        return redirect()
            ->route('vehicle-workorders.index', ['view' => 'transporters'])
            ->with('success', 'Transport work order registration created.');
    }

    public function edit(TransportWorkOrderRegistration $tw_registration): Response|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->hasPermissionTo('sections.transport.update')) {
            abort(403, 'You do not have access to edit transporter registrations.');
        }

        $tw_registration->load('siding:id,name,code');

        return Inertia::render('TransportWorkOrderRegistrations/Edit', [
            'registration' => $this->registrationPayload($tw_registration),
            'sidings' => $this->sidingsForRegistrationForms(),
            'permissions' => [
                'canUpdate' => $user->hasPermissionTo('sections.transport.update'),
            ],
        ]);
    }

    public function update(
        UpdateTransportWorkOrderRegistrationRequest $request,
        TransportWorkOrderRegistration $tw_registration,
    ): RedirectResponse {
        $validated = Arr::except($request->validated(), [
            'pan_documents',
            'gst_documents',
            'transporter_documents',
        ]);

        $tw_registration->update($validated);

        $fresh = $tw_registration->fresh();
        if ($fresh !== null) {
            $this->attachUploadedMedia($fresh, $request);
        }

        return redirect()
            ->route('vehicle-workorders.index', ['view' => 'transporters'])
            ->with('success', 'Transport work order registration updated.');
    }

    public function destroy(
        DestroyTransportWorkOrderRegistrationRequest $request,
        TransportWorkOrderRegistration $tw_registration,
    ): RedirectResponse {
        $tw_registration->delete();

        return redirect()
            ->route('vehicle-workorders.index', ['view' => 'transporters'])
            ->with('success', 'Transport work order registration deleted.');
    }

    public function destroyMedia(
        DestroyTransportRegistrationMediaRequest $request,
        TransportWorkOrderRegistration $tw_registration,
        Media $media,
    ): RedirectResponse {
        $allowedCollections = ['pan_documents', 'gst_documents', 'transporter_documents'];

        /** @var Media|null $owned */
        $owned = $tw_registration->media()
            ->whereKey($media->getKey())
            ->whereIn('collection_name', $allowedCollections)
            ->first();

        abort_if($owned === null, 404);

        $owned->delete();

        return redirect()
            ->route('vehicle-workorders.transport-registrations.edit', ['tw_registration' => $tw_registration])
            ->with('success', 'Attached file removed.');
    }

    /**
     * Full siding list for create/edit (not limited by the user's assigned sidings) so records can be pointed at any siding.
     *
     * @return Collection<int, Siding>
     */
    private function sidingsForRegistrationForms(): Collection
    {
        return Siding::query()
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    /**
     * @return array<string, mixed>
     */
    private function registrationPayload(TransportWorkOrderRegistration $registration): array
    {
        $collections = ['pan_documents', 'gst_documents', 'transporter_documents'];
        $media = [];
        foreach ($collections as $name) {
            $media[$name] = $registration->getMedia($name)->map(function ($m): array {
                return [
                    'id' => $m->id,
                    'name' => $m->name,
                    'file_name' => $m->file_name,
                    'url' => $m->getUrl(),
                ];
            })->values()->all();
        }

        return array_merge($registration->toArray(), ['media' => $media]);
    }

    private function attachUploadedMedia(TransportWorkOrderRegistration $registration, StoreTransportWorkOrderRegistrationRequest|UpdateTransportWorkOrderRegistrationRequest $request): void
    {
        foreach ($request->file('pan_documents', []) as $file) {
            $registration->addMedia($file)->toMediaCollection('pan_documents');
        }
        foreach ($request->file('gst_documents', []) as $file) {
            $registration->addMedia($file)->toMediaCollection('gst_documents');
        }
        foreach ($request->file('transporter_documents', []) as $file) {
            $registration->addMedia($file)->toMediaCollection('transporter_documents');
        }
    }
}
