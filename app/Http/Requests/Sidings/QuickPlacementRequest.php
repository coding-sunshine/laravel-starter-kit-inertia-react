<?php

declare(strict_types=1);

namespace App\Http\Requests\Sidings;

use App\Models\Siding;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class QuickPlacementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $siding = $this->route('siding');
        if (! $siding instanceof Siding) {
            return false;
        }

        return $this->user()
            ?->sidings()
            ->whereKey($siding->id)
            ->exists() ?? false;
    }

    /**
     * @return array<string, array<int, string|ValidationRule>>
     */
    public function rules(): array
    {
        $siding = $this->route('siding');
        $sidingId = $siding instanceof Siding ? $siding->id : null;

        return [
            'rake_id' => [
                'required',
                Rule::exists('rakes', 'id')->where(fn (Builder $q) => $q->where('siding_id', $sidingId)),
            ],
            'event' => ['required', Rule::in(['placed', 'released'])],
            'occurred_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
