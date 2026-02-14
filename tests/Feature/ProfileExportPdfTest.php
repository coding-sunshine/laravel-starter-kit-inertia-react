<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\LaravelPdf\Facades\Pdf;

test('guest cannot export profile pdf', function (): void {
    $response = $this->get(route('profile.export-pdf'));

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();
});

test('authenticated user can export profile pdf', function (): void {
    Pdf::fake();

    $user = User::factory()->create();
    $response = $this->actingAs($user)->get(route('profile.export-pdf'));

    $response->assertOk();
    Pdf::assertRespondedWithPdf(fn ($pdf): bool => $pdf->viewName === 'pdf.profile');
});
