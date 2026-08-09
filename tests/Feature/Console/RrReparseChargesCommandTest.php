<?php

declare(strict_types=1);

use App\Models\RrCharge;
use App\Models\RrDocument;
use App\Services\Railway\Contracts\RrPdfTextExtractorContract;

/**
 * FOIS-printed fixture text with POL1 plus two penalty codes (DCLA, ENHC) that the pre-fix
 * parser dropped — mirrors tests/Railway/RrParserServiceDualFormatTest.php's fixture.
 */
function rrReparseFixtureText(): string
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
DCLA 9200.00
ENHC 7800.00
TOTAL FREIGHT:               1514006

Wagon Details of the RR
 1        SEC      BOBRNHSM1 73140895218 65             25.61 0     2991323   86.4            0    50.25   65     0      0      0    65
 2        SC       BOBRNM1   72090261104 65             25.61 0     2991323   88.4            0    49.75   65     0      0      0    65
TXT;
}

function rrReparseAttachMinimalPdf(RrDocument $doc): void
{
    $path = tempnam(sys_get_temp_dir(), 'rr-reparse-test').'.pdf';
    file_put_contents($path, "%PDF-1.4\n1 0 obj<<>>\nendobj\ntrailer<<>>\n%%EOF");
    $doc->addMedia($path)->toMediaCollection('rr_pdf');
}

/**
 * The command resolves PDF text via RrPdfTextExtractorContract (the same
 * pdftotext -layout call RrParserService::parse() uses). Mocking the contract binding is the
 * seam for tests, so a real PDF / working pdftotext binary is never needed here.
 */
function mockRrReparseExtraction(string $text): void
{
    test()->mock(RrPdfTextExtractorContract::class, function ($mock) use ($text): void {
        $mock->shouldReceive('extract')->andReturn($text);
    });
}

it('inserts rr_charges rows for codes parsed from the PDF that are missing on the document', function (): void {
    $doc = RrDocument::factory()->create(['rr_number' => '161009670']);
    rrReparseAttachMinimalPdf($doc);
    RrCharge::factory()->create(['rr_document_id' => $doc->id, 'charge_code' => 'FREIGHT', 'amount' => 1439883.68]);
    RrCharge::factory()->create(['rr_document_id' => $doc->id, 'charge_code' => 'POL1', 'amount' => 2025.92]);
    mockRrReparseExtraction(rrReparseFixtureText());

    $this->artisan('rr:reparse-charges')
        ->expectsOutputToContain('DCLA=9200, ENHC=7800')
        ->expectsOutputToContain('Docs scanned: 1 | Docs changed: 1 | Rows added: 2')
        ->assertExitCode(0);

    expect(RrCharge::where('rr_document_id', $doc->id)->where('charge_code', 'POL1')->count())->toBe(1)
        ->and(RrCharge::where('rr_document_id', $doc->id)->where('charge_code', 'DCLA')->value('amount'))->toEqual(9200.00)
        ->and(RrCharge::where('rr_document_id', $doc->id)->where('charge_code', 'ENHC')->value('amount'))->toEqual(7800.00);
});

it('skips codes that already exist in rr_charges for the document', function (): void {
    $doc = RrDocument::factory()->create(['rr_number' => '161009670']);
    rrReparseAttachMinimalPdf($doc);
    RrCharge::factory()->create(['rr_document_id' => $doc->id, 'charge_code' => 'FREIGHT', 'amount' => 1439883.68]);
    RrCharge::factory()->create(['rr_document_id' => $doc->id, 'charge_code' => 'POL1', 'amount' => 2025.92]);
    RrCharge::factory()->create(['rr_document_id' => $doc->id, 'charge_code' => 'DCLA', 'amount' => 9200.00]);
    RrCharge::factory()->create(['rr_document_id' => $doc->id, 'charge_code' => 'ENHC', 'amount' => 7800.00]);
    mockRrReparseExtraction(rrReparseFixtureText());

    $this->artisan('rr:reparse-charges')
        ->expectsOutputToContain('Docs scanned: 1 | Docs changed: 0 | Rows added: 0')
        ->assertExitCode(0);

    expect(RrCharge::where('rr_document_id', $doc->id)->count())->toBe(4);
});

it('dry-run reports missing codes without writing to the database', function (): void {
    $doc = RrDocument::factory()->create(['rr_number' => '161009670']);
    rrReparseAttachMinimalPdf($doc);
    RrCharge::factory()->create(['rr_document_id' => $doc->id, 'charge_code' => 'FREIGHT', 'amount' => 1439883.68]);
    RrCharge::factory()->create(['rr_document_id' => $doc->id, 'charge_code' => 'POL1', 'amount' => 2025.92]);
    mockRrReparseExtraction(rrReparseFixtureText());

    $this->artisan('rr:reparse-charges', ['--dry-run' => true])
        ->expectsOutputToContain('[DRY RUN] doc')
        ->assertExitCode(0);

    expect(RrCharge::where('rr_document_id', $doc->id)->count())->toBe(2);
});
