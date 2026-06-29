<?php

declare(strict_types=1);

use App\Services\Railway\RrImportService;
use App\Services\Railway\RrParserService;

function rrMinimalStandardEtRrText(): string
{
    return <<<'TXT'
Electronically transmitted railway receipt

RR No. : 12345678
FNR: 9876543210123
Freight: Rs 1000
Distance (In KM): 100
Wagons: 1
Total Weight: 50
Wagon details of the Railway Receipt
1 NW BOBRNM1 12345678 65 26 0 2991324 85 0 50 65 0 0 65 65 65
TXT;
}

function rrMinimalFoisPrintedText(): string
{
    return <<<'TXT'
RR DETAILS
RR NO.        161009670      RR DATE       01-03-2026
FNR                          26030102127
INVC NO.:                    55                 INVC DATE:             01-03-2026             CHRG DIST:            123
RISK RATE:                   OR                 CLASS:                 145A                   RATE:                 389.6
WGON FRWH:             2                     WGHT UNIT:            57
TOTAL CHRG WT.:              145.0

CMDT CODE:          2991323

FROM STATION/SIDING:                                                                                                WBPC 02320746
  TO STATION/SIDING:           BAKRESHWAR CODE:    BTPC 02119485

ACTL WGHT:                                       100.0
CHBL WGHT AT NORM. RATE:                         145.0
FREIGHT:                     1439883.68
POL1 2025.92
TOTAL FREIGHT:               1514006

Wagon Details of the RR
 1        SEC      BOBRNHSM1 73140895218 65             25.61 0     2991323   86.4            0    50.25   65     0      0      0    65
 2        SC       BOBRNM1   72090261104 65             25.61 0     2991323   88.4            0    49.75   65     0      0      0    65
TXT;
}

function rrMinimalEtRrMultipageText(): string
{
    return <<<'TXT'
Electronically Transmitted Railway Receipt
RR No:999888777 RR Date:24.06.2026 FNR:26062399999 Station From:BMGK Station To:BTMT
Wagons                   2                      Total Weight             136.000
Actual            115.000
Chargeable        136.000
Distance(In Km)          236.000
Class                    145A                    Rate                        672.50
Comodity Code             2991323
Commodity Description:RUN OFF MINES COAL(ROM)
Invoice Number           36                      Invoice Date                23.06.2026
Freight(Rs.)              1000.00

Other Charges
GST      50.00
OTC      25.00
Total Freight                                1075.00

SR.   OWNG TYPE           WGON          CC(T)    TARE     No of CMDT      GROSS DIP   DIP        Actl.    PERM OVER OVER OVER CHBL
1     WR      BOXNM1      10089962694   68.000   22.530         2991323   87.400   0             64.870   68.000     0.000   0.000   0.000   68.000
2     CR      BOXNM1      10019903216   68.000   22.530         2991323   88.000   0             50.130   68.000     0.000   0.000   0.000   68.000
TXT;
}

beforeEach(function (): void {
    $this->parser = new RrParserService;
});

test('parses standard eT-RR text as et_rr with null actual_weight_mt', function (): void {
    $p = $this->parser->parseExtractedText(rrMinimalStandardEtRrText());

    expect($p['rr_format'])->toBe(RrParserService::RR_FORMAT_ET_RR)
        ->and($p['rr_number'])->toBe('12345678')
        ->and($p['actual_weight_mt'] ?? null)->toBeNull()
        ->and($p['wagons'])->toHaveCount(1)
        ->and((float) $p['wagons'][0]['loaded_weight'])->toBe(50.0);
});

test('parses FOIS printed layout with chargeable total, actual header, WBPC/BTPC, wagon ACTL as loaded_weight', function (): void {
    $p = $this->parser->parseExtractedText(rrMinimalFoisPrintedText());

    expect($p['rr_format'])->toBe(RrParserService::RR_FORMAT_FOIS_PRINTED)
        ->and($p['class'] ?? null)->toBe('145A')
        ->and((float) ($p['rate'] ?? 0))->toBe(389.6)
        ->and((float) $p['total_weight'])->toBe(145.0)
        ->and((float) $p['actual_weight_mt'])->toBe(100.0)
        ->and($p['from_station_code'])->toBe('WBPC')
        ->and($p['to_station_code'])->toBe('BTPC')
        ->and($p['wagon_count'])->toBe(2)
        ->and($p['wagons'])->toHaveCount(2)
        ->and(array_sum(array_column($p['wagons'], 'loaded_weight')))->toBe(100.0);

    expect($p['charges'])->not->toBeEmpty();
});

test('parses multipage ET-RR with CC as pcc, CHBL as permissible, Actl as loaded_weight', function (): void {
    $p = $this->parser->parseExtractedText(rrMinimalEtRrMultipageText());

    expect($p['rr_format'])->toBe(RrParserService::RR_FORMAT_ET_RR_MULTIPAGE)
        ->and($p['rr_number'])->toBe('999888777')
        ->and($p['fnr'])->toBe('26062399999')
        ->and($p['from_station_code'])->toBe('BMGK')
        ->and($p['to_station_code'])->toBe('BTMT')
        ->and($p['rr_received_date'])->toBe('2026-06-24')
        ->and((float) $p['total_weight'])->toBe(136.0)
        ->and((float) $p['actual_weight_mt'])->toBe(115.0)
        ->and($p['wagon_count'])->toBe(2)
        ->and($p['wagons'])->toHaveCount(2)
        ->and((float) $p['wagons'][0]['pcc_weight'])->toBe(68.0)
        ->and((float) $p['wagons'][0]['permissible_weight'])->toBe(68.0)
        ->and((float) $p['wagons'][0]['loaded_weight'])->toBe(64.87)
        ->and(array_sum(array_column($p['wagons'], 'loaded_weight')))->toBe(115.0);

    $codes = array_column($p['charges'], 'code');
    expect($codes)->toContain('FREIGHT', 'GST', 'OTC');
});

test('RrImportService assertParsedRrInternalConsistency passes for consistent multipage ET-RR parse', function (): void {
    $p = $this->parser->parseExtractedText(rrMinimalEtRrMultipageText());
    $svc = new RrImportService;
    $m = new ReflectionMethod(RrImportService::class, 'assertParsedRrInternalConsistency');
    $m->setAccessible(true);
    $m->invoke($svc, $p);

    expect(true)->toBeTrue();
});

test('RrImportService assertParsedRrInternalConsistency rejects multipage ET-RR when wagon sum mismatches actual header', function (): void {
    $p = $this->parser->parseExtractedText(rrMinimalEtRrMultipageText());
    $p['wagons'][1]['loaded_weight'] = 40.0;

    $svc = new RrImportService;
    $m = new ReflectionMethod(RrImportService::class, 'assertParsedRrInternalConsistency');
    $m->setAccessible(true);

    expect(fn () => $m->invoke($svc, $p))->toThrow(InvalidArgumentException::class, 'does not match header ACTL WGHT');
});

test('FOIS charges ignore GST particulars and remarks narrative (no false FOR/MITRA/WT rows)', function (): void {
    $bogusNarrative = <<<'TXT'

ALLOCATION AND OTHER DETAILS
*GST PARTICULARS: FOR 19-WEST BENGAL SOURABH MITRA 20 RC 19 OF 2017 DT 30 WT 3699.88
REMARKS: EXTRA TEXT
TXT;
    $text = str_replace(
        'TOTAL FREIGHT:               1514006'.PHP_EOL.PHP_EOL.'Wagon Details of the RR',
        'TOTAL FREIGHT:               1514006'.PHP_EOL.$bogusNarrative.PHP_EOL.'Wagon Details of the RR',
        rrMinimalFoisPrintedText()
    );
    $p = $this->parser->parseExtractedText($text);
    $codes = array_column($p['charges'], 'code');

    expect($codes)->not->toContain('FOR')
        ->not->toContain('MITRA')
        ->not->toContain('WT')
        ->not->toContain('RC')
        ->not->toContain('OF')
        ->not->toContain('DT');
});

test('throws when PDF text is neither eT-RR nor FOIS printed', function (): void {
    expect(fn () => $this->parser->parseExtractedText('This is generic PDF noise without RR markers.'))
        ->toThrow(InvalidArgumentException::class);
});

test('RrImportService assertParsedRrInternalConsistency passes for consistent FOIS parse', function (): void {
    $p = $this->parser->parseExtractedText(rrMinimalFoisPrintedText());
    $svc = new RrImportService;
    $m = new ReflectionMethod(RrImportService::class, 'assertParsedRrInternalConsistency');
    $m->setAccessible(true);
    $m->invoke($svc, $p);

    expect(true)->toBeTrue();
});

test('RrImportService assertParsedRrInternalConsistency rejects FOIS when wagon sum mismatches ACTL beyond tolerance', function (): void {
    $p = $this->parser->parseExtractedText(rrMinimalFoisPrintedText());
    $p['wagons'][1]['loaded_weight'] = 40.0;

    $svc = new RrImportService;
    $m = new ReflectionMethod(RrImportService::class, 'assertParsedRrInternalConsistency');
    $m->setAccessible(true);

    expect(fn () => $m->invoke($svc, $p))->toThrow(InvalidArgumentException::class, 'does not match header ACTL WGHT');
});

test('RrImportService assertParsedRrInternalConsistency rejects FOIS when wagon row count mismatches WGON FRWH', function (): void {
    $p = $this->parser->parseExtractedText(rrMinimalFoisPrintedText());
    array_pop($p['wagons']);

    $svc = new RrImportService;
    $m = new ReflectionMethod(RrImportService::class, 'assertParsedRrInternalConsistency');
    $m->setAccessible(true);

    expect(fn () => $m->invoke($svc, $p))->toThrow(InvalidArgumentException::class, 'Wagon row count');
});

/**
 * Fixture file is optional (may be untracked locally). Requires pdftotext on PATH.
 */
test('parses RAKE-1 (d) FOIS PDF when fixture and pdftotext are available', function (): void {
    $pdf = base_path('RAKE-1 (d).pdf');
    $text = shell_exec('pdftotext -layout '.escapeshellarg($pdf).' - 2>/dev/null');
    $trimmed = mb_trim((string) $text);
    expect($trimmed)->not->toBe('');

    $p = (new RrParserService)->parseExtractedText($trimmed);

    $tol = (float) config('rrmcs.rr_parse.tolerance_actual_weight_mt', 0.5);
    $sumLoaded = (float) array_sum(array_column($p['wagons'], 'loaded_weight'));

    expect($p['rr_format'])->toBe(RrParserService::RR_FORMAT_FOIS_PRINTED)
        ->and($p['from_station_code'])->toBe('WBPC')
        ->and($p['to_station_code'])->toBe('BTPC')
        ->and($p['rr_number'])->toBe('161009670')
        ->and(count($p['wagons']))->toBe(57)
        ->and(abs($sumLoaded - (float) $p['actual_weight_mt']))->toBeLessThanOrEqual($tol);
})->skip(function (): bool {
    $pdf = base_path('RAKE-1 (d).pdf');
    if (! is_readable($pdf)) {
        return true;
    }

    return mb_trim((string) shell_exec('command -v pdftotext 2>/dev/null')) === '';
}, 'Skipping: RAKE-1 (d).pdf missing or pdftotext unavailable');

/**
 * Fixture file is optional (may be untracked locally). Requires pdftotext on PATH.
 */
test('parses BMGK multipage ET-RR PDF when fixture and pdftotext are available', function (): void {
    $pdf = base_path('BMGK_BTMT_RR_461001833_24.06.2026.PDF');
    $text = shell_exec('pdftotext -layout '.escapeshellarg($pdf).' - 2>/dev/null');
    $trimmed = mb_trim((string) $text);
    expect($trimmed)->not->toBe('');

    $p = (new RrParserService)->parseExtractedText($trimmed);

    $tol = (float) config('rrmcs.rr_parse.tolerance_actual_weight_mt', 0.5);
    $sumLoaded = (float) array_sum(array_column($p['wagons'], 'loaded_weight'));
    $sumPermissible = (float) array_sum(array_column($p['wagons'], 'permissible_weight'));

    expect($p['rr_format'])->toBe(RrParserService::RR_FORMAT_ET_RR_MULTIPAGE)
        ->and($p['rr_number'])->toBe('461001833')
        ->and($p['fnr'])->toBe('26062318312')
        ->and($p['from_station_code'])->toBe('BMGK')
        ->and($p['to_station_code'])->toBe('BTMT')
        ->and($p['rr_received_date'])->toBe('2026-06-24')
        ->and(count($p['wagons']))->toBe(57)
        ->and($p['wagon_count'])->toBe(57)
        ->and(abs($sumLoaded - (float) $p['actual_weight_mt']))->toBeLessThanOrEqual($tol)
        ->and(abs((float) $p['total_weight'] - 3957.2))->toBeLessThanOrEqual(0.5)
        ->and(abs($sumPermissible - 3957.1))->toBeLessThanOrEqual(0.5)
        ->and((float) $p['wagons'][0]['pcc_weight'])->toBe(68.0)
        ->and((float) $p['wagons'][0]['permissible_weight'])->toBe(68.0)
        ->and((float) $p['wagons'][0]['loaded_weight'])->toBe(64.87);

    $svc = new RrImportService;
    $m = new ReflectionMethod(RrImportService::class, 'assertParsedRrInternalConsistency');
    $m->setAccessible(true);
    $m->invoke($svc, $p);

    expect(true)->toBeTrue();
})->skip(function (): bool {
    $pdf = base_path('BMGK_BTMT_RR_461001833_24.06.2026.PDF');
    if (! is_readable($pdf)) {
        return true;
    }

    return mb_trim((string) shell_exec('command -v pdftotext 2>/dev/null')) === '';
}, 'Skipping: BMGK PDF missing or pdftotext unavailable');
