<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Contact;
use App\Models\ContactEmail;
use App\Models\ContactPhone;
use App\Models\Source;

it('can create a contact with factory', function (): void {
    $contact = Contact::factory()->create();

    expect($contact)->toBeInstanceOf(Contact::class)
        ->and($contact->first_name)->toBeString()
        ->and($contact->last_name)->toBeString();
});

it('has a full name accessor', function (): void {
    $contact = Contact::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);

    expect($contact->full_name)->toBe('John Doe');
});

it('returns Unknown when name is empty', function (): void {
    $contact = Contact::factory()->create([
        'first_name' => '',
        'last_name' => '',
    ]);

    expect($contact->full_name)->toBe('Unknown');
});

it('belongs to a source', function (): void {
    $source = Source::factory()->create();
    $contact = Contact::factory()->create(['source_id' => $source->id]);

    expect($contact->source)->toBeInstanceOf(Source::class)
        ->and($contact->source->id)->toBe($source->id);
});

it('belongs to a company', function (): void {
    $company = Company::factory()->create();
    $contact = Contact::factory()->create(['company_id' => $company->id]);

    expect($contact->company)->toBeInstanceOf(Company::class)
        ->and($contact->company->id)->toBe($company->id);
});

it('has many contact emails', function (): void {
    $contact = Contact::factory()->create();
    ContactEmail::factory()->count(3)->create(['contact_id' => $contact->id]);

    expect($contact->contactEmails)->toHaveCount(3);
});

it('has many contact phones', function (): void {
    $contact = Contact::factory()->create();
    ContactPhone::factory()->count(2)->create(['contact_id' => $contact->id]);

    expect($contact->contactPhones)->toHaveCount(2);
});

it('can be soft deleted', function (): void {
    $contact = Contact::factory()->create();
    $contact->delete();

    expect($contact->trashed())->toBeTrue()
        ->and(Contact::withTrashed()->find($contact->id))->not->toBeNull();
});

it('casts extra_attributes to array', function (): void {
    $contact = Contact::factory()->create([
        'extra_attributes' => ['key' => 'value'],
    ]);

    $contact->refresh();
    expect($contact->extra_attributes)->toBeArray()
        ->and($contact->extra_attributes['key'])->toBe('value');
});

it('uses factory states for contact types', function (): void {
    $lead = Contact::factory()->lead()->create();
    $client = Contact::factory()->client()->create();
    $agent = Contact::factory()->agent()->create();
    $partner = Contact::factory()->partner()->create();

    expect($lead->type)->toBe('lead')
        ->and($client->type)->toBe('client')
        ->and($agent->type)->toBe('agent')
        ->and($partner->type)->toBe('partner');
});

it('cascades delete to contact emails', function (): void {
    $contact = Contact::factory()->create();
    $email = ContactEmail::factory()->create(['contact_id' => $contact->id]);

    // Force delete to trigger cascade
    $contact->forceDelete();

    expect(ContactEmail::find($email->id))->toBeNull();
});

it('cascades delete to contact phones', function (): void {
    $contact = Contact::factory()->create();
    $phone = ContactPhone::factory()->create(['contact_id' => $contact->id]);

    $contact->forceDelete();

    expect(ContactPhone::find($phone->id))->toBeNull();
});
