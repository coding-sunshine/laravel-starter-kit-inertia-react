<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\WebhookEndpoint;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

final class TriggerWebhookJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly WebhookEndpoint $endpoint,
        public readonly string $event,
        public readonly array $payload
    ) {}

    public function handle(): void
    {
        $body = json_encode([
            'event' => $this->event,
            'payload' => $this->payload,
            'timestamp' => now()->toIso8601String(),
        ]);

        $headers = ['Content-Type' => 'application/json'];

        if (! empty($this->endpoint->secret)) {
            $signature = hash_hmac('sha256', (string) $body, $this->endpoint->secret);
            $headers['X-Fusion-Signature'] = $signature;
        }

        Http::withHeaders($headers)->post($this->endpoint->url, json_decode((string) $body, true));

        $this->endpoint->update(['last_triggered_at' => now()]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error("Webhook delivery failed for endpoint #{$this->endpoint->id}: {$exception->getMessage()}");

        $this->endpoint->increment('failure_count');
    }
}
