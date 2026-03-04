<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Enums\Fleet\TrainingSessionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreTrainingSessionRequest;
use App\Http\Requests\Fleet\UpdateTrainingSessionRequest;
use App\Models\Fleet\TrainingCourse;
use App\Models\Fleet\TrainingSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class TrainingSessionController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', TrainingSession::class);
        $sessions = TrainingSession::query()
            ->with('trainingCourse')
            ->when($request->input('training_course_id'), fn ($q, $v) => $q->where('training_course_id', $v))
            ->when($request->input('status'), fn ($q, $v) => $q->where('status', $v))
            ->latest('scheduled_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/TrainingSessions/Index', [
            'trainingSessions' => $sessions,
            'filters' => $request->only(['training_course_id', 'status']),
            'courses' => TrainingCourse::query()->orderBy('course_name')->get(['id', 'course_name']),
            'statuses' => array_map(fn (TrainingSessionStatus $c): array => ['value' => $c->value, 'name' => $c->name], TrainingSessionStatus::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', TrainingSession::class);

        return Inertia::render('Fleet/TrainingSessions/Create', [
            'courses' => TrainingCourse::query()->orderBy('course_name')->get(['id', 'course_name']),
            'statuses' => array_map(fn (TrainingSessionStatus $c): array => ['value' => $c->value, 'name' => $c->name], TrainingSessionStatus::cases()),
        ]);
    }

    public function store(StoreTrainingSessionRequest $request): RedirectResponse
    {
        $this->authorize('create', TrainingSession::class);
        TrainingSession::query()->create($request->validated());

        return to_route('fleet.training-sessions.index')->with('flash', ['status' => 'success', 'message' => 'Training session created.']);
    }

    public function show(TrainingSession $training_session): Response
    {
        $this->authorize('view', $training_session);
        $training_session->load(['trainingCourse', 'enrollments.driver']);

        return Inertia::render('Fleet/TrainingSessions/Show', ['trainingSession' => $training_session]);
    }

    public function edit(TrainingSession $training_session): Response
    {
        $this->authorize('update', $training_session);

        return Inertia::render('Fleet/TrainingSessions/Edit', [
            'trainingSession' => $training_session,
            'courses' => TrainingCourse::query()->orderBy('course_name')->get(['id', 'course_name']),
            'statuses' => array_map(fn (TrainingSessionStatus $c): array => ['value' => $c->value, 'name' => $c->name], TrainingSessionStatus::cases()),
        ]);
    }

    public function update(UpdateTrainingSessionRequest $request, TrainingSession $training_session): RedirectResponse
    {
        $this->authorize('update', $training_session);
        $training_session->update($request->validated());

        return to_route('fleet.training-sessions.show', $training_session)->with('flash', ['status' => 'success', 'message' => 'Training session updated.']);
    }

    public function destroy(TrainingSession $training_session): RedirectResponse
    {
        $this->authorize('delete', $training_session);
        $training_session->delete();

        return to_route('fleet.training-sessions.index')->with('flash', ['status' => 'success', 'message' => 'Training session deleted.']);
    }
}
