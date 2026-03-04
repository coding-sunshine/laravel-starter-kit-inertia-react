<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Enums\Fleet\ToolboxTalkStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreToolboxTalkRequest;
use App\Http\Requests\Fleet\UpdateToolboxTalkRequest;
use App\Models\Fleet\Driver;
use App\Models\Fleet\ToolboxTalk;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ToolboxTalkController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ToolboxTalk::class);
        $talks = ToolboxTalk::query()
            ->with('presenter')
            ->when($request->input('status'), fn ($q, $v) => $q->where('status', $v))
            ->latest('scheduled_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/ToolboxTalks/Index', [
            'toolboxTalks' => $talks,
            'filters' => $request->only(['status']),
            'statuses' => array_map(fn (ToolboxTalkStatus $c): array => ['value' => $c->value, 'name' => $c->name], ToolboxTalkStatus::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', ToolboxTalk::class);
        $users = User::query()->orderBy('name')->get(['id', 'name'])->map(fn ($u): array => ['id' => $u->id, 'name' => $u->name]);
        $drivers = Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name'])->map(fn ($d): array => ['id' => $d->id, 'name' => $d->first_name.' '.$d->last_name]);

        return Inertia::render('Fleet/ToolboxTalks/Create', [
            'users' => $users,
            'drivers' => $drivers,
            'statuses' => array_map(fn (ToolboxTalkStatus $c): array => ['value' => $c->value, 'name' => $c->name], ToolboxTalkStatus::cases()),
        ]);
    }

    public function store(StoreToolboxTalkRequest $request): RedirectResponse
    {
        $this->authorize('create', ToolboxTalk::class);
        ToolboxTalk::query()->create($request->validated());

        return to_route('fleet.toolbox-talks.index')->with('flash', ['status' => 'success', 'message' => 'Toolbox talk created.']);
    }

    public function show(ToolboxTalk $toolbox_talk): Response
    {
        $this->authorize('view', $toolbox_talk);
        $toolbox_talk->load('presenter');

        return Inertia::render('Fleet/ToolboxTalks/Show', ['toolboxTalk' => $toolbox_talk]);
    }

    public function edit(ToolboxTalk $toolbox_talk): Response
    {
        $this->authorize('update', $toolbox_talk);
        $users = User::query()->orderBy('name')->get(['id', 'name'])->map(fn ($u): array => ['id' => $u->id, 'name' => $u->name]);
        $drivers = Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name'])->map(fn ($d): array => ['id' => $d->id, 'name' => $d->first_name.' '.$d->last_name]);

        return Inertia::render('Fleet/ToolboxTalks/Edit', [
            'toolboxTalk' => $toolbox_talk,
            'users' => $users,
            'drivers' => $drivers,
            'statuses' => array_map(fn (ToolboxTalkStatus $c): array => ['value' => $c->value, 'name' => $c->name], ToolboxTalkStatus::cases()),
        ]);
    }

    public function update(UpdateToolboxTalkRequest $request, ToolboxTalk $toolbox_talk): RedirectResponse
    {
        $this->authorize('update', $toolbox_talk);
        $toolbox_talk->update($request->validated());

        return to_route('fleet.toolbox-talks.show', $toolbox_talk)->with('flash', ['status' => 'success', 'message' => 'Toolbox talk updated.']);
    }

    public function destroy(ToolboxTalk $toolbox_talk): RedirectResponse
    {
        $this->authorize('delete', $toolbox_talk);
        $toolbox_talk->delete();

        return to_route('fleet.toolbox-talks.index')->with('flash', ['status' => 'success', 'message' => 'Toolbox talk deleted.']);
    }
}
