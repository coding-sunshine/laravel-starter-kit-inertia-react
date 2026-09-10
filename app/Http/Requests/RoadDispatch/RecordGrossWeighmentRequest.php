<?php

declare(strict_types=1);

namespace App\Http\Requests\RoadDispatch;

use Illuminate\Foundation\Http\FormRequest;

final class RecordGrossWeighmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view', $this->route('unload')) ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'gross_weight_mt' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Set default status if not provided
            if (!$this->has('weighment_status')) {
                $this->merge(['weighment_status' => 'PASS']);
            }
            
            $unload = $this->route('unload');
            $arrivalWeight = $unload?->vehicleArrival?->gross_weight;
            $grossWeight = (float) $this->input('gross_weight_mt');
            
            if ($arrivalWeight && $grossWeight != $arrivalWeight) {
                $tolerance = 0.01; // 0.01 MT tolerance
                $difference = abs($grossWeight - $arrivalWeight);
                
                if ($difference > $tolerance) {
                    // Add warning but don't fail validation
                    $warningMessage = "Warning: Gross weight ({$grossWeight} MT) does not match arrival weight ({$arrivalWeight} MT). Difference: {$difference} MT.";
                    
                    // Force status to FAIL when weights don't match
                    $this->merge(['weighment_status' => 'FAIL']);
                    
                    // Store warning for display
                    request()->session()->flash('weight_warning', $warningMessage);
                }
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'gross_weight_mt.required' => 'Gross weight is required.',
            'gross_weight_mt.numeric' => 'Gross weight must be a number.',
            'gross_weight_mt.min' => 'Gross weight must be at least 0.',
        ];
    }
}
