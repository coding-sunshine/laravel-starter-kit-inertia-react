<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contact;
use Illuminate\Support\Facades\Http;

/**
 * Service for interacting with the Vapi voice AI API.
 */
final readonly class VapiService
{
    private string $apiKey;

    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = (string) config('services.vapi.api_key', '');
        $this->baseUrl = 'https://api.vapi.ai';
    }

    public function isConfigured(): bool
    {
        return ! empty($this->apiKey);
    }

    /**
     * Initiate an outbound call to a contact.
     *
     * @return array<string, mixed>
     */
    public function initiateCall(Contact $contact, string $phoneNumber): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'Vapi API key not configured'];
        }

        $response = Http::withToken($this->apiKey)
            ->post("{$this->baseUrl}/call", [
                'type' => 'outboundPhoneCall',
                'phoneNumberId' => $phoneNumber,
                'customer' => [
                    'number' => $phoneNumber,
                    'name' => mb_trim($contact->first_name.' '.($contact->last_name ?? '')),
                ],
                'metadata' => [
                    'contact_id' => $contact->id,
                    'organization_id' => $contact->organization_id,
                ],
            ]);

        return $response->json() ?? [];
    }

    /**
     * Get recent call logs from Vapi.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCallLogs(): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        $response = Http::withToken($this->apiKey)
            ->get("{$this->baseUrl}/call");

        if (! $response->successful()) {
            return [];
        }

        return $response->json() ?? [];
    }
}
