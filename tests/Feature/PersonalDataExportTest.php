<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Spatie\PersonalDataExport\Jobs\CreatePersonalDataExportJob;

it('queues personal data export when requested by authenticated user', function (): void {
    Queue::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('personal-data-export.store'));

    $response->assertRedirect();
    $response->assertSessionHas('status');

    Queue::assertPushed(CreatePersonalDataExportJob::class);
});

it('requires authentication to request personal data export', function (): void {
    $response = $this->post(route('personal-data-export.store'));

    $response->assertRedirect(route('login'));
});
