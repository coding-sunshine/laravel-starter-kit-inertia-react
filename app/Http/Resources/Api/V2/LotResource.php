<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class LotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'title' => $this->title,
            'price' => $this->price,
            'title_status' => $this->title_status,
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'is_archived' => $this->is_archived,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
