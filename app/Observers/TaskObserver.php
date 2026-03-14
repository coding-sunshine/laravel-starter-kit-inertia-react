<?php

declare(strict_types=1);

namespace App\Observers;

use App\Actions\UpdateLastContactedAtAction;
use App\Models\Task;

final class TaskObserver
{
    public function __construct(private readonly UpdateLastContactedAtAction $updateLastContactedAt)
    {
        //
    }

    public function saved(Task $task): void
    {
        if (! $task->wasChanged('status')) {
            return;
        }

        if ($task->status !== Task::STATUS_DONE) {
            return;
        }

        $contactTypes = [
            Task::TYPE_CALL,
            Task::TYPE_EMAIL,
            Task::TYPE_MEETING,
            Task::TYPE_FOLLOW_UP,
        ];

        if (! in_array($task->type, $contactTypes, true)) {
            return;
        }

        $contact = $task->assignedContact ?? $task->attachedContact;

        if ($contact === null) {
            return;
        }

        $this->updateLastContactedAt->handle($contact, 'task_completed');
    }
}
