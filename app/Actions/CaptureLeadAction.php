<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Contact;
use App\Models\ContactAttribution;
use App\Models\ContactEmail;
use App\Models\ContactPhone;
use App\Models\EngagementEvent;
use App\Models\Source;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Capture a lead from any channel (web form, SMS, chat, phone, API) and normalize it to a Contact.
 */
final readonly class CaptureLeadAction
{
    /**
     * @param  array<string, mixed>  $data  Normalized lead data from any channel
     */
    public function handle(array $data, int $organizationId): Contact
    {
        $contact = DB::transaction(function () use ($data, $organizationId): Contact {
            $source = isset($data['source_name'])
                ? Source::query()->firstOrCreate(['name' => $data['source_name']])
                : null;

            $contact = Contact::query()->updateOrCreate(
                [
                    'organization_id' => $organizationId,
                    'contact_origin' => $data['channel'] ?? 'web_form',
                ],
                [
                    'first_name' => $data['first_name'] ?? 'Unknown',
                    'last_name' => $data['last_name'] ?? null,
                    'type' => 'lead',
                    'stage' => 'new',
                    'source_id' => $source?->id,
                    'company_name' => $data['company_name'] ?? null,
                    'organization_id' => $organizationId,
                ]
            );

            if (! empty($data['email'])) {
                ContactEmail::query()->firstOrCreate([
                    'contact_id' => $contact->id,
                    'email' => $data['email'],
                ], ['type' => 'primary', 'is_primary' => true]);
            }

            if (! empty($data['phone'])) {
                ContactPhone::query()->firstOrCreate([
                    'contact_id' => $contact->id,
                    'phone' => $data['phone'],
                ], ['type' => 'mobile', 'is_primary' => true]);
            }

            // Record attribution
            ContactAttribution::query()->create([
                'contact_id' => $contact->id,
                'campaign_name' => $data['campaign_name'] ?? null,
                'ad_name' => $data['ad_name'] ?? null,
                'source' => $data['channel'] ?? 'web_form',
                'attributed_at' => Carbon::now(),
                'organization_id' => $organizationId,
            ]);

            // Record engagement event
            EngagementEvent::query()->create([
                'contact_id' => $contact->id,
                'event_type' => 'form_submit',
                'source' => $data['channel'] ?? 'web_form',
                'payload' => ['page' => $data['page_url'] ?? null, 'campaign' => $data['campaign_name'] ?? null],
                'occurred_at' => Carbon::now(),
            ]);

            return $contact;
        });

        app(TriggerWebhooksAction::class)->handle('contact.created', [
            'contact_id' => $contact->id,
            'first_name' => $contact->first_name,
            'last_name' => $contact->last_name,
            'type' => $contact->type,
            'stage' => $contact->stage,
            'organization_id' => $organizationId,
        ], $organizationId);

        return $contact;
    }
}
