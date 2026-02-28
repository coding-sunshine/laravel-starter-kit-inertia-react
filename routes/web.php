<?php

declare(strict_types=1);

/*
 * All routes must have ->name() for RBAC (permission:sync-routes). Run:
 *   php artisan permission:sync-routes
 * after adding or changing routes.
 */

use App\Http\Controllers\Billing\BillingDashboardController;
use App\Http\Controllers\Billing\CreditController;
use App\Http\Controllers\Billing\InvoiceController;
use App\Http\Controllers\Billing\PaddleWebhookController;
use App\Http\Controllers\Billing\PricingController;
use App\Http\Controllers\Billing\StripeWebhookController;
use App\Http\Controllers\Blog\BlogController;
use App\Http\Controllers\Changelog\ChangelogController;
use App\Http\Controllers\ContactSubmissionController;
use App\Http\Controllers\CookieConsentController;
use App\Http\Controllers\EnterpriseInquiryController;
use App\Http\Controllers\HelpCenter\HelpCenterController;
use App\Http\Controllers\HelpCenter\RateHelpArticleController;
use App\Http\Controllers\InvitationAcceptController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\OrganizationInvitationController;
use App\Http\Controllers\OrganizationMemberController;
use App\Http\Controllers\OrganizationSwitchController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PageViewController;
use App\Http\Controllers\PersonalDataExportController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\Settings\AchievementsController;
use App\Http\Controllers\Settings\BrandingController;
use App\Http\Controllers\TermsAcceptController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserEmailResetNotificationController;
use App\Http\Controllers\UserEmailVerificationController;
use App\Http\Controllers\UserEmailVerificationNotificationController;
use App\Http\Controllers\UserPasswordController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\UserTwoFactorAuthenticationController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
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

Route::get('robots.txt', function (): Response {
    $base = mb_rtrim(config('app.url'), '/');

    return response("User-agent: *\nDisallow:\n\nSitemap: {$base}/sitemap.xml\n", 200, [
        'Content-Type' => 'text/plain',
    ]);
})->name('robots');

Route::get('up', function (): JsonResponse {
    $checks = ['app' => true];
    try {
        DB::connection()->getPdo();
        $checks['database'] = true;
    } catch (Throwable) {
        $checks['database'] = false;
    }
    $ok = ! in_array(false, $checks, true);

    return response()->json(['status' => $ok ? 'ok' : 'degraded', 'checks' => $checks], $ok ? 200 : 503);
})->name('up');

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

    Route::get('dashboard', fn () => Inertia::render('dashboard'))->name('dashboard');

    Route::get('chat', fn () => Inertia::render('chat/index'))->name('chat');

    Route::get('users', function (Request $request) {
        $user = $request->user();
        $canView = $user?->can('bypass-permissions')
            || (config('tenancy.enabled', true)
                && $user?->canInOrganization('org.members.view'));

        abort_unless($canView, 403);

        return Inertia::render('users/table', [
            'tableData' => App\DataTables\UserDataTable::makeTable($request),
            'searchableColumns' => App\DataTables\UserDataTable::tableSearchableColumns(),
        ]);
    })->name('users.table');

    Route::get('users/{user}', function (App\Models\User $user, Request $request) {
        $currentUser = $request->user();
        $canView = $currentUser?->can('bypass-permissions')
            || (config('tenancy.enabled', true)
                && $currentUser?->canInOrganization('org.members.view'));

        abort_unless($canView, 403);

        $organization = App\Services\TenantContext::get();
        if ($organization && ! $currentUser?->can('bypass-permissions')) {
            abort_unless(
                $user->organizations()->where('organizations.id', $organization->id)->exists(),
                404,
            );
        }

        return Inertia::render('users/show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at?->toIso8601String(),
            ],
        ]);
    })->name('users.show');

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

    Route::middleware('tenant')->group(function (): void {
        Route::get('billing', [BillingDashboardController::class, 'index'])->name('billing.index');
        Route::get('billing/credits', [CreditController::class, 'index'])->name('billing.credits.index');
        Route::post('billing/credits/purchase', [CreditController::class, 'purchase'])->name('billing.credits.purchase');
        Route::post('billing/credits/checkout/lemon-squeezy', [CreditController::class, 'checkoutLemonSqueezy'])->name('billing.credits.checkout.lemon-squeezy');
        Route::get('billing/invoices', [InvoiceController::class, 'index'])->name('billing.invoices.index');
        Route::get('billing/invoices/{invoice}', [InvoiceController::class, 'download'])->name('billing.invoices.download');
    });

    Route::middleware(['tenant', 'permission:org.settings.manage'])->group(function (): void {
        Route::get('settings/branding', [BrandingController::class, 'edit'])->name('settings.branding.edit');
        Route::put('settings/branding', [BrandingController::class, 'update'])->name('settings.branding.update');
    });

    Route::middleware('tenant')->prefix('fleet')->name('fleet.')->group(function (): void {
        Route::get('/', [App\Http\Controllers\Fleet\FleetDashboardController::class, 'index'])->name('dashboard');
        Route::resource('locations', App\Http\Controllers\Fleet\LocationController::class)->names('locations');
        Route::resource('cost-centers', App\Http\Controllers\Fleet\CostCenterController::class)->names('cost-centers');
        Route::resource('drivers', App\Http\Controllers\Fleet\DriverController::class)->names('drivers');
        Route::resource('trailers', App\Http\Controllers\Fleet\TrailerController::class)->names('trailers');
        Route::post('vehicles/{vehicle}/assign-driver', [App\Http\Controllers\Fleet\VehicleController::class, 'assignDriver'])->name('vehicles.assign-driver');
        Route::post('vehicles/{vehicle}/unassign-driver', [App\Http\Controllers\Fleet\VehicleController::class, 'unassignDriver'])->name('vehicles.unassign-driver');
        Route::resource('vehicles', App\Http\Controllers\Fleet\VehicleController::class)->names('vehicles');
        Route::resource('geofences', App\Http\Controllers\Fleet\GeofenceController::class)->names('geofences');
        Route::resource('garages', App\Http\Controllers\Fleet\GarageController::class)->names('garages');
        Route::resource('fuel-stations', App\Http\Controllers\Fleet\FuelStationController::class)->names('fuel-stations');
        Route::resource('ev-charging-stations', App\Http\Controllers\Fleet\EvChargingStationController::class)->names('ev-charging-stations');
        Route::resource('operator-licences', App\Http\Controllers\Fleet\OperatorLicenceController::class)->names('operator-licences');
        Route::resource('driver-vehicle-assignments', App\Http\Controllers\Fleet\DriverVehicleAssignmentController::class)->only(['index', 'store', 'update', 'destroy'])->names('driver-vehicle-assignments');
        Route::resource('routes', App\Http\Controllers\Fleet\RouteController::class)->names('routes');
        Route::resource('routes.route-stops', App\Http\Controllers\Fleet\RouteStopController::class)->only(['store', 'update', 'destroy'])->names('routes.stops')->scoped();
        Route::resource('trips', App\Http\Controllers\Fleet\TripController::class)->only(['index', 'show'])->names('trips');
        Route::resource('behavior-events', App\Http\Controllers\Fleet\BehaviorEventController::class)->only(['index', 'show'])->names('behavior-events');
        Route::resource('telematics-devices', App\Http\Controllers\Fleet\TelematicsDeviceController::class)->names('telematics-devices');
        Route::resource('geofence-events', App\Http\Controllers\Fleet\GeofenceEventController::class)->only(['index'])->names('geofence-events');
        Route::resource('fuel-cards', App\Http\Controllers\Fleet\FuelCardController::class)->names('fuel-cards');
        Route::resource('fuel-transactions', App\Http\Controllers\Fleet\FuelTransactionController::class)->names('fuel-transactions');
        Route::resource('service-schedules', App\Http\Controllers\Fleet\ServiceScheduleController::class)->names('service-schedules');
        Route::resource('work-orders', App\Http\Controllers\Fleet\WorkOrderController::class)->names('work-orders');
        Route::resource('defects', App\Http\Controllers\Fleet\DefectController::class)->names('defects');
        Route::resource('compliance-items', App\Http\Controllers\Fleet\ComplianceItemController::class)->names('compliance-items');
        Route::resource('driver-working-time', App\Http\Controllers\Fleet\DriverWorkingTimeController::class)->names('driver-working-time');
        Route::resource('tachograph-downloads', App\Http\Controllers\Fleet\TachographDownloadController::class)->names('tachograph-downloads');
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
});

Route::post('webhooks/stripe', StripeWebhookController::class)->name('webhooks.stripe')->withoutMiddleware([ValidateCsrfToken::class]);
Route::post('webhooks/paddle', PaddleWebhookController::class)->name('webhooks.paddle')->withoutMiddleware([ValidateCsrfToken::class]);

Route::middleware(['auth', 'feature:onboarding'])->group(function (): void {
    Route::get('onboarding', [OnboardingController::class, 'show'])->name('onboarding');
    Route::post('onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');
});

Route::middleware('auth')->group(function (): void {
    Route::personalDataExports('personal-data-exports');

    Route::delete('user', [UserController::class, 'destroy'])->name('user.destroy');

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
});

Route::middleware('guest')->group(function (): void {
    Route::get('register', [UserController::class, 'create'])
        ->middleware('registration.enabled')
        ->name('register');
    Route::post('register', [UserController::class, 'store'])
        ->middleware(['registration.enabled', ProtectAgainstSpam::class])
        ->name('register.store');

    Route::get('reset-password/{token}', [UserPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('reset-password', [UserPasswordController::class, 'store'])
        ->name('password.store');

    Route::get('forgot-password', [UserEmailResetNotificationController::class, 'create'])
        ->name('password.request');
    Route::post('forgot-password', [UserEmailResetNotificationController::class, 'store'])
        ->name('password.email');

    Route::get('login', [SessionController::class, 'create'])
        ->name('login');
    Route::post('login', [SessionController::class, 'store'])
        ->name('login.store');
});

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
