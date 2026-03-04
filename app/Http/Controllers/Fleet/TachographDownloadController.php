<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreTachographDownloadRequest;
use App\Http\Requests\Fleet\UpdateTachographDownloadRequest;
use App\Models\Fleet\TachographDownload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class TachographDownloadController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', TachographDownload::class);
        $downloads = TachographDownload::query()
            ->with('driver')
            ->when($request->input('driver_id'), fn ($q, $v) => $q->where('driver_id', $v))
            ->when($request->input('status'), fn ($q, $v) => $q->where('status', $v))
            ->latest('download_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/TachographDownloads/Index', [
            'tachographDownloads' => $downloads,
            'filters' => $request->only(['driver_id', 'status']),
            'drivers' => \App\Models\Fleet\Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
            'statuses' => array_map(fn (\App\Enums\Fleet\TachographDownloadStatus $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\TachographDownloadStatus::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', TachographDownload::class);

        return Inertia::render('Fleet/TachographDownloads/Create', [
            'drivers' => \App\Models\Fleet\Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
            'statuses' => array_map(fn (\App\Enums\Fleet\TachographDownloadStatus $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\TachographDownloadStatus::cases()),
        ]);
    }

    public function store(StoreTachographDownloadRequest $request): RedirectResponse
    {
        $this->authorize('create', TachographDownload::class);
        TachographDownload::query()->create($request->validated());

        return to_route('fleet.tachograph-downloads.index')->with('flash', ['status' => 'success', 'message' => 'Tachograph download created.']);
    }

    public function show(TachographDownload $tachograph_download): Response
    {
        $this->authorize('view', $tachograph_download);
        $tachograph_download->load('driver');

        return Inertia::render('Fleet/TachographDownloads/Show', ['tachographDownload' => $tachograph_download]);
    }

    public function edit(TachographDownload $tachograph_download): Response
    {
        $this->authorize('update', $tachograph_download);

        return Inertia::render('Fleet/TachographDownloads/Edit', [
            'tachographDownload' => $tachograph_download,
            'drivers' => \App\Models\Fleet\Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
            'statuses' => array_map(fn (\App\Enums\Fleet\TachographDownloadStatus $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\TachographDownloadStatus::cases()),
        ]);
    }

    public function update(UpdateTachographDownloadRequest $request, TachographDownload $tachograph_download): RedirectResponse
    {
        $this->authorize('update', $tachograph_download);
        $tachograph_download->update($request->validated());

        return to_route('fleet.tachograph-downloads.show', $tachograph_download)->with('flash', ['status' => 'success', 'message' => 'Tachograph download updated.']);
    }

    public function destroy(TachographDownload $tachograph_download): RedirectResponse
    {
        $this->authorize('delete', $tachograph_download);
        $tachograph_download->delete();

        return to_route('fleet.tachograph-downloads.index')->with('flash', ['status' => 'success', 'message' => 'Tachograph download deleted.']);
    }
}
