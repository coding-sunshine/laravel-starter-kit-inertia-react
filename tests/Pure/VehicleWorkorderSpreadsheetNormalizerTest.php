<?php

declare(strict_types=1);

use App\Support\VehicleWorkorderSpreadsheetNormalizer;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

it('normalizes header labels consistently', function (): void {
    expect(VehicleWorkorderSpreadsheetNormalizer::normalizeHeaderLabel(' REGD.  NO '))
        ->toBe('regd. no');

    expect(VehicleWorkorderSpreadsheetNormalizer::normalizeHeaderLabel('MOBILE.NO 1'))
        ->toBe('mobile.no 1');
});

it('normalizes vehicle registration numbers', function (): void {
    $n = new VehicleWorkorderSpreadsheetNormalizer;

    expect($n->normalizeVehicleNo('  ab 1234 cd  '))->toBe('AB 1234 CD');
    expect($n->normalizeVehicleNo(''))->toBeNull()
        ->and($n->normalizeVehicleNo(null))->toBeNull();
});

it('parses tare weight without rounding (trimmed cell value)', function (): void {
    $n = new VehicleWorkorderSpreadsheetNormalizer;

    expect($n->parseTareWeight(' 123.456 '))->toBe(123.456)
        ->and($n->parseTareWeight(10))->toBe(10.0)
        ->and($n->parseTareWeight(null))->toBeNull()
        ->and($n->parseTareWeight('x'))->toBeNull();
});

it('coerces tyres to integers or yields null', function (): void {
    $n = new VehicleWorkorderSpreadsheetNormalizer;

    expect($n->normalizeTyres(6))->toBe(6)
        ->and($n->normalizeTyres('8'))->toBe(8)
        ->and($n->normalizeTyres(' 8.12 '))->toBeNull()
        ->and($n->normalizeTyres('six'))->toBeNull()
        ->and($n->normalizeTyres(null))->toBeNull();
});

it('parses optional dates including excel serial numbers', function (): void {
    $n = new VehicleWorkorderSpreadsheetNormalizer;

    expect($n->normalizeDate('NOT ISSUED'))->toBeNull();

    $serial = 43831;

    expect($n->normalizeDate($serial))
        ->toBe(ExcelDate::excelToDateTimeObject((float) $serial)->format('Y-m-d'));
});
