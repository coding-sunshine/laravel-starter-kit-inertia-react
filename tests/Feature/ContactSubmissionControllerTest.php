<?php

declare(strict_types=1);

use App\Models\ContactSubmission;

/**
 * Valid honeypot fields for contact.store (same as register when ProtectAgainstSpam is enabled).
 *
 * @return array<string, mixed>
 */
function contactHoneypotFields(): array
{
    return [
        config('honeypot.name_field_name', 'my_name') => '',
        config('honeypot.valid_from_field_name', 'valid_from') => encrypt(now()->subSeconds(2)->timestamp),
    ];
}

it('renders contact page', function (): void {
    $response = $this->get(route('contact.create'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('contact/create'));
});

it('stores a contact submission', function (): void {
    $response = $this->fromRoute('contact.create')
        ->post(route('contact.store'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'subject' => 'Question',
            'message' => 'Hello, I have a question.',
            ...contactHoneypotFields(),
        ]);

    $response->assertRedirectToRoute('contact.create')
        ->assertSessionHas('status');

    $submission = ContactSubmission::query()->where('email', 'jane@example.com')->first();

    expect($submission)->not->toBeNull()
        ->and($submission->name)->toBe('Jane Doe')
        ->and($submission->subject)->toBe('Question')
        ->and($submission->message)->toBe('Hello, I have a question.')
        ->and($submission->status)->toBe('new');
});

it('requires name', function (): void {
    $response = $this->fromRoute('contact.create')
        ->post(route('contact.store'), [
            'email' => 'jane@example.com',
            'subject' => 'Subject',
            'message' => 'Message',
            ...contactHoneypotFields(),
        ]);

    $response->assertRedirectToRoute('contact.create')
        ->assertSessionHasErrors('name');
});
