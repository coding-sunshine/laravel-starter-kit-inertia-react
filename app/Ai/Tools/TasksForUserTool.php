<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\Task;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * AI tool: get tasks for the current user for C1 TaskChecklist rendering.
 */
final class TasksForUserTool implements Tool
{
    public function description(): string
    {
        return 'Get tasks for a user — today\'s tasks, overdue tasks, or upcoming tasks. Returns TaskChecklist data for C1 rendering.';
    }

    public function handle(Request $request): Stringable|string
    {
        $userId = (int) ($request->input['user_id'] ?? auth()->id());
        $filter = (string) ($request->input['filter'] ?? 'today');
        $contactId = $request->input['contact_id'] ?? null;
        $limit = (int) ($request->input['limit'] ?? 10);

        $q = Task::query()
            ->with(['assignedUser', 'contact'])
            ->where('assigned_to', $userId)
            ->limit(min($limit, 50));

        if ($contactId !== null) {
            $q->where('contact_id', (int) $contactId);
        }

        $now = Carbon::now();
        match ($filter) {
            'overdue' => $q->where('due_at', '<', $now)->where('is_completed', false),
            'today' => $q->whereDate('due_at', $now->toDateString())->where('is_completed', false),
            'upcoming' => $q->where('due_at', '>', $now)->where('is_completed', false),
            default => null,
        };

        $tasks = $q->orderBy('due_at')->get();

        $items = $tasks->map(fn (Task $t) => [
            'id' => $t->id,
            'title' => $t->title,
            'due_at' => $t->due_at?->toIso8601String(),
            'priority' => $t->priority,
            'type' => $t->type,
            'is_completed' => (bool) $t->is_completed,
            'assigned_to' => $t->assignedUser ? ['id' => $t->assignedUser->id, 'name' => $t->assignedUser->name] : null,
            'contact' => $t->contact ? ['id' => $t->contact->id, 'name' => "{$t->contact->first_name} {$t->contact->last_name}", 'href' => "/contacts/{$t->contact->id}"] : null,
            'actions' => [
                ['label' => 'Mark Done', 'type' => 'complete', 'href' => null],
                ['label' => 'Assign to Me', 'type' => 'assign', 'href' => null],
            ],
        ])->all();

        $title = match ($filter) {
            'overdue' => 'Overdue Tasks',
            'today' => 'Your Tasks for Today',
            'upcoming' => 'Upcoming Tasks',
            default => 'Tasks',
        };

        $result = [
            'component' => 'TaskChecklist',
            'props' => [
                'title' => $title,
                'tasks' => $items,
                'show_contact' => true,
                'actions' => [
                    ['label' => 'Create Task', 'type' => 'action', 'href' => '/tasks?create=1'],
                    ['label' => 'View All Tasks', 'type' => 'link', 'href' => '/tasks'],
                ],
            ],
        ];

        return Str::of(json_encode($result) ?: '{}');
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'user_id' => $schema->integer()->description('User ID to fetch tasks for (defaults to current user)'),
            'filter' => $schema->string()->description('Which tasks: today, overdue, upcoming (default: today)'),
            'contact_id' => $schema->integer()->description('Filter tasks for a specific contact'),
            'limit' => $schema->integer()->description('Max results (default 10)'),
        ];
    }
}
