<?php

declare(strict_types=1);

namespace App\Http\Requests\RoadDispatch;

use Illuminate\Foundation\Http\FormRequest;

final class RecordTareWeighmentRequest extends FormRequest
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
            'tare_weight_mt' => [
                'required',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    $grossWeight = (float) $this->input('gross_weight_mt');
                    if ($grossWeight > 0 && $value >= $grossWeight) {
                        $fail("Tare weight ({$value} MT) must be less than gross weight ({$grossWeight} MT).");
                    }
                },
            ],
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
            $arrivalGrossWeight = $unload?->vehicleArrival?->gross_weight;
            $arrivalTareWeight = $unload?->vehicleArrival?->tare_weight;
            $grossWeight = (float) $this->input('gross_weight_mt');
            $tareWeight = (float) $this->input('tare_weight_mt');
            
            // Validate gross weight against arrival gross weight
            if ($arrivalGrossWeight && $grossWeight != $arrivalGrossWeight) {
                $tolerance = 0.01; // 0.01 MT tolerance
                $difference = abs($grossWeight - $arrivalGrossWeight);
                
                if ($difference > $tolerance) {
                    // Add warning but don't fail validation
                    $warningMessage = "Warning: Gross weight ({$grossWeight} MT) does not match arrival weight ({$arrivalGrossWeight} MT). Difference: {$difference} MT.";
                    
                    // Force status to FAIL when weights don't match
                    $this->merge(['weighment_status' => 'FAIL']);
                    
                    // Store warning for display
                    request()->session()->flash('weight_warning', $warningMessage);
                }
            }
            
            // Validate tare weight against arrival tare weight (if available)
            if ($arrivalTareWeight && $tareWeight != $arrivalTareWeight) {
                $tolerance = 0.01; // 0.01 MT tolerance
                $difference = abs($tareWeight - $arrivalTareWeight);
                
                if ($difference > $tolerance) {
                    // Add warning but don't fail validation
                    $warningMessage = "Warning: Tare weight ({$tareWeight} MT) does not arrival tare weight ({$arrivalTareWeight} MT). Difference: {$difference} MT.";
                    
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
            'tare_weight_mt.required' => 'Tare weight is required.',
            'tare_weight_mt.numeric' => 'Tare weight must be a number.',
            'tare_weight_mt.min' => 'Tare weight must be at least 0.',
        ];
    }
}
