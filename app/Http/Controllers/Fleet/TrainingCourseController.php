<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreTrainingCourseRequest;
use App\Http\Requests\Fleet\UpdateTrainingCourseRequest;
use App\Models\Fleet\TrainingCourse;
use App\Enums\Fleet\TrainingCourseCategory;
use App\Enums\Fleet\TrainingDeliveryMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class TrainingCourseController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', TrainingCourse::class);
        $courses = TrainingCourse::query()
            ->when($request->input('category'), fn ($q, $v) => $q->where('category', $v))
            ->when($request->input('delivery_method'), fn ($q, $v) => $q->where('delivery_method', $v))
            ->when($request->boolean('is_active'), fn ($q) => $q->where('is_active', true))
            ->orderBy('course_name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/TrainingCourses/Index', [
            'trainingCourses' => $courses,
            'filters' => $request->only(['category', 'delivery_method', 'is_active']),
            'categories' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], TrainingCourseCategory::cases()),
            'deliveryMethods' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], TrainingDeliveryMethod::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', TrainingCourse::class);
        return Inertia::render('Fleet/TrainingCourses/Create', [
            'categories' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], TrainingCourseCategory::cases()),
            'deliveryMethods' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], TrainingDeliveryMethod::cases()),
        ]);
    }

    public function store(StoreTrainingCourseRequest $request): RedirectResponse
    {
        $this->authorize('create', TrainingCourse::class);
        TrainingCourse::create($request->validated());
        return to_route('fleet.training-courses.index')->with('flash', ['status' => 'success', 'message' => 'Training course created.']);
    }

    public function show(TrainingCourse $training_course): Response
    {
        $this->authorize('view', $training_course);
        $training_course->load('trainingSessions');

        return Inertia::render('Fleet/TrainingCourses/Show', ['trainingCourse' => $training_course]);
    }

    public function edit(TrainingCourse $training_course): Response
    {
        $this->authorize('update', $training_course);
        return Inertia::render('Fleet/TrainingCourses/Edit', [
            'trainingCourse' => $training_course,
            'categories' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], TrainingCourseCategory::cases()),
            'deliveryMethods' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], TrainingDeliveryMethod::cases()),
        ]);
    }

    public function update(UpdateTrainingCourseRequest $request, TrainingCourse $training_course): RedirectResponse
    {
        $this->authorize('update', $training_course);
        $training_course->update($request->validated());
        return to_route('fleet.training-courses.show', $training_course)->with('flash', ['status' => 'success', 'message' => 'Training course updated.']);
    }

    public function destroy(TrainingCourse $training_course): RedirectResponse
    {
        $this->authorize('delete', $training_course);
        $training_course->delete();
        return to_route('fleet.training-courses.index')->with('flash', ['status' => 'success', 'message' => 'Training course deleted.']);
    }
}
