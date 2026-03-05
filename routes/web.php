<?php

declare(strict_types=1);

/*
 * All routes must have ->name() for RBAC (permission:sync-routes). Run:
 *   php artisan permission:sync-routes
 * after adding or changing routes.
 */

use App\Http\Controllers\Alerts\AlertController;
use App\Http\Controllers\Billing\BillingDashboardController;
use App\Http\Controllers\Billing\CreditController;
use App\Http\Controllers\Billing\InvoiceController;
use App\Http\Controllers\Billing\PaddleWebhookController;
use App\Http\Controllers\Billing\PricingController;
use App\Http\Controllers\Billing\StripeWebhookController;
use App\Http\Controllers\Blog\BlogController;
use App\Http\Controllers\Changelog\ChangelogController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ContactSubmissionController;
use App\Http\Controllers\CookieConsentController;
use App\Http\Controllers\DailyVehicleEntryController;
use App\Http\Controllers\EnterpriseInquiryController;
use App\Http\Controllers\GenerateDispatchReportController;
use App\Http\Controllers\HelpCenter\HelpCenterController;
use App\Http\Controllers\HelpCenter\RateHelpArticleController;
use App\Http\Controllers\Indents\IndentsController;
use App\Http\Controllers\InvitationAcceptController;
use App\Http\Controllers\LoadersController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\OrganizationInvitationController;
use App\Http\Controllers\OrganizationMemberController;
use App\Http\Controllers\OrganizationSwitchController;
use App\Http\Controllers\PenaltyTypesController;
use App\Http\Controllers\PersonalDataExportController;
use App\Http\Controllers\PowerPlantController;
use App\Http\Controllers\PowerplantSidingDistancesController;
use App\Http\Controllers\RailwayReceipts\PenaltyController;
use App\Http\Controllers\RailwayReceipts\RrDocumentController;
use App\Http\Controllers\Rakes\RakeGuardInspectionController;
use App\Http\Controllers\Rakes\RakeLoadController;
use App\Http\Controllers\Rakes\RakesController;
use App\Http\Controllers\Rakes\RakeTxrController;
use App\Http\Controllers\Rakes\RakeWagonController;
use App\Http\Controllers\Rakes\RakeWeighmentController;
use App\Http\Controllers\Reconciliation\PowerPlantReceiptController;
use App\Http\Controllers\Reconciliation\ReconciliationController;
use App\Http\Controllers\Reports\ReportsController;
use App\Http\Controllers\RoadDispatch\VehicleArrivalController;
use App\Http\Controllers\RoadDispatch\VehicleUnloadController;
use App\Http\Controllers\SectionTimersController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\Settings\AchievementsController;
use App\Http\Controllers\SidingsController;
use App\Http\Controllers\SidingSwitchController;
use App\Http\Controllers\TermsAcceptController;
use App\Http\Controllers\TxrController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserEmailResetNotificationController;
use App\Http\Controllers\UserEmailVerificationController;
use App\Http\Controllers\UserEmailVerificationNotificationController;
use App\Http\Controllers\UserPasswordController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\UserTwoFactorAuthenticationController;
use App\Http\Controllers\VehicleDispatchController;
use App\Http\Controllers\VehicleWorkorderController;
use App\Http\Controllers\WagonUnfitController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Spatie\Honeypot\ProtectAgainstSpam;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

Route::get('/favicon.ico', function (): BinaryFileResponse|RedirectResponse {
    $path = public_path('favicon.ico');

    if (File::exists($path)) {
        return response()->file($path, ['Content-Type' => 'image/x-icon']);
    }

    return redirect('/favicon.svg', 302);
})->name('favicon');

Route::get('robots.txt', function (): Illuminate\Http\Response {
    $base = mb_rtrim(config('app.url'), '/');

    return response("User-agent: *\nDisallow:\n\nSitemap: {$base}/sitemap.xml\n", 200, [
        'Content-Type' => 'text/plain',
    ]);
})->name('robots');

Route::get('up', function (): Illuminate\Http\JsonResponse {
    $checks = ['app' => true];
    try {
        Illuminate\Support\Facades\DB::connection()->getPdo();
        $checks['database'] = true;
    } catch (Throwable) {
        $checks['database'] = false;
    }
    $ok = ! in_array(false, $checks, true);

    return response()->json(['status' => $ok ? 'ok' : 'degraded', 'checks' => $checks], $ok ? 200 : 503);
})->name('up');

Route::get('/', function (): RedirectResponse {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

// Invitation accept (public show, auth store)
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

    Route::get('dashboard', App\Http\Controllers\Dashboard\ExecutiveDashboardController::class)->name('dashboard');

    // Organizations (multi-tenancy; routes redirect to dashboard when tenancy disabled)
    Route::middleware('tenancy.enabled')->group(function (): void {
        Route::post('organizations/switch', OrganizationSwitchController::class)->name('organizations.switch');
        Route::resource('organizations', OrganizationController::class)->except(['edit']);
        Route::get('organizations/{organization}/edit', [OrganizationController::class, 'edit'])->name('organizations.edit');
        Route::get('organizations/{organization}/members', [OrganizationMemberController::class, 'index'])->name('organizations.members.index');
        Route::put('organizations/{organization}/members/{member}', [OrganizationMemberController::class, 'update'])->name('organizations.members.update')->scopeBindings();
        Route::delete('organizations/{organization}/members/{member}', [OrganizationMemberController::class, 'destroy'])->name('organizations.members.destroy')->scopeBindings();
        Route::post('organizations/{organization}/invitations', [OrganizationInvitationController::class, 'store'])->name('organizations.invitations.store');
        Route::delete('organizations/{organization}/invitations/{invitation}', [OrganizationInvitationController::class, 'destroy'])->name('organizations.invitations.destroy')->scopeBindings();
        Route::put('organizations/{organization}/invitations/{invitation}/resend', [OrganizationInvitationController::class, 'update'])->name('organizations.invitations.resend')->scopeBindings();
    });

    // Billing (org-scoped; tenant middleware ensures current org)
    Route::middleware('tenant')->group(function (): void {
        Route::get('billing', [BillingDashboardController::class, 'index'])->name('billing.index');
        Route::get('billing/credits', [CreditController::class, 'index'])->name('billing.credits.index');
        Route::post('billing/credits/purchase', [CreditController::class, 'purchase'])->name('billing.credits.purchase');
        Route::post('billing/credits/checkout/lemon-squeezy', [CreditController::class, 'checkoutLemonSqueezy'])->name('billing.credits.checkout.lemon-squeezy');
        Route::get('billing/invoices', [InvoiceController::class, 'index'])->name('billing.invoices.index');
        Route::get('billing/invoices/{invoice}', [InvoiceController::class, 'download'])->name('billing.invoices.download');
    });

    // Siding context switcher
    Route::post('siding/switch', SidingSwitchController::class)->name('siding.switch');

    // RRMCS Routes (Railway Rake Management Control System)
    Route::get('rakes', [RakesController::class, 'index'])->name('rakes.index');
    Route::get('rakes/{rake}', [RakesController::class, 'show'])->name('rakes.show');
    Route::get('rakes/{rake}/edit', [RakesController::class, 'edit'])->name('rakes.edit');
    Route::put('rakes/{rake}', [RakesController::class, 'update'])->name('rakes.update');
    Route::delete('rakes/{rake}', [RakesController::class, 'destroy'])->name('rakes.destroy');
    Route::post('rakes/{rake}/generate-wagons', [RakesController::class, 'generateWagons'])->name('rakes.generate-wagons');
    Route::put('rakes/{rake}/wagons/{wagon}', [RakeWagonController::class, 'update'])->name('rakes.wagons.update');
    Route::put('rakes/{rake}/txr', [RakeTxrController::class, 'update'])->name('rakes.txr.update');
    Route::post('rakes/{rake}/txr/start', [RakeTxrController::class, 'start'])->name('rakes.txr.start');
    Route::post('rakes/{rake}/txr/end', [RakeTxrController::class, 'end'])->name('rakes.txr.end');
    Route::post('rakes/{rake}/txr/unfit-logs', [RakeTxrController::class, 'storeUnfitLogs'])->name('rakes.txr.unfit-logs');
    // New TXR header routes
    Route::post('rakes/{rake}/txr', [TxrController::class, 'store'])->name('rakes.txr.store');
    // Unfit wagon routes
    Route::post('txr/{txr}/unfit-wagons', [WagonUnfitController::class, 'store'])->name('txr.unfit-wagons.store');
    Route::get('rakes/{rake}/load', [RakeLoadController::class, 'show'])->name('rakes.load.show');
    Route::post('rakes/{rake}/load/confirm-placement', [RakeLoadController::class, 'confirmPlacement'])->name('rakes.load.confirm-placement');
    Route::post('rakes/{rake}/load/wagon', [RakeLoadController::class, 'loadWagon'])->name('rakes.load.wagon');
    Route::post('rakes/{rake}/load/wagons', [RakeLoadController::class, 'storeWagonLoadings'])->name('rakes.load.wagons');
    Route::post('rakes/{rake}/load/guard-inspection', [RakeLoadController::class, 'recordGuardInspection'])->name('rakes.load.guard-inspection');
    Route::post('rakes/{rake}/load/weighment', [RakeLoadController::class, 'recordWeighment'])->name('rakes.load.weighment');
    Route::post('rakes/{rake}/load/confirm-dispatch', [RakeLoadController::class, 'confirmDispatch'])->name('rakes.load.confirm-dispatch');
    Route::post('rakes/{rake}/weighments', [RakeWeighmentController::class, 'store'])->name('rakes.weighments.store');
    Route::get('rakes/{rake}/comparison', [RakesController::class, 'comparison'])->name('rakes.comparison');
    Route::post('rakes/{rake}/guard-inspection', [RakeGuardInspectionController::class, 'store'])->name('rakes.guard-inspection.store');
    Route::get('indents', [IndentsController::class, 'index'])->name('indents.index');
    Route::get('indents/create', [IndentsController::class, 'create'])->name('indents.create');
    Route::post('indents', [IndentsController::class, 'store'])->name('indents.store');
    Route::get('indents/{indent}', [IndentsController::class, 'show'])->name('indents.show');
    Route::get('indents/{indent}/edit', [IndentsController::class, 'edit'])->name('indents.edit');
    Route::put('indents/{indent}', [IndentsController::class, 'update'])->name('indents.update');
    Route::get('indents/{indent}/create-rake', [IndentsController::class, 'createRake'])->name('indents.create-rake');
    Route::post('indents/{indent}/store-rake', [IndentsController::class, 'storeRakeFromIndent'])->name('indents.store-rake');

    // Master Data
    Route::prefix('master-data')->name('master-data.')->group(function (): void {
        Route::resource('power-plants', PowerPlantController::class);
        Route::resource('sidings', SidingsController::class);
        Route::resource('loaders', LoadersController::class);
        Route::resource('penalty-types', PenaltyTypesController::class);
        Route::resource('section-timers', SectionTimersController::class);
        Route::resource('distance-matrix', PowerplantSidingDistancesController::class)->names([
            'index' => 'master-data.distance-matrix.index',
            'create' => 'master-data.distance-matrix.create',
            'store' => 'master-data.distance-matrix.store',
            'show' => 'master-data.distance-matrix.show',
            'edit' => 'master-data.distance-matrix.edit',
            'update' => 'master-data.distance-matrix.update',
            'destroy' => 'master-data.distance-matrix.destroy',
        ]);
    });

    // Road Dispatch (vehicle arrivals and unloads)
    Route::get('road-dispatch/arrivals', [VehicleArrivalController::class, 'index'])->name('road-dispatch.arrivals.index');
    Route::get('road-dispatch/arrivals/create', [VehicleArrivalController::class, 'create'])->name('road-dispatch.arrivals.create');
    Route::post('road-dispatch/arrivals', [VehicleArrivalController::class, 'store'])->name('road-dispatch.arrivals.store');
    Route::get('road-dispatch/arrivals/{arrival}', [VehicleArrivalController::class, 'show'])->name('road-dispatch.arrivals.show');
    Route::get('road-dispatch/arrivals/{arrival}/unload', [VehicleArrivalController::class, 'unload'])->name('road-dispatch.arrivals.unload');
    Route::get('road-dispatch/unloads', [VehicleUnloadController::class, 'index'])->name('road-dispatch.unloads.index');
    Route::get('road-dispatch/unloads/create', [VehicleUnloadController::class, 'create'])->name('road-dispatch.unloads.create');
    Route::post('road-dispatch/unloads', [VehicleUnloadController::class, 'store'])->name('road-dispatch.unloads.store');
    Route::get('road-dispatch/unloads/{unload}', [VehicleUnloadController::class, 'show'])->name('road-dispatch.unloads.show');
    Route::post('road-dispatch/unloads/{unload}/gross-weighment', [VehicleUnloadController::class, 'recordGrossWeighment'])->name('road-dispatch.unloads.gross-weighment');
    Route::post('road-dispatch/unloads/{unload}/start-unload', [VehicleUnloadController::class, 'startUnload'])->name('road-dispatch.unloads.start-unload');
    Route::post('road-dispatch/unloads/{unload}/tare-weighment', [VehicleUnloadController::class, 'recordTareWeighment'])->name('road-dispatch.unloads.tare-weighment');
    Route::post('road-dispatch/unloads/{unload}/complete', [VehicleUnloadController::class, 'complete'])->name('road-dispatch.unloads.complete');
    Route::put('road-dispatch/unloads/{unload}/confirm', [VehicleUnloadController::class, 'confirm'])->name('road-dispatch.unloads.confirm');

    // Daily Vehicle Entries
    Route::get('road-dispatch/daily-vehicle-entries', [DailyVehicleEntryController::class, 'index'])->name('road-dispatch.daily-vehicle-entries.index');
    Route::post('road-dispatch/daily-vehicle-entries', [DailyVehicleEntryController::class, 'store'])->name('road-dispatch.daily-vehicle-entries.store');
    Route::patch('road-dispatch/daily-vehicle-entries/{entry}', [DailyVehicleEntryController::class, 'update'])->name('road-dispatch.daily-vehicle-entries.update');
    Route::delete('road-dispatch/daily-vehicle-entries/{entry}', [DailyVehicleEntryController::class, 'destroy'])->name('road-dispatch.daily-vehicle-entries.destroy');
    Route::post('road-dispatch/daily-vehicle-entries/{entry}/complete', [DailyVehicleEntryController::class, 'markCompleted'])->name('road-dispatch.daily-vehicle-entries.complete');
    Route::get('road-dispatch/daily-vehicle-entries/export', [DailyVehicleEntryController::class, 'export'])->name('road-dispatch.daily-vehicle-entries.export');

    // Vehicle Dispatch Register
    Route::get('vehicle-dispatch', [VehicleDispatchController::class, 'index'])->name('vehicle-dispatch.index');
    Route::put('vehicle-dispatch/{vehicle_dispatch}', [VehicleDispatchController::class, 'update'])->name('vehicle-dispatch.update');
    Route::post('vehicle-dispatch/import', [VehicleDispatchController::class, 'import'])->name('vehicle-dispatch.import');
    Route::post('vehicle-dispatch/save', [VehicleDispatchController::class, 'saveImport'])->name('vehicle-dispatch.save');
    Route::post('dispatch-reports/generate', [GenerateDispatchReportController::class, 'generate'])->name('dispatch-reports.generate');

    // Vehicle Work Orders
    Route::get('vehicle-workorders', [VehicleWorkorderController::class, 'index'])->name('vehicle-workorders.index');
    Route::get('vehicle-workorders/{vehicle_workorder}/edit', [VehicleWorkorderController::class, 'edit'])->name('vehicle-workorders.edit');
    Route::put('vehicle-workorders/{vehicle_workorder}', [VehicleWorkorderController::class, 'update'])->name('vehicle-workorders.update');

    // Railway Receipts (RR) and Penalties
    Route::get('railway-receipts', [RrDocumentController::class, 'index'])->name('railway-receipts.index');
    Route::get('railway-receipts/create', [RrDocumentController::class, 'create'])->name('railway-receipts.create');
    Route::post('railway-receipts', [RrDocumentController::class, 'store'])->name('railway-receipts.store');
    Route::get('railway-receipts/{rrDocument}', [RrDocumentController::class, 'show'])->name('railway-receipts.show');
    Route::put('railway-receipts/{rrDocument}', [RrDocumentController::class, 'update'])->name('railway-receipts.update');
    Route::get('penalties', [PenaltyController::class, 'index'])->name('penalties.index');
    Route::get('penalties/analytics', [PenaltyController::class, 'analytics'])->name('penalties.analytics');
    Route::patch('penalties/{penalty}', [PenaltyController::class, 'update'])->name('penalties.update');
    Route::get('penalties/{penalty}/dispute-recommendation', [PenaltyController::class, 'disputeRecommendation'])->name('penalties.dispute-recommendation');

    // Alerts
    Route::get('alerts', [AlertController::class, 'index'])->name('alerts.index');
    Route::put('alerts/{alert}/resolve', [AlertController::class, 'resolve'])->name('alerts.resolve');

    // Reconciliation
    Route::get('reconciliation', [ReconciliationController::class, 'index'])->name('reconciliation.index');
    Route::get('reconciliation/{rake}', [ReconciliationController::class, 'show'])->name('reconciliation.show');
    Route::get('reconciliation/power-plant-receipts', [PowerPlantReceiptController::class, 'index'])->name('reconciliation.power-plant-receipts.index');
    Route::get('reconciliation/power-plant-receipts/create', [PowerPlantReceiptController::class, 'create'])->name('reconciliation.power-plant-receipts.create');
    Route::post('reconciliation/power-plant-receipts', [PowerPlantReceiptController::class, 'store'])->name('reconciliation.power-plant-receipts.store');

    // Reports
    Route::get('reports', [ReportsController::class, 'index'])->name('reports.index');
    Route::post('reports/generate', [ReportsController::class, 'generate'])->name('reports.generate');

    // AI Chatbot
    Route::get('chat/conversations', [ChatController::class, 'index'])->name('chat.conversations.index');
    Route::get('chat/conversations/{id}', [ChatController::class, 'show'])->name('chat.conversations.show');
    Route::get('chat/demurrage-warnings', [ChatController::class, 'demurrageWarnings'])->name('chat.demurrage-warnings');
    Route::post('chat', [ChatController::class, 'message'])->name('chat.message');
    Route::post('chat/stream', [ChatController::class, 'stream'])->name('chat.stream');

    Route::get('profile/export-pdf', App\Http\Controllers\ProfileExportPdfController::class)
        ->middleware('feature:profile_pdf_export')
        ->name('profile.export-pdf');
});

Route::post('webhooks/stripe', StripeWebhookController::class)->name('webhooks.stripe')->withoutMiddleware([Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);
Route::post('webhooks/paddle', PaddleWebhookController::class)->name('webhooks.paddle')->withoutMiddleware([Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

Route::middleware(['auth', 'feature:onboarding'])->group(function (): void {
    Route::get('onboarding', [OnboardingController::class, 'show'])->name('onboarding');
    Route::post('onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');
});

Route::middleware('auth')->group(function (): void {
    Route::personalDataExports('personal-data-exports');
});

Route::middleware('auth')->group(function (): void {
    // User...
    Route::delete('user', [UserController::class, 'destroy'])->name('user.destroy');

    // User Profile...
    Route::redirect('settings', '/settings/profile')->name('settings');
    Route::get('settings/profile', [UserProfileController::class, 'edit'])->name('user-profile.edit');
    Route::patch('settings/profile', [UserProfileController::class, 'update'])->name('user-profile.update');

    // User Password...
    Route::get('settings/password', [UserPasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [UserPasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');

    // Appearance...
    Route::get('settings/appearance', fn () => Inertia::render('appearance/update'))
        ->middleware('feature:appearance_settings')
        ->name('appearance.edit');

    // Personal data export (GDPR)...
    Route::get('settings/personal-data-export', fn () => Inertia::render('settings/personal-data-export'))
        ->middleware('feature:personal_data_export')
        ->name('personal-data-export.edit');
    Route::post('settings/personal-data-export', PersonalDataExportController::class)
        ->middleware(['feature:personal_data_export', 'throttle:3,1'])
        ->name('personal-data-export.store');

    // User Two-Factor Authentication...
    Route::get('settings/two-factor', [UserTwoFactorAuthenticationController::class, 'show'])
        ->middleware('feature:two_factor_auth')
        ->name('two-factor.show');

    // Gamification (Level & Achievements)...
    Route::get('settings/achievements', [AchievementsController::class, 'show'])
        ->middleware('feature:gamification')
        ->name('achievements.show');
});

Route::middleware('guest')->group(function (): void {
    // User...
    Route::get('register', [UserController::class, 'create'])
        ->middleware('registration.enabled')
        ->name('register');
    Route::post('register', [UserController::class, 'store'])
        ->middleware(['registration.enabled', ProtectAgainstSpam::class])
        ->name('register.store');

    // User Password...
    Route::get('reset-password/{token}', [UserPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('reset-password', [UserPasswordController::class, 'store'])
        ->name('password.store');

    // User Email Reset Notification...
    Route::get('forgot-password', [UserEmailResetNotificationController::class, 'create'])
        ->name('password.request');
    Route::post('forgot-password', [UserEmailResetNotificationController::class, 'store'])
        ->name('password.email');

    // Session...
    Route::get('login', [SessionController::class, 'create'])
        ->name('login');
    Route::post('login', [SessionController::class, 'store'])
        ->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    // User Email Verification...
    Route::get('verify-email', [UserEmailVerificationNotificationController::class, 'create'])
        ->name('verification.notice');
    Route::post('email/verification-notification', [UserEmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // User Email Verification...
    Route::get('verify-email/{id}/{hash}', [UserEmailVerificationController::class, 'update'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    // Session...
    Route::post('logout', [SessionController::class, 'destroy'])
        ->name('logout');
});
