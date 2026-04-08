<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateManualRakeWeighmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'total_net_weight_mt' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'from_station' => ['nullable', 'string', 'max:255'],
            'to_station' => ['nullable', 'string', 'max:255'],
            'priority_number' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array{total_net_weight_mt: float, from_station: string|null, to_station: string|null, priority_number: string|null}
     */
    public function updatePayload(): array
    {
        $validated = $this->validated();

        $emptyToNull = static function (mixed $v): ?string {
            if ($v === null || $v === '') {
                return null;
            }
            $t = mb_trim((string) $v);

            return $t === '' ? null : $t;
        };

        return [
            'total_net_weight_mt' => (float) $validated['total_net_weight_mt'],
            'from_station' => $emptyToNull($validated['from_station'] ?? null),
            'to_station' => $emptyToNull($validated['to_station'] ?? null),
            'priority_number' => $emptyToNull($validated['priority_number'] ?? null),
        ];
    }
}
