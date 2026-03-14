<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => $this->type,
            'status' => $this->status,
            'priority' => $this->priority,
            'due_at' => $this->due_at?->toIso8601String(),
            'assigned_to_user_id' => $this->assigned_to_user_id,
            'assigned_contact_id' => $this->assigned_contact_id,
            'is_completed' => $this->is_completed,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
