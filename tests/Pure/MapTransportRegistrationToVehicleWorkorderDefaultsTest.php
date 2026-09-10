<?php

declare(strict_types=1);

use App\Actions\MapTransportRegistrationToVehicleWorkorderDefaults;
use App\Models\TransportWorkOrderRegistration;
use Carbon\Carbon;

it('maps columns and formats date', function (): void {
    $date = Carbon::parse('2026-05-10');
    $r = new TransportWorkOrderRegistration;
    $r->siding_id = 5;
    $r->work_order_no_1 = ' P64 ';
    $r->work_order_no_2 = 'REF2';
    $r->transporter_name = 'ABC Transport';
    $r->pan_card = 'PAN1';
    $r->gst_no = 'GST1';
    $r->mobile_1 = '111';
    $r->mobile_2 = '222';
    $r->address = 'Addr';
    $r->reference_no = 'REF';
    $r->legal_name_of_business = 'Legal LLP';
    $r->trade_name = '';
    $r->setAttribute('work_order_date', $date);

    $map = new MapTransportRegistrationToVehicleWorkorderDefaults;
    $defaults = $map->handle($r);

    expect($defaults['siding_id'])->toBe('5')
        ->and($defaults['wo_no'])->toBe('P64')
        ->and($defaults['wo_no_2'])->toBe('REF2')
        ->and($defaults['transport_name'])->toBe('ABC Transport')
        ->and($defaults['pan_no'])->toBe('PAN1')
        ->and($defaults['gst_no'])->toBe('GST1')
        ->and($defaults['mobile_no_1'])->toBe('111')
        ->and($defaults['mobile_no_2'])->toBe('222')
        ->and($defaults['address'])->toBe('Addr')
        ->and($defaults['referenced'])->toBe('REF')
        ->and($defaults['proprietor_name'])->toBe('Legal LLP')
        ->and($defaults['work_order_date'])->toBe('2026-05-10')
        ->and($map->registrationPickerLabel($r))->toBe('ABC Transport REF2');
});

it('omits siding when flag is false', function (): void {
    $r = new TransportWorkOrderRegistration;
    $r->siding_id = 3;
    $r->transporter_name = 'T';

    $defaults = (new MapTransportRegistrationToVehicleWorkorderDefaults)->handle($r, false);

    expect($defaults)->not->toHaveKey('siding_id');
});

it('falls back proprietor to trade name', function (): void {
    $r = new TransportWorkOrderRegistration;
    $r->legal_name_of_business = null;
    $r->trade_name = 'Trade Co';

    $defaults = (new MapTransportRegistrationToVehicleWorkorderDefaults)->handle($r, false);

    expect($defaults['proprietor_name'])->toBe('Trade Co');
});

it('picker label prefers work order no 2 when both are set', function (): void {
    $r = new TransportWorkOrderRegistration;
    $r->transporter_name = 'Roadways Co';
    $r->work_order_no_1 = 'D/111';
    $r->work_order_no_2 = 'P/422';

    $label = (new MapTransportRegistrationToVehicleWorkorderDefaults)->registrationPickerLabel($r);

    expect($label)->toBe('Roadways Co P/422');
});

it('picker label falls back to work order no 1 when wo2 is empty', function (): void {
    $r = new TransportWorkOrderRegistration;
    $r->transporter_name = 'Solo Haul';
    $r->work_order_no_1 = 'D/900';
    $r->work_order_no_2 = ' ';

    expect(
        (new MapTransportRegistrationToVehicleWorkorderDefaults)->registrationPickerLabel($r),
    )->toBe('Solo Haul D/900');
});

it('labels with placeholder when name missing', function (): void {
    $r = new TransportWorkOrderRegistration;
    $r->transporter_name = '';
    $r->work_order_no_1 = 'K1';

    $label = (new MapTransportRegistrationToVehicleWorkorderDefaults)->registrationPickerLabel($r);

    expect($label)->toBe('(no name) K1');
});
