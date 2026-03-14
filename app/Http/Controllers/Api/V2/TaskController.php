<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Http\Resources\Api\V2\TaskResource;
use App\Models\Task;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final class TaskController extends BaseApiController
{
    /**
     * List tasks. Supports filter, sort, and pagination.
     *
     * Query params: filter[status], filter[type], filter[assigned_to_user_id], sort, per_page
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $tasks = QueryBuilder::for(Task::class)
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('type'),
                AllowedFilter::exact('priority'),
                AllowedFilter::exact('assigned_to_user_id'),
                AllowedFilter::exact('assigned_contact_id'),
            ])
            ->allowedSorts(['id', 'title', 'status', 'due_at', 'created_at'])
            ->where('organization_id', TenantContext::id())
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return TaskResource::collection($tasks);
    }

    /**
     * Show a single task.
     */
    public function show(Task $task): JsonResponse
    {
        return $this->responseSuccess(null, new TaskResource($task));
    }

    /**
     * Create a task.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'priority' => ['nullable', 'string'],
            'due_at' => ['nullable', 'date'],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'assigned_contact_id' => ['nullable', 'integer', 'exists:contacts,id'],
            'description' => ['nullable', 'string'],
        ]);

        $task = Task::query()->create([
            ...$validated,
            'organization_id' => TenantContext::id(),
        ]);

        return $this->responseCreated('Task created.', new TaskResource($task));
    }

    /**
     * Update a task.
     */
    public function update(Request $request, Task $task): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'string'],
            'priority' => ['sometimes', 'string'],
            'due_at' => ['sometimes', 'nullable', 'date'],
            'assigned_to_user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'is_completed' => ['sometimes', 'boolean'],
        ]);

        $task->update($validated);

        return $this->responseSuccess(null, new TaskResource($task->fresh()));
    }
}
