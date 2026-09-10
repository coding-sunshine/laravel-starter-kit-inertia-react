<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Penalty;
use Illuminate\Foundation\Http\FormRequest;

final class UpdatePenaltyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $penalty = $this->route('penalty');

        if (! $penalty instanceof Penalty) {
            return false;
        }

        $user = $this->user();
        if ($user?->isSuperAdmin()) {
            return true;
        }

        $sidingId = $penalty->rake?->siding_id;

        return $sidingId !== null && ($user?->canAccessSiding($sidingId) ?? false);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'responsible_party' => ['nullable', 'string', 'in:railway,siding,transporter,plant,other'],
            'root_cause' => ['nullable', 'string', 'max:2000'],
            'dispute_reason' => ['nullable', 'string', 'max:2000'],
            'resolution_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
