<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use App\Models\TransportWorkOrderRegistration;
use App\Support\TransportWorkOrderRegistrationSidingResolver;
use Closure;
use Illuminate\Validation\Validator;

trait ValidatesTransportWorkOrderRegistrationPayload
{
    protected function mergeNormalizedTransportRegistrationRequest(): void
    {
        $keys = [
            'siding_id',
            'work_order_no_1',
            'work_order_no_2',
            'reference_no',
            'work_order_date',
            'transporter_name',
            'trade_name',
            'legal_name_of_business',
            'pan_card',
            'gst_no',
            'status',
            'email',
            'vendor_code',
            'mobile_1',
            'mobile_2',
            'address',
            'gramin_or_non_gramin',
        ];
        $merged = [];
        foreach ($keys as $key) {
            if ($this->has($key) && $this->input($key) === '') {
                $merged[$key] = null;
            }
        }
        $merged['is_active'] = $this->boolean('is_active');

        $this->merge($merged);
    }

    /**
     * @return list<string>
     */
    protected function graminOrNonGraminRuleIn(): array
    {
        return TransportWorkOrderRegistration::GRAMIN_OR_NON_GRAMIN_VALUES;
    }

    /**
     * @return array<int, mixed>
     */
    protected function workOrderNoRules(): array
    {
        return [
            'nullable',
            'string',
            'max:255',
            function (string $attribute, mixed $value, Closure $fail): void {
                $this->validateWorkOrderNoCharacters($attribute, $value, $fail);
            },
        ];
    }

    protected function validateWorkOrderNoCharacters(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        foreach (mb_str_split((string) $value) as $ch) {
            if ($ch === '') {
                continue;
            }

            if (preg_match('/^[0-9]$/u', $ch) === 1) {
                continue;
            }

            if (preg_match('/^[\s\/.\-_]$/u', $ch) === 1) {
                continue;
            }

            if (preg_match('/^[DPK]$/iu', $ch) === 1) {
                continue;
            }

            $fail(__('Work order fields may only use digits, spaces, . / _ - plus the letters D, P, or K.'));

            return;
        }
    }

    /**
     * When the resolver can derive a siding from WO1/WO2, it must equal the submitted siding choice.
     */
    protected function assertResolvedSidingMatchesSelection(Validator $validator, ?string $workOrderNo1, ?string $workOrderNo2, mixed $selectedSidingId): void
    {
        if ($selectedSidingId === null || $selectedSidingId === '') {
            return;
        }

        $resolver = app(TransportWorkOrderRegistrationSidingResolver::class);
        $resolved = $resolver->resolveSidingId(
            $this->normalizeWorkOrderValue($workOrderNo1),
            $this->normalizeWorkOrderValue($workOrderNo2),
        );

        if ($resolved === null) {
            return;
        }

        if ((int) $resolved !== (int) $selectedSidingId) {
            $validator->errors()->add(
                'siding_id',
                __('The siding must match the D, P, or K siding letter encoded in these work order numbers.'),
            );
        }
    }

    protected function normalizeWorkOrderValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_string($value) ? $value : (string) $value;
    }
}
