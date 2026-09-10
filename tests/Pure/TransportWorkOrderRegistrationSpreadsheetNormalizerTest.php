<?php

declare(strict_types=1);

use App\Models\TransportWorkOrderRegistration;
use App\Support\TransportWorkOrderRegistrationSpreadsheetNormalizer;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

it('sets reference no to null for new work order placeholders', function (): void {
    $n = new TransportWorkOrderRegistrationSpreadsheetNormalizer;

    expect($n->normalizeReferenceNo('NEW WORKORDER NOT ISSUED'))->toBeNull();
    expect($n->normalizeReferenceNo('some new workorder not issued variant'))->toBeNull();
    expect($n->normalizeReferenceNo('  REF-123  '))->toBe('REF-123');
});

it('sets work order date to null for placeholders and parses excel serials', function (): void {
    $n = new TransportWorkOrderRegistrationSpreadsheetNormalizer;

    expect($n->normalizeWorkOrderDate('NOT ISSUED'))->toBeNull();
    expect($n->normalizeWorkOrderDate('Not Issued'))->toBeNull();
    expect($n->normalizeWorkOrderDate('something not issued here'))->toBeNull();

    $serial = 43831;
    expect($n->normalizeWorkOrderDate($serial))
        ->toBe(ExcelDate::excelToDateTimeObject((float) $serial)->format('Y-m-d'));
});

it('maps ACTIVE and INACTIVE status to is_active while preserving raw status', function (): void {
    $n = new TransportWorkOrderRegistrationSpreadsheetNormalizer;

    expect($n->normalizeStatusColumns('ACTIVE'))
        ->toMatchArray(['is_active' => true, 'status' => 'ACTIVE']);

    expect($n->normalizeStatusColumns('INACTIVE'))
        ->toMatchArray(['is_active' => false, 'status' => 'INACTIVE']);

    expect($n->normalizeStatusColumns('Pending'))
        ->toMatchArray(['is_active' => true, 'status' => 'Pending']);
});

it('normalizes Gramin / Non Gramin variants to canonical stored values', function (): void {
    $n = new TransportWorkOrderRegistrationSpreadsheetNormalizer;

    expect($n->normalizeGraminOrNonGramin('Gramin'))
        ->toBe(TransportWorkOrderRegistration::GRAMIN_OR_NON_GRAMIN_GRAMIN);

    expect($n->normalizeGraminOrNonGramin('GRAMIN'))
        ->toBe(TransportWorkOrderRegistration::GRAMIN_OR_NON_GRAMIN_GRAMIN);

    expect($n->normalizeGraminOrNonGramin('Non Gramin'))
        ->toBe(TransportWorkOrderRegistration::GRAMIN_OR_NON_GRAMIN_NON_GRAMIN);

    expect($n->normalizeGraminOrNonGramin('surrendered'))->toBeNull();
    expect($n->normalizeGraminOrNonGramin(''))->toBeNull();
});

it('normalizes header labels for column matching', function (): void {
    expect(TransportWorkOrderRegistrationSpreadsheetNormalizer::normalizeHeaderLabel('  Work   Order  No. 2  '))
        ->toBe('work order no. 2');
});
