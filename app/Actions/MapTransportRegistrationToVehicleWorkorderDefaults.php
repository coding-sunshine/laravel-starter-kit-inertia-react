<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\TransportWorkOrderRegistration;

final readonly class MapTransportRegistrationToVehicleWorkorderDefaults
{
    /**
     * Map a transport work order registration onto vehicle workorder form/store fields.
     *
     * @param  bool  $includeSidingId  When false (e.g. vehicle workorder edit), siding is left unchanged.
     * @return array<string, mixed>
     */
    public function handle(TransportWorkOrderRegistration $registration, bool $includeSidingId = true): array
    {
        $proprietor = mb_trim((string) ($registration->legal_name_of_business ?? ''));
        if ($proprietor === '') {
            $proprietor = mb_trim((string) ($registration->trade_name ?? ''));
        }

        $out = [
            'wo_no' => $this->stringOrEmpty($registration->work_order_no_1),
            'wo_no_2' => $this->stringOrEmpty($registration->work_order_no_2),
            'work_order_date' => $registration->work_order_date?->format('Y-m-d') ?? '',
            'transport_name' => $this->stringOrEmpty($registration->transporter_name),
            'pan_no' => $this->stringOrEmpty($registration->pan_card),
            'gst_no' => $this->stringOrEmpty($registration->gst_no),
            'mobile_no_1' => $this->stringOrEmpty($registration->mobile_1),
            'mobile_no_2' => $this->stringOrEmpty($registration->mobile_2),
            'address' => $this->stringOrEmpty($registration->address),
            'referenced' => $this->stringOrEmpty($registration->reference_no),
            'proprietor_name' => $proprietor,
        ];

        if ($includeSidingId && $registration->siding_id !== null) {
            $out['siding_id'] = (string) $registration->siding_id;
        }

        return $out;
    }

    /**
     * Picker label uses work order no. 2 when set (siding-style code), otherwise no. 1.
     */
    public function registrationPickerLabel(TransportWorkOrderRegistration $registration): string
    {
        $name = mb_trim((string) ($registration->transporter_name ?? ''));
        if ($name === '') {
            $name = '(no name)';
        }

        $wo2 = mb_trim((string) ($registration->work_order_no_2 ?? ''));
        $wo1 = mb_trim((string) ($registration->work_order_no_1 ?? ''));
        $wo = $wo2 !== '' ? $wo2 : $wo1;

        return $wo !== '' ? mb_trim($name.' '.$wo) : $name;
    }

    private function stringOrEmpty(?string $value): string
    {
        $s = mb_trim((string) ($value ?? ''));

        return $s === '' ? '' : $s;
    }
}
