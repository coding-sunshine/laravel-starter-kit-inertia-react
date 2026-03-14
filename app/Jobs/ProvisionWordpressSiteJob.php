<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\WordpressWebsite;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ProvisionWordpressSiteJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 120;

    public function __construct(public readonly WordpressWebsite $site) {}

    public function handle(): void
    {
        $provisionerUrl = config('services.wp_provisioner.url');

        if (empty($provisionerUrl)) {
            Log::warning("WP_PROVISIONER_URL not set — skipping provisioning for site #{$this->site->id}");

            return;
        }

        $callbackUrl = url("/api/provisioner/wordpress-sites/{$this->site->id}/callback");

        try {
            $this->site->update(['stage' => 2]); // Initializing

            Http::post($provisionerUrl, [
                'site_id' => $this->site->id,
                'title' => $this->site->title,
                'type' => $this->site->type,
                'primary_color' => $this->site->primary_color,
                'secondary_color' => $this->site->secondary_color,
                'enquiry_emails' => $this->site->enquiry_recipient_emails ?? [],
                'callback_url' => $callbackUrl,
            ]);
        } catch (Throwable $e) {
            Log::error("WP provisioning failed for site #{$this->site->id}: {$e->getMessage()}");
            throw $e;
        }
    }
}
