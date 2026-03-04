<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Enums\Fleet\TrainingEnrollmentStatus;
use App\Enums\Fleet\TrainingPassFail;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreTrainingEnrollmentRequest;
use App\Http\Requests\Fleet\UpdateTrainingEnrollmentRequest;
use App\Models\Fleet\Driver;
use App\Models\Fleet\TrainingEnrollment;
use App\Models\Fleet\TrainingSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class TrainingEnrollmentController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', TrainingEnrollment::class);
        $enrollments = TrainingEnrollment::query()
            ->with(['trainingSession.trainingCourse', 'driver', 'enrolledBy'])
            ->when($request->input('training_session_id'), fn ($q, $v) => $q->where('training_session_id', $v))
            ->when($request->input('driver_id'), fn ($q, $v) => $q->where('driver_id', $v))
            ->when($request->input('enrollment_status'), fn ($q, $v) => $q->where('enrollment_status', $v))
            ->latest('enrollment_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/TrainingEnrollments/Index', [
            'trainingEnrollments' => $enrollments,
            'filters' => $request->only(['training_session_id', 'driver_id', 'enrollment_status']),
            'trainingSessions' => TrainingSession::query()->with('trainingCourse')->latest('scheduled_date')->get(['id', 'session_name', 'scheduled_date', 'training_course_id'])->map(fn ($s): array => ['id' => $s->id, 'name' => $s->session_name.' ('.$s->scheduled_date->format('Y-m-d').')']),
            'drivers' => Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name'])->map(fn ($d): array => ['id' => $d->id, 'name' => $d->first_name.' '.$d->last_name]),
            'enrollmentStatuses' => array_map(fn (TrainingEnrollmentStatus $c): array => ['value' => $c->value, 'name' => $c->name], TrainingEnrollmentStatus::cases()),
            'passFailOptions' => array_map(fn (TrainingPassFail $c): array => ['value' => $c->value, 'name' => $c->name], TrainingPassFail::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', TrainingEnrollment::class);

        return Inertia::render('Fleet/TrainingEnrollments/Create', [
            'trainingSessions' => TrainingSession::query()->with('trainingCourse')->latest('scheduled_date')->get(['id', 'session_name', 'scheduled_date'])->map(fn ($s): array => ['id' => $s->id, 'name' => $s->session_name.' ('.$s->scheduled_date->format('Y-m-d').')']),
            'drivers' => Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name'])->map(fn ($d): array => ['id' => $d->id, 'name' => $d->first_name.' '.$d->last_name]),
            'enrollmentStatuses' => array_map(fn (TrainingEnrollmentStatus $c): array => ['value' => $c->value, 'name' => $c->name], TrainingEnrollmentStatus::cases()),
            'passFailOptions' => array_map(fn (TrainingPassFail $c): array => ['value' => $c->value, 'name' => $c->name], TrainingPassFail::cases()),
        ]);
    }

    public function store(StoreTrainingEnrollmentRequest $request): RedirectResponse
    {
        $this->authorize('create', TrainingEnrollment::class);
        TrainingEnrollment::query()->create(array_merge($request->validated(), ['enrolled_by' => $request->user()->id]));

        return to_route('fleet.training-enrollments.index')->with('flash', ['status' => 'success', 'message' => 'Training enrollment created.']);
    }

    public function show(TrainingEnrollment $training_enrollment): Response
    {
        $this->authorize('view', $training_enrollment);
        $training_enrollment->load(['trainingSession.trainingCourse', 'driver', 'enrolledBy']);

        return Inertia::render('Fleet/TrainingEnrollments/Show', ['trainingEnrollment' => $training_enrollment]);
    }

    public function edit(TrainingEnrollment $training_enrollment): Response
    {
        $this->authorize('update', $training_enrollment);

        return Inertia::render('Fleet/TrainingEnrollments/Edit', [
            'trainingEnrollment' => $training_enrollment,
            'trainingSessions' => TrainingSession::query()->with('trainingCourse')->latest('scheduled_date')->get(['id', 'session_name', 'scheduled_date'])->map(fn ($s): array => ['id' => $s->id, 'name' => $s->session_name.' ('.$s->scheduled_date->format('Y-m-d').')']),
            'drivers' => Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name'])->map(fn ($d): array => ['id' => $d->id, 'name' => $d->first_name.' '.$d->last_name]),
            'enrollmentStatuses' => array_map(fn (TrainingEnrollmentStatus $c): array => ['value' => $c->value, 'name' => $c->name], TrainingEnrollmentStatus::cases()),
            'passFailOptions' => array_map(fn (TrainingPassFail $c): array => ['value' => $c->value, 'name' => $c->name], TrainingPassFail::cases()),
        ]);
    }

    public function update(UpdateTrainingEnrollmentRequest $request, TrainingEnrollment $training_enrollment): RedirectResponse
    {
        $this->authorize('update', $training_enrollment);
        $training_enrollment->update($request->validated());

        return to_route('fleet.training-enrollments.show', $training_enrollment)->with('flash', ['status' => 'success', 'message' => 'Training enrollment updated.']);
    }

    public function destroy(TrainingEnrollment $training_enrollment): RedirectResponse
    {
        $this->authorize('delete', $training_enrollment);
        $training_enrollment->delete();

        return to_route('fleet.training-enrollments.index')->with('flash', ['status' => 'success', 'message' => 'Training enrollment deleted.']);
    }
}
