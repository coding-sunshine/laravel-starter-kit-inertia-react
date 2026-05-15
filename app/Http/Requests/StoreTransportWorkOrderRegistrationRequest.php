<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesTransportWorkOrderRegistrationPayload;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreTransportWorkOrderRegistrationRequest extends FormRequest
{
    use ValidatesTransportWorkOrderRegistrationPayload;

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->hasPermissionTo('sections.transport.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'siding_id' => ['required', 'integer', 'exists:sidings,id'],
            'work_order_no_1' => $this->workOrderNoRules(),
            'work_order_no_2' => [...$this->workOrderNoRules(), Rule::unique('transport_work_order_registrations', 'work_order_no_2')],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'work_order_date' => ['nullable', 'date'],
            'transporter_name' => ['required', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'legal_name_of_business' => ['required', 'string'],
            'pan_card' => ['nullable', 'string', 'max:32'],
            'gst_no' => ['nullable', 'string', 'max:32'],
            'status' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'vendor_code' => ['nullable', 'string', 'max:100'],
            'mobile_1' => ['nullable', 'string', 'max:32'],
            'mobile_2' => ['nullable', 'string', 'max:32'],
            'address' => ['required', 'string'],
            'gramin_or_non_gramin' => ['required', 'string', Rule::in($this->graminOrNonGraminRuleIn())],
            'is_active' => ['boolean'],
            'pan_documents' => ['sometimes', 'array'],
            'pan_documents.*' => ['file', 'max:10240', 'mimetypes:application/pdf,image/jpeg,image/png,image/webp,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'gst_documents' => ['sometimes', 'array'],
            'gst_documents.*' => ['file', 'max:10240', 'mimetypes:application/pdf,image/jpeg,image/png,image/webp,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'transporter_documents' => ['sometimes', 'array'],
            'transporter_documents.*' => ['file', 'max:10240', 'mimetypes:application/pdf,image/jpeg,image/png,image/webp,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->assertResolvedSidingMatchesSelection(
                $validator,
                $this->normalizeWorkOrderValue($this->input('work_order_no_1')),
                $this->normalizeWorkOrderValue($this->input('work_order_no_2')),
                $this->input('siding_id'),
            );
        });
    }

    protected function prepareForValidation(): void
    {
        $this->mergeNormalizedTransportRegistrationRequest();
    }
}
