<?php

declare(strict_types=1);

/*
 * All routes must have ->name() for RBAC (permission:sync-routes). Run:
 *   php artisan permission:sync-routes
 * after adding or changing routes.
 */

use App\Http\Controllers\AdTemplateController;
use App\Http\Controllers\AgentPortalController;
use App\Http\Controllers\AiSummaryController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AnnouncementsTableController;
use App\Http\Controllers\Api\LoginEventController;
use App\Http\Controllers\Api\ProvisionerApiController;
use App\Http\Controllers\Api\SlugAvailabilityController;
use App\Http\Controllers\AutomationRuleController;
use App\Http\Controllers\Billing\BillingDashboardController;
use App\Http\Controllers\Billing\CreditController;
use App\Http\Controllers\Billing\InvoiceController;
use App\Http\Controllers\Billing\PaddleWebhookController;
use App\Http\Controllers\Billing\PricingController;
use App\Http\Controllers\Billing\StripeWebhookController;
use App\Http\Controllers\Blog\BlogController;
use App\Http\Controllers\BotV2Controller;
use App\Http\Controllers\BrochureLayoutController;
use App\Http\Controllers\BuilderPortalController;
use App\Http\Controllers\CampaignSiteController;
use App\Http\Controllers\CategoriesTableController;
use App\Http\Controllers\Changelog\ChangelogController;
use App\Http\Controllers\ColdOutreachController;
use App\Http\Controllers\CommissionController;
use App\Http\Controllers\ConciergeController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContactSubmissionController;
use App\Http\Controllers\CookieConsentController;
use App\Http\Controllers\CustomFieldController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DealDocumentController;
use App\Http\Controllers\DealForecastController;
use App\Http\Controllers\DealTrackerController;
use App\Http\Controllers\Dev\ComponentShowcaseController;
use App\Http\Controllers\Dev\PageGalleryController;
use App\Http\Controllers\EmailCampaignController;
use App\Http\Controllers\EnterpriseInquiryController;
use App\Http\Controllers\FlyerController;
use App\Http\Controllers\FunnelController;
use App\Http\Controllers\FunnelTemplateController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HelpCenter\HelpCenterController;
use App\Http\Controllers\HelpCenter\RateHelpArticleController;
use App\Http\Controllers\Internal\CaddyAskController;
use App\Http\Controllers\InventoryApiController;
use App\Http\Controllers\InvitationAcceptController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\LeadCaptureController;
use App\Http\Controllers\LeadGenerationController;
use App\Http\Controllers\LotsTableController;
use App\Http\Controllers\MemberListingsController;
use App\Http\Controllers\Notifications\ClearAllNotificationsController;
use App\Http\Controllers\Notifications\DeleteNotificationController;
use App\Http\Controllers\Notifications\IndexNotificationsController;
use App\Http\Controllers\Notifications\MarkAllNotificationsReadController;
use App\Http\Controllers\Notifications\MarkNotificationReadController;
use App\Http\Controllers\NurtureSequenceController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\OrganizationInvitationController;
use App\Http\Controllers\OrganizationMemberController;
use App\Http\Controllers\OrganizationsTableController;
use App\Http\Controllers\OrganizationSwitchController;
use App\Http\Controllers\OrgThemeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PageViewController;
use App\Http\Controllers\PaymentStageController;
use App\Http\Controllers\PersonalDataExportController;
use App\Http\Controllers\PinnedNoteController;
use App\Http\Controllers\PipelineController;
use App\Http\Controllers\PostsTableController;
use App\Http\Controllers\PredictiveSuggestionsController;
use App\Http\Controllers\ProjectsTableController;
use App\Http\Controllers\PropertyEnquiryController;
use App\Http\Controllers\PropertyReservationController;
use App\Http\Controllers\PropertySearchController;
use App\Http\Controllers\PublicSiteController;
use App\Http\Controllers\PuckTemplateController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RetargetingPixelController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\Settings\AchievementsController;
use App\Http\Controllers\Settings\AuditLogController;
use App\Http\Controllers\Settings\BrandingController;
use App\Http\Controllers\Settings\NotificationPreferencesController;
use App\Http\Controllers\Settings\OrgBrandingUserControlsController;
use App\Http\Controllers\Settings\OrgDomainsController;
use App\Http\Controllers\Settings\OrgFeaturesController;
use App\Http\Controllers\Settings\OrgRolesController;
use App\Http\Controllers\Settings\OrgSlugController;
use App\Http\Controllers\SignupController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TermsAcceptController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserEmailResetNotificationController;
use App\Http\Controllers\UserEmailVerificationController;
use App\Http\Controllers\UserEmailVerificationNotificationController;
use App\Http\Controllers\UserPasswordController;
use App\Http\Controllers\UserPreferencesController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\UsersTableController;
use App\Http\Controllers\UserTwoFactorAuthenticationController;
use App\Http\Controllers\VapiController;
use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\XeroController;
use App\Http\Middleware\InternalRequestMiddleware;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Spatie\Honeypot\ProtectAgainstSpam;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

if (app()->environment(['local', 'staging'])) {
    Route::get('dev/components', ComponentShowcaseController::class)
        ->middleware(['auth', 'feature:component_showcase'])
        ->name('dev.components');

    Route::get('dev/pages', PageGalleryController::class)
        ->middleware(['auth'])
        ->name('dev.pages');
}

Route::get('/favicon.ico', function (): BinaryFileResponse|RedirectResponse {
    $path = public_path('favicon.ico');

    if (File::exists($path)) {
        return response()->file($path, ['Content-Type' => 'image/x-icon']);
    }

    return redirect('/favicon.svg', 302);
})->name('favicon');

Route::get('robots.txt', function (): Response {
    $base = mb_rtrim(config('app.url'), '/');

    return response("User-agent: *\nDisallow:\n\nSitemap: {$base}/sitemap.xml\n", 200, [
        'Content-Type' => 'text/plain',
    ]);
})->name('robots');

Route::get('up/ready', [HealthController::class, 'ready'])->name('up.ready');
Route::get('up', [HealthController::class, 'up'])->name('up');

Route::get('/', fn () => Inertia::render('welcome'))->name('home');

Route::get('invitations/{token}', [InvitationAcceptController::class, 'show'])->name('invitations.show');
Route::post('invitations/{token}/accept', [InvitationAcceptController::class, 'store'])->name('invitations.accept')->middleware('auth');

Route::get('cookie-consent/accept', CookieConsentController::class)
    ->middleware('feature:cookie_consent')
    ->name('cookie-consent.accept');

Route::get('legal/terms', fn () => Inertia::render('legal/terms'))->name('legal.terms');
Route::get('legal/privacy', fn () => Inertia::render('legal/privacy'))->name('legal.privacy');

Route::prefix('blog')->name('blog.')->middleware('feature:blog')->group(function (): void {
    Route::get('/', [BlogController::class, 'index'])->name('index');
    Route::get('/{post:slug}', [BlogController::class, 'show'])->name('show');
});

Route::get('changelog', [ChangelogController::class, 'index'])
    ->middleware('feature:changelog')
    ->name('changelog.index');

Route::prefix('help')->name('help.')->middleware('feature:help')->group(function (): void {
    Route::get('/', [HelpCenterController::class, 'index'])->name('index');
    Route::get('/{helpArticle:slug}', [HelpCenterController::class, 'show'])->name('show');
    Route::post('/{helpArticle:slug}/rate', RateHelpArticleController::class)->name('rate');
});

Route::get('pricing', [PricingController::class, 'index'])->name('pricing');

Route::get('contact', [ContactSubmissionController::class, 'create'])
    ->middleware('feature:contact')
    ->name('contact.create');
Route::post('contact', [ContactSubmissionController::class, 'store'])
    ->middleware(['feature:contact', ProtectAgainstSpam::class])
    ->name('contact.store');

Route::get('enterprise', [EnterpriseInquiryController::class, 'create'])
    ->name('enterprise-inquiries.create');
Route::post('enterprise', [EnterpriseInquiryController::class, 'store'])
    ->middleware(ProtectAgainstSpam::class)
    ->name('enterprise-inquiries.store');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('terms/accept', [TermsAcceptController::class, 'show'])->name('terms.accept');
    Route::post('terms/accept', [TermsAcceptController::class, 'store'])->name('terms.accept.store');

    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('chat', fn () => Inertia::render('chat/index'))->name('chat');

    Route::get('announcements', [AnnouncementsTableController::class, 'index'])->name('announcements.table');
    Route::get('contacts', [ContactController::class, 'index'])->name('contacts.index');
    Route::get('tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::get('campaign-sites', [CampaignSiteController::class, 'index'])->name('campaign-sites.index');
    Route::get('campaign-sites/{campaign}/edit-puck', [CampaignSiteController::class, 'editPuck'])->name('campaign-sites.edit-puck');
    Route::post('campaign-sites/{campaign}/puck-save', [CampaignSiteController::class, 'savePuck'])->name('campaign-sites.puck-save');
    Route::get('projects', [ProjectsTableController::class, 'index'])->name('projects.table');
    Route::get('lots', [LotsTableController::class, 'index'])->name('lots.table');
    Route::get('reservations', [PropertyReservationController::class, 'index'])->name('reservations.index');
    Route::get('enquiries', [PropertyEnquiryController::class, 'index'])->name('enquiries.index');
    Route::get('searches', [PropertySearchController::class, 'index'])->name('searches.index');
    Route::get('sales', [SaleController::class, 'index'])->name('sales.index');
    Route::get('commissions', [CommissionController::class, 'index'])->name('commissions.index');
    Route::get('pipeline', [PipelineController::class, 'index'])->name('pipeline.index');
    Route::get('funnel', [FunnelController::class, 'index'])->name('funnel.index');
    Route::get('member-listings', [MemberListingsController::class, 'index'])->name('member-listings.index');
    Route::post('contacts/bulk-update', [ContactController::class, 'bulkUpdate'])->name('contacts.bulk-update');
    Route::patch('contacts/{contact}/quick-edit', [ContactController::class, 'quickEdit'])->name('contacts.quick-edit');
    Route::post('reservations/bulk-update', [PropertyReservationController::class, 'bulkUpdate'])->name('reservations.bulk-update');
    Route::patch('reservations/{reservation}/quick-edit', [PropertyReservationController::class, 'quickEdit'])->name('reservations.quick-edit');
    Route::post('lots/{lot}/push', [LotsTableController::class, 'push'])->name('lots.push');
    Route::post('projects/{project}/push', [ProjectsTableController::class, 'push'])->name('projects.push');
    // Lead generation routes
    Route::get('lead-generation', [LeadGenerationController::class, 'index'])->name('lead-generation.index');
    Route::post('lead-generation/landing-page-copy', [LeadGenerationController::class, 'landingPageCopy'])->name('lead-generation.landing-page-copy');
    Route::post('lead-generation/lead-brief/{contact}', [LeadGenerationController::class, 'leadBrief'])->name('lead-generation.lead-brief');
    Route::post('lead-generation/score-and-route/{contact}', [LeadGenerationController::class, 'scoreAndRoute'])->name('lead-generation.score-and-route');
    Route::get('lead-generation/coaching/{contact}', [LeadGenerationController::class, 'coaching'])->name('lead-generation.coaching');
    Route::get('website-index', [WebsiteController::class, 'index'])->middleware('tenant')->name('website-index.index');
    Route::post('website-index', [WebsiteController::class, 'store'])->middleware('tenant')->name('website-index.store');
    Route::delete('website-index/{website}', [WebsiteController::class, 'destroy'])->middleware('tenant')->name('website-index.destroy');

    Route::get('nurture-sequences', [NurtureSequenceController::class, 'index'])->name('nurture-sequences.index');
    Route::post('nurture-sequences', [NurtureSequenceController::class, 'store'])->name('nurture-sequences.store');
    Route::post('nurture-sequences/enroll/{contact}', [NurtureSequenceController::class, 'enroll'])->name('nurture-sequences.enroll');
    Route::get('cold-outreach', [ColdOutreachController::class, 'index'])->name('cold-outreach.index');
    Route::post('cold-outreach/generate', [ColdOutreachController::class, 'generate'])->name('cold-outreach.generate');
    Route::post('lead-capture', [LeadCaptureController::class, 'store'])->name('lead-capture.store');
    Route::post('lead-capture/bulk', [LeadCaptureController::class, 'bulkStore'])->name('lead-capture.bulk');

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/{type}', [ReportController::class, 'show'])->name('reports.show');
    Route::get('categories', [CategoriesTableController::class, 'index'])->name('categories.table');
    Route::get('posts', [PostsTableController::class, 'index'])->name('posts.table');
    Route::get('users', [UsersTableController::class, 'index'])->name('users.table');
    Route::post('users/bulk-soft-delete', [UsersTableController::class, 'bulkSoftDelete'])->name('users.bulk-soft-delete');
    Route::patch('users/batch-update', [UsersTableController::class, 'batchUpdate'])->name('users.batch-update');
    Route::post('users/{user}/duplicate', [UsersTableController::class, 'duplicate'])->name('users.duplicate');
    Route::get('users/{user}', [UsersTableController::class, 'show'])->name('users.show');
    Route::post('users/{id}/restore', [UsersTableController::class, 'restore'])->name('users.restore');
    Route::delete('users/{id}/force-delete', [UsersTableController::class, 'forceDelete'])->name('users.force-delete');

    Route::middleware('tenancy.enabled')->group(function (): void {
        Route::post('organizations/switch', OrganizationSwitchController::class)
            ->middleware('throttle:20,1')
            ->name('organizations.switch');
        Route::get('organizations/list', [OrganizationsTableController::class, 'index'])->name('organizations.list');
        Route::resource('organizations', OrganizationController::class)->except(['edit']);
        Route::get('organizations/{organization}/edit', [OrganizationController::class, 'edit'])->name('organizations.edit');
        Route::get('organizations/{organization}/members', [OrganizationMemberController::class, 'index'])->name('organizations.members.index');
        Route::put('organizations/{organization}/members/{member}', [OrganizationMemberController::class, 'update'])->name('organizations.members.update')->scopeBindings();
        Route::delete('organizations/{organization}/members/{member}', [OrganizationMemberController::class, 'destroy'])->name('organizations.members.destroy')->scopeBindings();
        Route::post('organizations/{organization}/invitations', [OrganizationInvitationController::class, 'store'])->name('organizations.invitations.store');
        Route::delete('organizations/{organization}/invitations/{invitation}', [OrganizationInvitationController::class, 'destroy'])->name('organizations.invitations.destroy')->scopeBindings();
        Route::put('organizations/{organization}/invitations/{invitation}/resend', [OrganizationInvitationController::class, 'update'])->name('organizations.invitations.resend')->scopeBindings();
    });

    Route::get('search', SearchController::class)->middleware('tenant')->name('search');

    Route::middleware('tenant')->group(function (): void {
        Route::get('billing', [BillingDashboardController::class, 'index'])->name('billing.index');
        Route::get('billing/credits', [CreditController::class, 'index'])->name('billing.credits.index');
        Route::post('billing/credits/purchase', [CreditController::class, 'purchase'])->name('billing.credits.purchase');
        Route::post('billing/credits/checkout/lemon-squeezy', [CreditController::class, 'checkoutLemonSqueezy'])->name('billing.credits.checkout.lemon-squeezy');
        Route::get('billing/invoices', [InvoiceController::class, 'index'])->name('billing.invoices.index');
        Route::get('billing/invoices/{invoice}', [InvoiceController::class, 'download'])->name('billing.invoices.download');
        Route::get('billing/stub-return', fn () => redirect()->route('reservations.index'))->name('billing.stub-return');
    });

    Route::middleware(['tenant', 'permission:org.settings.manage'])->group(function (): void {
        Route::get('settings/branding', [BrandingController::class, 'edit'])->name('settings.branding.edit');
        Route::put('settings/branding', [BrandingController::class, 'update'])->name('settings.branding.update');
        Route::post('settings/branding/user-controls', OrgBrandingUserControlsController::class)->name('settings.branding.user-controls');
        Route::get('settings/audit-log', AuditLogController::class)->name('settings.audit-log');

        Route::get('settings/features', [OrgFeaturesController::class, 'show'])->name('settings.features.show');
        Route::post('settings/features', [OrgFeaturesController::class, 'update'])->name('settings.features.update');

        Route::get('settings/roles', [OrgRolesController::class, 'index'])->name('settings.roles.index');
        Route::post('settings/roles', [OrgRolesController::class, 'store'])->name('settings.roles.store');
        Route::delete('settings/roles/{role}', [OrgRolesController::class, 'destroy'])->name('settings.roles.destroy');

        Route::get('settings/general', [OrgSlugController::class, 'show'])->name('settings.general.show');
        Route::patch('settings/general/slug', [OrgSlugController::class, 'update'])->name('settings.general.slug.update');
        Route::get('settings/domains', [OrgDomainsController::class, 'show'])->name('settings.domains.show');
        Route::post('settings/domains', [OrgDomainsController::class, 'store'])->name('settings.domains.store');
        Route::delete('settings/domains/{domain}', [OrgDomainsController::class, 'destroy'])->name('settings.domains.destroy');
        Route::post('settings/domains/{domain}/verify', [OrgDomainsController::class, 'verify'])->name('settings.domains.verify');
    });

    Route::middleware('tenant')->group(function (): void {
        Route::get('pages', [PageController::class, 'index'])->name('pages.index');
        Route::get('pages/create', [PageController::class, 'create'])->name('pages.create');
        Route::post('pages', [PageController::class, 'store'])->name('pages.store')->middleware('throttle:30,1');
        Route::get('pages/{page}/edit', [PageController::class, 'edit'])->name('pages.edit');
        Route::put('pages/{page}', [PageController::class, 'update'])->name('pages.update')->middleware('throttle:30,1');
        Route::get('pages/{page}/preview', [PageController::class, 'preview'])->name('pages.preview');
        Route::post('pages/{page}/duplicate', [PageController::class, 'duplicate'])->name('pages.duplicate');
        Route::delete('pages/{page}', [PageController::class, 'destroy'])->name('pages.destroy');
        Route::get('p/{slug}', [PageViewController::class, 'show'])->name('pages.show')->middleware('throttle:120,1');
    });

    Route::get('profile/export-pdf', App\Http\Controllers\ProfileExportPdfController::class)
        ->middleware('feature:profile_pdf_export')
        ->name('profile.export-pdf');

    // AI Core routes
    Route::get('ai/bot', [BotV2Controller::class, 'index'])->name('ai.bot.index');
    Route::post('ai/bot/chat', [BotV2Controller::class, 'chat'])->name('ai.bot.chat');
    Route::get('ai/concierge', [ConciergeController::class, 'index'])->name('ai.concierge.index');
    Route::post('ai/concierge/match', [ConciergeController::class, 'match'])->name('ai.concierge.match');
    Route::get('ai/suggestions/{contact}', [PredictiveSuggestionsController::class, 'show'])->name('ai.suggestions.show');
    Route::post('ai/suggestions/{contact}/generate', [PredictiveSuggestionsController::class, 'generate'])->name('ai.suggestions.generate');
    Route::get('ai/summaries/{type}/{id}', [AiSummaryController::class, 'show'])->name('ai.summaries.show');
    Route::post('ai/summaries/{type}/{id}', [AiSummaryController::class, 'generate'])->name('ai.summaries.generate');
    Route::get('funnel/templates', [FunnelTemplateController::class, 'index'])->name('funnel.templates.index');
    Route::post('funnel/templates', [FunnelTemplateController::class, 'store'])->name('funnel.templates.store');
    Route::post('funnel/templates/{template}/enroll/{contact}', [FunnelTemplateController::class, 'enroll'])->name('funnel.templates.enroll');
    Route::get('ai/calls', [VapiController::class, 'index'])->name('ai.calls.index');

    // Phase 2 — Property, Builder & Push Portal (US-012)
    Route::get('agent-portal', [AgentPortalController::class, 'index'])->name('agent-portal.index');
    Route::post('agent-portal/schedule', [AgentPortalController::class, 'schedule'])->name('agent-portal.schedule');

    Route::get('builder-portal', [BuilderPortalController::class, 'index'])->name('builder-portal.index');
    Route::post('builder-portal', [BuilderPortalController::class, 'store'])->name('builder-portal.store');
    Route::get('builder-portal/{portal}', [BuilderPortalController::class, 'show'])->name('builder-portal.show');

    Route::get('inventory', [InventoryApiController::class, 'index'])->name('inventory.index');
    Route::post('inventory/import', [InventoryApiController::class, 'import'])->name('inventory.import');
    Route::get('inventory/template/{type}', [InventoryApiController::class, 'template'])->name('inventory.template');

    Route::get('flyers/{flyer}/edit-puck', [FlyerController::class, 'editPuck'])->name('flyers.edit-puck');
    Route::post('flyers/{flyer}/puck-save', [FlyerController::class, 'savePuck'])->name('flyers.puck-save');
    Route::post('flyers/{flyer}/export-pdf', [FlyerController::class, 'exportPdf'])->name('flyers.export-pdf');

    Route::get('puck-templates', [PuckTemplateController::class, 'index'])->name('puck-templates.index');
    Route::post('puck-templates', [PuckTemplateController::class, 'store'])->name('puck-templates.store');
    Route::get('puck-templates/{template}/edit', [PuckTemplateController::class, 'edit'])->name('puck-templates.edit');

    // US-013 — CRM Collaboration & Automation
    Route::get('custom-fields', [CustomFieldController::class, 'index'])->name('custom-fields.index');
    Route::post('custom-fields', [CustomFieldController::class, 'store'])->name('custom-fields.store');
    Route::patch('custom-fields/{customField}', [CustomFieldController::class, 'update'])->name('custom-fields.update');
    Route::delete('custom-fields/{customField}', [CustomFieldController::class, 'destroy'])->name('custom-fields.destroy');
    Route::get('custom-fields/values', [CustomFieldController::class, 'values'])->name('custom-fields.values');

    Route::get('automation-rules', [AutomationRuleController::class, 'index'])->name('automation-rules.index');
    Route::post('automation-rules', [AutomationRuleController::class, 'store'])->name('automation-rules.store');
    Route::patch('automation-rules/{automationRule}', [AutomationRuleController::class, 'update'])->name('automation-rules.update');
    Route::delete('automation-rules/{automationRule}', [AutomationRuleController::class, 'destroy'])->name('automation-rules.destroy');

    Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    Route::post('analytics/nl-query', [AnalyticsController::class, 'nlQuery'])->name('analytics.nl-query');

    Route::get('sales/{sale}/forecast', [DealForecastController::class, 'show'])->name('sales.forecast');

    // Marketing & Content Tools (US-014)
    Route::get('ad-templates', [AdTemplateController::class, 'index'])->name('ad-templates.index');
    Route::post('ad-templates', [AdTemplateController::class, 'store'])->name('ad-templates.store');
    Route::post('ad-templates/generate-copy', [AdTemplateController::class, 'generateCopy'])->name('ad-templates.generate-copy');
    Route::delete('ad-templates/{adTemplate}', [AdTemplateController::class, 'destroy'])->name('ad-templates.destroy');

    Route::get('brochure-layouts', [BrochureLayoutController::class, 'index'])->name('brochure-layouts.index');
    Route::post('brochure-layouts', [BrochureLayoutController::class, 'store'])->name('brochure-layouts.store');
    Route::post('brochure-layouts/flyers/{flyer}/generate-pdf', [BrochureLayoutController::class, 'generatePdf'])->name('brochure-layouts.generate-pdf');

    Route::get('retargeting-pixels', [RetargetingPixelController::class, 'index'])->name('retargeting-pixels.index');
    Route::post('retargeting-pixels', [RetargetingPixelController::class, 'store'])->name('retargeting-pixels.store');
    Route::patch('retargeting-pixels/{retargetingPixel}', [RetargetingPixelController::class, 'update'])->name('retargeting-pixels.update');
    Route::delete('retargeting-pixels/{retargetingPixel}', [RetargetingPixelController::class, 'destroy'])->name('retargeting-pixels.destroy');

    Route::get('email-campaigns', [EmailCampaignController::class, 'index'])->name('email-campaigns.index');
    Route::post('email-campaigns', [EmailCampaignController::class, 'store'])->name('email-campaigns.store');
    Route::post('email-campaigns/{emailCampaign}/personalise', [EmailCampaignController::class, 'personalise'])->name('email-campaigns.personalise');
    Route::post('email-campaigns/{emailCampaign}/send', [EmailCampaignController::class, 'send'])->name('email-campaigns.send');
    Route::delete('email-campaigns/{emailCampaign}', [EmailCampaignController::class, 'destroy'])->name('email-campaigns.destroy');

    Route::get('landing-pages', [LandingPageController::class, 'index'])->name('landing-pages.index');
    Route::post('landing-pages/generate', [LandingPageController::class, 'generate'])->name('landing-pages.generate');
    Route::patch('landing-pages/{landingPageTemplate}', [LandingPageController::class, 'update'])->name('landing-pages.update');
    Route::delete('landing-pages/{landingPageTemplate}', [LandingPageController::class, 'destroy'])->name('landing-pages.destroy');

    // Deal Tracker (US-015)
    Route::get('deal-tracker', [DealTrackerController::class, 'index'])->name('deal-tracker.index');
    Route::patch('deal-tracker/{reservation}/stage', [DealTrackerController::class, 'stageUpdate'])->name('deal-tracker.stage-update');
    Route::get('sales/{sale}/payment-stages', [PaymentStageController::class, 'index'])->name('payment-stages.index');
    Route::post('sales/{sale}/payment-stages', [PaymentStageController::class, 'store'])->name('payment-stages.store');
    Route::patch('payment-stages/{paymentStage}', [PaymentStageController::class, 'update'])->name('payment-stages.update');
    Route::delete('payment-stages/{paymentStage}', [PaymentStageController::class, 'destroy'])->name('payment-stages.destroy');
    Route::get('reservations/{reservation}/pinned-notes', [PinnedNoteController::class, 'indexForReservation'])->name('pinned-notes.reservation.index');
    Route::post('reservations/{reservation}/pinned-notes', [PinnedNoteController::class, 'storeForReservation'])->name('pinned-notes.reservation.store');
    Route::get('sales/{sale}/pinned-notes', [PinnedNoteController::class, 'indexForSale'])->name('pinned-notes.sale.index');
    Route::post('sales/{sale}/pinned-notes', [PinnedNoteController::class, 'storeForSale'])->name('pinned-notes.sale.store');
    Route::delete('pinned-notes/{pinnedNote}', [PinnedNoteController::class, 'destroy'])->name('pinned-notes.destroy');
    Route::get('deal-documents', [DealDocumentController::class, 'index'])->name('deal-documents.index');
    Route::post('deal-documents', [DealDocumentController::class, 'store'])->name('deal-documents.store');
    Route::delete('deal-documents/{dealDocument}', [DealDocumentController::class, 'destroy'])->name('deal-documents.destroy');

    // Xero Integration (US-017)
    Route::get('/xero', [XeroController::class, 'index'])->name('xero.index');
    Route::get('/xero/connect', [XeroController::class, 'connect'])->name('xero.connect');
    Route::get('/xero/callback', [XeroController::class, 'callback'])->name('xero.callback');
    Route::post('/xero/disconnect', [XeroController::class, 'disconnect'])->name('xero.disconnect');
    Route::post('/xero/sync-contacts', [XeroController::class, 'syncContacts'])->name('xero.sync-contacts');
    Route::post('/xero/sync-invoices', [XeroController::class, 'syncInvoices'])->name('xero.sync-invoices');
});

Route::get('/api/slug-availability', SlugAvailabilityController::class)
    ->middleware('auth')
    ->name('api.slug-availability');

Route::post('api/login-event', [LoginEventController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('api.login-event');

Route::get('/internal/caddy/ask', CaddyAskController::class)
    ->middleware(InternalRequestMiddleware::class)
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->name('internal.caddy.ask');

Route::post('webhooks/stripe', StripeWebhookController::class)->name('webhooks.stripe')->withoutMiddleware([ValidateCsrfToken::class]);
Route::post('webhooks/paddle', PaddleWebhookController::class)->name('webhooks.paddle')->withoutMiddleware([ValidateCsrfToken::class]);
Route::post('webhooks/vapi', [VapiController::class, 'webhook'])->name('webhooks.vapi')->withoutMiddleware([ValidateCsrfToken::class]);
Route::post('/xero/webhook', [XeroController::class, 'webhook'])->name('xero.webhook')->withoutMiddleware([ValidateCsrfToken::class]);

// Signup & Onboarding (US-018) — public signup flow, auth onboarding
Route::middleware('guest')->prefix('signup')->name('signup.')->group(function (): void {
    Route::get('/', [SignupController::class, 'index'])->name('index');
    Route::get('/register', [SignupController::class, 'create'])->name('register');
    Route::post('/provision', [SignupController::class, 'provision'])->middleware(ProtectAgainstSpam::class)->name('provision');
});

Route::get('/signup/complete', [SignupController::class, 'complete'])
    ->middleware('auth')
    ->name('signup.complete');

Route::middleware('auth')->prefix('signup')->name('signup.')->group(function (): void {
    Route::get('/onboarding', [SignupController::class, 'onboarding'])->name('onboarding');
    Route::post('/onboarding/{stepKey}/complete', [SignupController::class, 'completeStep'])->name('onboarding.complete-step');
});

// Public campaign site routes (no auth)
Route::get('w/{uuid}', [PublicSiteController::class, 'show'])->name('public.campaign-site');
Route::get('survey/{uuid}', [PublicSiteController::class, 'survey'])->name('public.survey');
Route::post('survey/{uuid}', [PublicSiteController::class, 'submitSurvey'])->name('public.survey.submit')->withoutMiddleware([ValidateCsrfToken::class]);

// WordPress provisioner API (auth:sanctum)
Route::middleware('auth:sanctum')->prefix('api/provisioner')->name('provisioner.')->group(function (): void {
    Route::get('wordpress-sites/pending', [ProvisionerApiController::class, 'pending'])->name('pending');
    Route::get('wordpress-sites/removing', [ProvisionerApiController::class, 'removing'])->name('removing');
    Route::post('wordpress-sites/{site}/callback', [ProvisionerApiController::class, 'callback'])->name('callback')->withoutMiddleware([ValidateCsrfToken::class]);
    Route::get('subscribers/{api_key}', [ProvisionerApiController::class, 'subscriberDetail'])->name('subscriber');
});

Route::middleware(['auth', 'feature:onboarding'])->group(function (): void {
    Route::get('onboarding', [OnboardingController::class, 'show'])->name('onboarding');
    Route::post('onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('personal-data-exports/{zipFilename}', [Spatie\PersonalDataExport\Http\Controllers\PersonalDataExportController::class, 'export'])
        ->name('personal-data-exports');

    Route::delete('user', [UserController::class, 'destroy'])->name('user.destroy');

    Route::patch('user/preferences', [UserPreferencesController::class, 'update'])->name('user.preferences.update');

    Route::post('org/theme', [OrgThemeController::class, 'save'])->name('org.theme.save');
    Route::delete('org/theme', [OrgThemeController::class, 'reset'])->name('org.theme.reset');
    Route::post('org/theme/analyze-logo', [OrgThemeController::class, 'analyzeLogo'])->name('org.theme.analyze-logo');

    Route::redirect('settings', '/settings/profile')->name('settings');
    Route::get('settings/profile', [UserProfileController::class, 'edit'])->name('user-profile.edit');
    Route::patch('settings/profile', [UserProfileController::class, 'update'])->name('user-profile.update');

    Route::get('settings/password', [UserPasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [UserPasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');

    Route::get('settings/appearance', fn () => Inertia::render('appearance/update'))
        ->middleware('feature:appearance_settings')
        ->name('appearance.edit');

    Route::get('settings/personal-data-export', fn () => Inertia::render('settings/personal-data-export'))
        ->middleware('feature:personal_data_export')
        ->name('personal-data-export.edit');
    Route::post('settings/personal-data-export', PersonalDataExportController::class)
        ->middleware(['feature:personal_data_export', 'throttle:3,1'])
        ->name('personal-data-export.store');

    Route::get('settings/two-factor', [UserTwoFactorAuthenticationController::class, 'show'])
        ->middleware('feature:two_factor_auth')
        ->name('two-factor.show');

    Route::get('settings/achievements', [AchievementsController::class, 'show'])
        ->middleware('feature:gamification')
        ->name('achievements.show');

    Route::get('settings/notifications', [NotificationPreferencesController::class, 'show'])->name('settings.notifications.show');
    Route::patch('settings/notifications', [NotificationPreferencesController::class, 'update'])->name('settings.notifications.update');

    Route::prefix('notifications')->name('notifications.')->group(function (): void {
        Route::get('/', IndexNotificationsController::class)->name('index');
        Route::post('{notification}/read', MarkNotificationReadController::class)->name('read');
        Route::post('read-all', MarkAllNotificationsReadController::class)->name('read-all');
        Route::delete('{notification}', DeleteNotificationController::class)->name('delete');
        Route::delete('/', ClearAllNotificationsController::class)->name('clear');
    });
});

Route::middleware('guest')->group(function (): void {
    Route::get('register', [UserController::class, 'create'])
        ->middleware('registration.enabled')
        ->name('register');
    Route::post('register', [UserController::class, 'store'])
        ->middleware(['registration.enabled', ProtectAgainstSpam::class, 'throttle:registration'])
        ->name('register.store');

    Route::get('reset-password/{token}', [UserPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('reset-password', [UserPasswordController::class, 'store'])
        ->middleware('throttle:password-reset-submit')
        ->name('password.store');

    Route::get('forgot-password', [UserEmailResetNotificationController::class, 'create'])
        ->name('password.request');
    Route::post('forgot-password', [UserEmailResetNotificationController::class, 'store'])
        ->middleware('throttle:password-reset-request')
        ->name('password.email');

    Route::get('login', [SessionController::class, 'create'])
        ->name('login');
    Route::post('login', [SessionController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');
});

Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])->name('auth.social.redirect');
Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])->name('auth.social.callback');

Route::middleware('auth')->group(function (): void {
    Route::get('verify-email', [UserEmailVerificationNotificationController::class, 'create'])
        ->name('verification.notice');
    Route::post('email/verification-notification', [UserEmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
    Route::get('verify-email/{id}/{hash}', [UserEmailVerificationController::class, 'update'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('logout', [SessionController::class, 'destroy'])
        ->name('logout');
});
