<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\VehicleWorkorder;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateVehicleWorkorderRequest extends FormRequest
{
    private const int MAX_UPLOAD_KB = 20480;

    /**
     * @var list<string>
     */
    private const array UPLOAD_MIMES = [
        'pdf',
        'jpeg',
        'jpg',
        'png',
        'webp',
        'doc',
        'docx',
    ];

    public function authorize(): bool
    {
        $workorder = $this->route('vehicle_workorder');

        if (! $workorder instanceof VehicleWorkorder) {
            return false;
        }

        $user = $this->user();
        if ($user === null) {
            return false;
        }

        if (! $user->canAccessSiding((int) $workorder->siding_id)) {
            return false;
        }

        $targetSidingId = (int) $this->input('siding_id', $workorder->siding_id);

        return $user->canAccessSiding($targetSidingId);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $mimes = implode(',', self::UPLOAD_MIMES);
        $fileRule = [
            'nullable',
            'file',
            'max:'.self::MAX_UPLOAD_KB,
            'mimes:'.$mimes,
        ];

        return [
            'siding_id' => ['required', 'integer', 'exists:sidings,id'],
            'vehicle_no' => ['required', 'string', 'max:50'],
            'rcd_pin_no' => ['required', 'string', 'max:50'],
            'transport_name' => ['required', 'string', 'max:255'],
            'wo_no' => ['nullable', 'string', 'max:100'],
            'wo_no_2' => ['required', 'string', 'max:100'],
            'work_order_date' => ['required', 'date'],
            'issued_date' => ['nullable', 'date'],
            'proprietor_name' => ['nullable', 'string', 'max:255'],
            'represented_by' => ['nullable', 'string', 'max:255'],
            'place' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'tyres' => ['required', 'integer', 'min:1', 'max:100'],
            'tare_weight' => ['required', 'numeric', 'min:0'],
            'mobile_no_1' => ['nullable', 'string', 'max:20'],
            'mobile_no_2' => ['nullable', 'string', 'max:20'],
            'owner_type' => ['nullable', 'string', 'max:50'],
            'regd_date' => ['nullable', 'date'],
            'permit_validity_date' => ['nullable', 'date'],
            'tax_validity_date' => ['nullable', 'date'],
            'fitness_validity_date' => ['nullable', 'date'],
            'insurance_validity_date' => ['nullable', 'date'],
            'maker_model' => ['nullable', 'string', 'max:100'],
            'make' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string'],
            'recommended_by' => ['nullable', 'string', 'max:255'],
            'referenced' => ['nullable', 'string', 'max:255'],
            'local_or_non_local' => ['nullable', 'string', 'max:50'],
            'pan_no' => ['nullable', 'string', 'max:50'],
            'gst_no' => ['nullable', 'string', 'max:50'],
            'vehicle_rc_certificate' => $fileRule,
            'vehicle_insurance_certificate' => $fileRule,
            'vehicle_other_documents' => ['nullable', 'array', 'max:20'],
            'vehicle_other_documents.*' => ['file', 'max:'.self::MAX_UPLOAD_KB, 'mimes:'.$mimes],
        ];
    }

    protected function prepareForValidation(): void
    {
        $nullable = [
            'wo_no',
            'issued_date',
            'proprietor_name',
            'represented_by',
            'place',
            'address',
            'mobile_no_1',
            'mobile_no_2',
            'owner_type',
            'regd_date',
            'permit_validity_date',
            'tax_validity_date',
            'fitness_validity_date',
            'insurance_validity_date',
            'maker_model',
            'make',
            'model',
            'remarks',
            'recommended_by',
            'referenced',
            'local_or_non_local',
            'pan_no',
            'gst_no',
        ];
        $data = $this->all();
        foreach ($nullable as $key) {
            if (isset($data[$key]) && $data[$key] === '') {
                $data[$key] = null;
            }
        }
        $this->merge($data);
    }
}
