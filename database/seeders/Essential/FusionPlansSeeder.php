<?php

declare(strict_types=1);

namespace Database\Seeders\Essential;

use App\Models\Billing\Plan;
use Illuminate\Database\Seeder;
use Laravelcm\Subscriptions\Interval;

/**
 * Seeds the default Fusion CRM subscription plans.
 * Superadmin can edit all fields at runtime via Filament → Plans.
 */
final class FusionPlansSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => ['en' => 'Starter'],
                'slug' => 'fusion-starter',
                'description' => ['en' => 'Core CRM features for solo agents and small teams.'],
                'price' => 330.00,
                'setup_fee' => 150.00,
                'is_public' => true,
                'currency' => 'aud',
                'invoice_period' => 1,
                'invoice_interval' => Interval::MONTH->value,
                'ai_credits_per_period' => 50,
                'max_users' => 3,
                'max_php_sites' => 1,
                'max_wp_sites' => 0,
                'features' => [
                    'flags' => [
                        'PropertyAccessFeature',
                        'BotInABoxFeature',
                        'SprFeature',
                        'CampaignWebsitesFeature',
                        'FlyersFeature',
                        'WebsitesFeature',
                        'PhpSitesFeature',
                    ],
                    'ai_credits' => 50,
                    'max_users' => 3,
                    'max_php_sites' => 1,
                    'max_wp_sites' => 0,
                ],
                'sort_order' => 1,
            ],
            [
                'name' => ['en' => 'Growth'],
                'slug' => 'fusion-growth',
                'description' => ['en' => 'Full AI-powered CRM with advanced integrations.'],
                'price' => 415.00,
                'setup_fee' => 0.00,
                'is_public' => true,
                'currency' => 'aud',
                'invoice_period' => 1,
                'invoice_interval' => Interval::MONTH->value,
                'ai_credits_per_period' => 200,
                'max_users' => null,
                'max_php_sites' => 2,
                'max_wp_sites' => 3,
                'features' => [
                    'flags' => [
                        'PropertyAccessFeature',
                        'AiToolsFeature',
                        'AiBotsCustomFeature',
                        'BotInABoxFeature',
                        'SprFeature',
                        'ApiAccessFeature',
                        'WebsitesFeature',
                        'WordPressSitesFeature',
                        'PhpSitesFeature',
                        'CampaignWebsitesFeature',
                        'FlyersFeature',
                        'XeroIntegrationFeature',
                        'AdvancedReportsFeature',
                    ],
                    'ai_credits' => 200,
                    'max_users' => null,
                    'max_php_sites' => 2,
                    'max_wp_sites' => 3,
                ],
                'sort_order' => 2,
            ],
            [
                'name' => ['en' => 'Growth Annual'],
                'slug' => 'fusion-growth-annual',
                'description' => ['en' => 'Full AI-powered CRM — annual plan (save ~20%).'],
                'price' => 3960.00,
                'setup_fee' => 0.00,
                'is_public' => true,
                'currency' => 'aud',
                'invoice_period' => 1,
                'invoice_interval' => Interval::YEAR->value,
                'ai_credits_per_period' => 2400,
                'max_users' => null,
                'max_php_sites' => 2,
                'max_wp_sites' => 3,
                'features' => [
                    'flags' => [
                        'PropertyAccessFeature',
                        'AiToolsFeature',
                        'AiBotsCustomFeature',
                        'BotInABoxFeature',
                        'SprFeature',
                        'ApiAccessFeature',
                        'WebsitesFeature',
                        'WordPressSitesFeature',
                        'PhpSitesFeature',
                        'CampaignWebsitesFeature',
                        'FlyersFeature',
                        'XeroIntegrationFeature',
                        'AdvancedReportsFeature',
                    ],
                    'ai_credits' => 2400,
                    'max_users' => null,
                    'max_php_sites' => 2,
                    'max_wp_sites' => 3,
                ],
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $attributes) {
            Plan::query()->updateOrCreate(
                ['slug' => $attributes['slug']],
                $attributes
            );
        }
    }
}
