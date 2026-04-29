<?php

declare(strict_types=1);

/*
 * All routes must have ->name() for RBAC (permission:sync-routes). Run:
 *   php artisan permission:sync-routes
 * after adding or changing routes.
 */

use App\Http\Controllers\AccountDeletionRequestController;
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
use App\Http\Controllers\CoalStockApproxDetailController;
use App\Http\Controllers\ContactSubmissionController;
use App\Http\Controllers\CookieConsentController;
use App\Http\Controllers\DailyVehicleEntryController;
use App\Http\Controllers\EnterpriseInquiryController;
use App\Http\Controllers\Exports\CoalTransportReportExportController;
use App\Http\Controllers\Exports\DispatchReportDprExportController;
use App\Http\Controllers\GenerateDispatchReportController;
use App\Http\Controllers\HelpCenter\HelpCenterController;
use App\Http\Controllers\HelpCenter\RateHelpArticleController;
use App\Http\Controllers\HistoricalMineController;
use App\Http\Controllers\HistoricalRakeController;
use App\Http\Controllers\Indents\IndentsController;
use App\Http\Controllers\InvitationAcceptController;
use App\Http\Controllers\LoaderOperatorsController;
use App\Http\Controllers\LoadersController;
use App\Http\Controllers\Notifications\NotificationReadController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\OpeningCoalStockController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\OrganizationInvitationController;
use App\Http\Controllers\OrganizationMemberController;
use App\Http\Controllers\OrganizationSwitchController;
use App\Http\Controllers\PenaltyTypesController;
use App\Http\Controllers\PersonalDataExportController;
use App\Http\Controllers\PowerPlantController;
use App\Http\Controllers\PowerplantSidingDistancesController;
use App\Http\Controllers\ProductionEntryController;
use App\Http\Controllers\RailwayReceipts\PenaltyController;
use App\Http\Controllers\RailwayReceipts\RrDocumentController;
use App\Http\Controllers\RailwaySidingEmptyWeighmentController;
use App\Http\Controllers\Rakes\PreRrController;
use App\Http\Controllers\Rakes\RakeDiverrtDestinationController;
use App\Http\Controllers\Rakes\RakeDiversionModeController;
use App\Http\Controllers\Rakes\RakeGuardInspectionController;
use App\Http\Controllers\Rakes\RakeLoadController;
use App\Http\Controllers\Rakes\RakeLoaderController;
use App\Http\Controllers\Rakes\RakePowerPlantReceiptController;
use App\Http\Controllers\Rakes\RakeRrHubStateController;
use App\Http\Controllers\Rakes\RakesController;
use App\Http\Controllers\Rakes\RakeTxrController;
use App\Http\Controllers\Rakes\RakeWagonController;
use App\Http\Controllers\Rakes\RakeWeighmentController;
use App\Http\Controllers\Reconciliation\PowerPlantReceiptController;
use App\Http\Controllers\Reconciliation\ReconciliationController;
use App\Http\Controllers\Reports\ReportsController;
use App\Http\Controllers\RoadDispatch\VehicleArrivalController;
use App\Http\Controllers\RoadDispatch\VehicleUnloadController;
use App\Http\Controllers\RR\RrUploadController;
use App\Http\Controllers\SectionTimersController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\Settings\AchievementsController;
use App\Http\Controllers\ShiftTimingsController;
use App\Http\Controllers\SidingPreIndentReportController;
use App\Http\Controllers\SidingsController;
use App\Http\Controllers\SidingSwitchController;
use App\Http\Controllers\StockLedgerController;
use App\Http\Controllers\TermsAcceptController;
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
use App\Http\Controllers\Weighments\WeighmentsController;
use App\Models\User;
use App\Services\Auth\HomeRedirectService;
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
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    $user = auth()->user();
    if (! $user instanceof User) {
        return redirect()->route('dashboard');
    }

    $homeRoute = resolve(HomeRedirectService::class)->getHomeRouteFor($user);

    return redirect()->route($homeRoute);
})->name('home');

// Invitation accept (public show, auth store)
Route::get('invitations/{token}', [InvitationAcceptController::class, 'show'])->name('invitations.show');
Route::post('invitations/{token}/accept', [InvitationAcceptController::class, 'store'])->name('invitations.accept')->middleware('auth');

Route::get('cookie-consent/accept', CookieConsentController::class)
    ->middleware('feature:cookie_consent')
    ->name('cookie-consent.accept');

Route::get('legal/terms', fn () => Inertia::render('legal/terms'))->name('legal.terms');
Route::get('legal/privacy', fn () => Inertia::render('legal/privacy'))->name('legal.privacy');
Route::get('account/request-deletion', [AccountDeletionRequestController::class, 'show'])->name('account.request-deletion.show');
Route::post('account/request-deletion', [AccountDeletionRequestController::class, 'store'])->name('account.request-deletion.store');

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
    Route::get('dashboard/executive-yesterday-data', [App\Http\Controllers\Dashboard\ExecutiveDashboardController::class, 'executiveYesterdayData'])
        ->name('dashboard.executive-yesterday-data');
    Route::get('dashboard/rake-performance/rakes', [App\Http\Controllers\Dashboard\ExecutiveDashboardController::class, 'rakePerformanceList'])
        ->name('dashboard.rake-performance.rakes.index');
    Route::get('dashboard/rake-performance/rakes/{rake}', [App\Http\Controllers\Dashboard\ExecutiveDashboardController::class, 'rakePerformanceDetail'])
        ->name('dashboard.rake-performance.rakes.show');
    Route::get('dashboard/loader-overload/loaders', [App\Http\Controllers\Dashboard\LoaderOverloadWebController::class, 'loaders'])
        ->name('dashboard.loader-overload.loaders.index');
    Route::get('dashboard/loader-overload/loaders/{loader}', [App\Http\Controllers\Dashboard\LoaderOverloadWebController::class, 'loaderShow'])
        ->name('dashboard.loader-overload.loaders.show');
    Route::get('dashboard/loader-overload/operators', [App\Http\Controllers\Dashboard\LoaderOverloadWebController::class, 'operators'])
        ->name('dashboard.loader-overload.operators.index');
    Route::get('dashboard/loader-overload/operators/show', [App\Http\Controllers\Dashboard\LoaderOverloadWebController::class, 'operatorShow'])
        ->name('dashboard.loader-overload.operators.show');

    Route::get('exports/coal-transport-report', CoalTransportReportExportController::class)
        ->name('exports.coal-transport-report');

    Route::post('notifications/read-all', [NotificationReadController::class, 'markAll'])->name('notifications.read-all');
    Route::post('notifications/{id}/read', [NotificationReadController::class, 'markOne'])->name('notifications.read-one');

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

    // Siding monitor
    Route::get('sidings/{siding}/monitor', [App\Http\Controllers\Sidings\SidingMonitorController::class, 'show'])
        ->name('sidings.monitor');

    // RRMCS Routes (Railway Rake Management Control System)
    Route::get('rake-loader', [RakeLoaderController::class, 'index'])->name('rake-loader.index');
    Route::get('rake-loader/rakes/{rake}/loading', [RakeLoaderController::class, 'loading'])->name('rake-loader.rakes.loading');
    Route::post('rake-loader/rakes/{rake}/override', [RakeLoaderController::class, 'storeOverride'])->name('rake-loader.rakes.override');

    Route::get('rakes', [RakesController::class, 'index'])->name('rakes.index');
    Route::get('rakes/{rake}', [RakesController::class, 'show'])->name('rakes.show');
    Route::get('rakes/{rake}/rr-hub-state', RakeRrHubStateController::class)->name('rakes.rr-hub-state');
    Route::get('rakes/{rake}/pre-rr', [PreRrController::class, 'show'])->name('rakes.pre-rr.show');
    Route::patch('rakes/{rake}/diversion-mode', RakeDiversionModeController::class)->name('rakes.diversion-mode.update');
    Route::post('rakes/{rake}/diverrt-destinations', [RakeDiverrtDestinationController::class, 'store'])->name('rakes.diverrt-destinations.store');
    Route::delete('rakes/{rake}/diverrt-destinations/{diverrtDestination}', [RakeDiverrtDestinationController::class, 'destroy'])->name('rakes.diverrt-destinations.destroy');
    Route::get('rakes/{rake}/edit', [RakesController::class, 'edit'])->name('rakes.edit');
    Route::put('rakes/{rake}', [RakesController::class, 'update'])->name('rakes.update');
    Route::delete('rakes/{rake}', [RakesController::class, 'destroy'])->name('rakes.destroy');
    Route::post('rakes/{rake}/generate-wagons', [RakesController::class, 'generateWagons'])->name('rakes.generate-wagons');
    Route::post('rakes/{rake}/loading/start', [RakesController::class, 'startLoadingTimer'])->name('rakes.loading.start');
    Route::post('rakes/{rake}/loading/reset', [RakesController::class, 'resetLoadingTimer'])->name('rakes.loading.reset');
    Route::post('rakes/{rake}/loading/stop', [RakesController::class, 'stopLoadingTimer'])->name('rakes.loading.stop');
    Route::put('rakes/{rake}/loading-times', [RakesController::class, 'updateLoadingTimes'])->name('rakes.loading-times.update');
    Route::put('rakes/{rake}/wagons/{wagon}', [RakeWagonController::class, 'update'])->name('rakes.wagons.update');
    Route::put('rakes/{rake}/wagons-bulk', [RakeWagonController::class, 'bulkUpdate'])->name('rakes.wagons.bulk-update');
    Route::put('rakes/{rake}/txr', [RakeTxrController::class, 'update'])->name('rakes.txr.update');
    Route::post('rakes/{rake}/txr/start', [RakeTxrController::class, 'start'])->name('rakes.txr.start');
    Route::post('rakes/{rake}/txr/end', [RakeTxrController::class, 'end'])->name('rakes.txr.end');
    Route::post('rakes/{rake}/txr/unfit-logs', [RakeTxrController::class, 'storeUnfitLogs'])->name('rakes.txr.unfit-logs');
    Route::post('rakes/{rake}/txr/upload-note', [RakeTxrController::class, 'uploadNote'])->name('rakes.txr.upload-note');
    // Unfit wagon routes (alternative: by txr id)
    Route::post('txr/{txr}/unfit-wagons', [WagonUnfitController::class, 'store'])->name('txr.unfit-wagons.store');
    Route::get('rakes/{rake}/load', [RakeLoadController::class, 'show'])->name('rakes.load.show');
    Route::post('rakes/{rake}/load/confirm-placement', [RakeLoadController::class, 'confirmPlacement'])->name('rakes.load.confirm-placement');
    Route::post('rakes/{rake}/load/wagon', [RakeLoadController::class, 'loadWagon'])->name('rakes.load.wagon');
    Route::post('rakes/{rake}/load/wagons', [RakeLoadController::class, 'storeWagonLoadings'])->name('rakes.load.wagons');
    Route::get('rakes/{rake}/load/wagon-loadings', [RakeLoadController::class, 'indexWagonLoadings'])->name('rakes.load.wagon-loadings');
    Route::post('rakes/{rake}/load/wagon-rows/ensure-all', [RakeLoadController::class, 'ensureAllWagonLoadingRows'])->name('rakes.load.wagon-rows.ensure-all');
    Route::post('rakes/{rake}/load/wagon-rows', [RakeLoadController::class, 'storeWagonRow'])->name('rakes.load.wagon-rows.store');
    Route::patch('rakes/{rake}/load/wagon-rows/{loading}', [RakeLoadController::class, 'updateWagonRow'])->name('rakes.load.wagon-rows.update');
    Route::delete('rakes/{rake}/load/wagon-rows/{loading}', [RakeLoadController::class, 'destroyWagonRow'])->name('rakes.load.wagon-rows.destroy');
    Route::post('rakes/{rake}/load/guard-inspection', [RakeLoadController::class, 'recordGuardInspection'])->name('rakes.load.guard-inspection');
    Route::post('rakes/{rake}/load/confirm-dispatch', [RakeLoadController::class, 'confirmDispatch'])->name('rakes.load.confirm-dispatch');
    Route::post('rakes/{rake}/weighments', [RakeWeighmentController::class, 'store'])->name('rakes.weighments.store');
    Route::post('rakes/{rake}/weighments/manual', [RakeWeighmentController::class, 'storeManual'])->name('rakes.weighments.manual');
    Route::patch('rakes/{rake}/weighments/{rakeWeighment}', [RakeWeighmentController::class, 'updateManual'])->name('rakes.weighments.update-manual');
    Route::delete('rakes/{rake}/weighments', [RakeWeighmentController::class, 'destroy'])->name('rakes.weighments.destroy');
    Route::post('rakes/{rake}/power-plant-receipts', [RakePowerPlantReceiptController::class, 'store'])->name('rakes.power-plant-receipts.store');
    Route::delete('rakes/{rake}/power-plant-receipts/{receipt}', [RakePowerPlantReceiptController::class, 'destroy'])->name('rakes.power-plant-receipts.destroy')->scopeBindings();
    Route::get('rakes/{rake}/comparison', [RakesController::class, 'comparison'])->name('rakes.comparison');
    Route::post('rakes/{rake}/guard-inspection', [RakeGuardInspectionController::class, 'store'])->name('rakes.guard-inspection.store');
    Route::get('indents', [IndentsController::class, 'index'])->name('indents.index');
    Route::post('indents/import', [IndentsController::class, 'importPreview'])->name('indents.import');
    Route::get('indents/create', [IndentsController::class, 'create'])->name('indents.create');
    Route::post('indents', [IndentsController::class, 'store'])->name('indents.store');
    Route::get('indents/{indent}/pdf', [IndentsController::class, 'downloadPdf'])->name('indents.pdf');
    Route::get('indents/{indent}', [IndentsController::class, 'show'])->name('indents.show');
    Route::get('indents/{indent}/edit', [IndentsController::class, 'edit'])->name('indents.edit');
    Route::put('indents/{indent}', [IndentsController::class, 'update'])->name('indents.update');
    Route::patch('indents/{indent}/assign-rake-number', [IndentsController::class, 'assignRakeNumber'])->name('indents.assign-rake-number');
    Route::delete('indents/{indent}', [IndentsController::class, 'destroy'])->name('indents.destroy');
    Route::get('indents/{indent}/create-rake', [IndentsController::class, 'createRake'])->name('indents.create-rake');
    Route::post('indents/{indent}/store-rake', [IndentsController::class, 'storeRakeFromIndent'])->name('indents.store-rake');

    // Master Data
    Route::prefix('master-data')->name('master-data.')->group(function (): void {
        Route::resource('power-plants', PowerPlantController::class);
        Route::resource('sidings', SidingsController::class);
        Route::resource('loaders', LoadersController::class);
        Route::post('loader-operators', [LoaderOperatorsController::class, 'store'])->name('loader-operators.store');
        Route::put('loader-operators/{loaderOperator}', [LoaderOperatorsController::class, 'update'])->name('loader-operators.update');
        Route::resource('penalty-types', PenaltyTypesController::class);
        Route::resource('section-timers', SectionTimersController::class);
        Route::get('shift-timings', [ShiftTimingsController::class, 'index'])->name('shift-timings.index');
        Route::get('shift-timings/{siding}/edit', [ShiftTimingsController::class, 'edit'])->name('shift-timings.edit');
        Route::put('shift-timings/{siding}', [ShiftTimingsController::class, 'update'])->name('shift-timings.update');
        Route::get('opening-coal-stock', [OpeningCoalStockController::class, 'index'])->name('opening-coal-stock.index');
        Route::get('opening-coal-stock/{siding}/edit', [OpeningCoalStockController::class, 'edit'])->name('opening-coal-stock.edit');
        Route::put('opening-coal-stock/{siding}', [OpeningCoalStockController::class, 'update'])->name('opening-coal-stock.update');
        Route::post('opening-coal-stock/{siding}/fix', [OpeningCoalStockController::class, 'fixWrongOpening'])->name('opening-coal-stock.fix');
        Route::get('stock-ledger', [StockLedgerController::class, 'index'])->name('stock-ledger.index');
        Route::get('stock-ledger/stock-report', [StockLedgerController::class, 'stockReport'])->name('stock-ledger.stock-report');
        Route::post('stock-ledger/adjust', [StockLedgerController::class, 'adjust'])->name('stock-ledger.adjust');
        Route::get('daily-stock-details', [CoalStockApproxDetailController::class, 'index'])->name('daily-stock-details.index');
        Route::get('daily-stock-details/export', [CoalStockApproxDetailController::class, 'export'])->name('daily-stock-details.export');
        Route::get('daily-stock-details/create', [CoalStockApproxDetailController::class, 'create'])->name('daily-stock-details.create');
        Route::post('daily-stock-details', [CoalStockApproxDetailController::class, 'store'])->name('daily-stock-details.store');
        Route::get('daily-stock-details/{coalStockApproxDetail}/edit', [CoalStockApproxDetailController::class, 'edit'])->name('daily-stock-details.edit');
        Route::put('daily-stock-details/{coalStockApproxDetail}', [CoalStockApproxDetailController::class, 'update'])->name('daily-stock-details.update');
        Route::delete('daily-stock-details/{coalStockApproxDetail}', [CoalStockApproxDetailController::class, 'destroy'])->name('daily-stock-details.destroy');
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
    Route::get('road-dispatch/daily-vehicle-entries/hourly-summary', [DailyVehicleEntryController::class, 'hourlySummary'])->name('road-dispatch.daily-vehicle-entries.hourly-summary');
    Route::get('road-dispatch/daily-vehicle-entries/shift-report', [DailyVehicleEntryController::class, 'shiftReport'])->name('road-dispatch.daily-vehicle-entries.shift-report');
    Route::get('road-dispatch/daily-vehicle-entries/hourly-summary/export', [DailyVehicleEntryController::class, 'exportHourlySummary'])->name('road-dispatch.daily-vehicle-entries.hourly-summary.export');
    Route::get('road-dispatch/daily-vehicle-entries/export', [DailyVehicleEntryController::class, 'export'])->name('road-dispatch.daily-vehicle-entries.export');
    Route::get('road-dispatch/vehicle-workorders/lookup', [DailyVehicleEntryController::class, 'lookupVehicle'])->name('road-dispatch.vehicle-workorders.lookup');

    // Railway Siding Empty Weighment
    Route::get('railway-siding-empty-weighment', [RailwaySidingEmptyWeighmentController::class, 'index'])->name('railway-siding-empty-weighment.index');
    Route::post('railway-siding-empty-weighment', [RailwaySidingEmptyWeighmentController::class, 'store'])->name('railway-siding-empty-weighment.store');
    Route::get('railway-siding-empty-weighment/hourly-summary', [RailwaySidingEmptyWeighmentController::class, 'hourlySummary'])->name('railway-siding-empty-weighment.hourly-summary');
    Route::patch('railway-siding-empty-weighment/{entry}', [RailwaySidingEmptyWeighmentController::class, 'update'])->name('railway-siding-empty-weighment.update');
    Route::delete('railway-siding-empty-weighment/{entry}', [RailwaySidingEmptyWeighmentController::class, 'destroy'])->name('railway-siding-empty-weighment.destroy');
    Route::post('railway-siding-empty-weighment/{entry}/complete', [RailwaySidingEmptyWeighmentController::class, 'markCompleted'])->name('railway-siding-empty-weighment.complete');
    Route::get('railway-siding-empty-weighment/export', [RailwaySidingEmptyWeighmentController::class, 'export'])->name('railway-siding-empty-weighment.export');

    // Historical Railway Siding (historical rake data)
    Route::get('historical/railway-siding', [HistoricalRakeController::class, 'index'])->name('historical.railway-siding.index');
    Route::get('historical/railway-siding/export', [HistoricalRakeController::class, 'export'])->name('historical.railway-siding.export');
    Route::post('historical/railway-siding', [HistoricalRakeController::class, 'store'])->name('historical.railway-siding.store');
    Route::patch('historical/railway-siding/{rake}', [HistoricalRakeController::class, 'update'])->name('historical.railway-siding.update');
    Route::delete('historical/railway-siding/{rake}', [HistoricalRakeController::class, 'destroy'])->name('historical.railway-siding.destroy');

    // Historical Mines (monthly mines data)
    Route::get('historical/mines', [HistoricalMineController::class, 'index'])->name('historical.mines.index');
    Route::post('historical/mines', [HistoricalMineController::class, 'store'])->name('historical.mines.store');
    Route::patch('historical/mines/{mine}', [HistoricalMineController::class, 'update'])->name('historical.mines.update');
    Route::delete('historical/mines/{mine}', [HistoricalMineController::class, 'destroy'])->name('historical.mines.destroy');

    // Production (Coal / OB)
    Route::prefix('production/coal')->name('production.coal.')->group(function (): void {
        Route::get('/', [ProductionEntryController::class, 'index'])->name('index');
        Route::post('/', [ProductionEntryController::class, 'store'])->name('store');
        Route::get('{production_entry}/edit', [ProductionEntryController::class, 'edit'])->name('edit');
        Route::patch('{production_entry}', [ProductionEntryController::class, 'update'])->name('update');
        Route::delete('{production_entry}', [ProductionEntryController::class, 'destroy'])->name('destroy');
    });
    Route::prefix('production/ob')->name('production.ob.')->group(function (): void {
        Route::get('/', [ProductionEntryController::class, 'index'])->name('index');
        Route::post('/', [ProductionEntryController::class, 'store'])->name('store');
        Route::get('{production_entry}/edit', [ProductionEntryController::class, 'edit'])->name('edit');
        Route::patch('{production_entry}', [ProductionEntryController::class, 'update'])->name('update');
        Route::delete('{production_entry}', [ProductionEntryController::class, 'destroy'])->name('destroy');
    });

    Route::resource('siding-pre-indent-reports', SidingPreIndentReportController::class);

    // Vehicle Dispatch Register
    Route::get('vehicle-dispatch', [VehicleDispatchController::class, 'index'])->name('vehicle-dispatch.index');
    Route::get('vehicle-dispatch/dpr-data', [VehicleDispatchController::class, 'dprData'])->name('vehicle-dispatch.dpr-data');
    Route::get('vehicle-dispatch/calendar-days', [VehicleDispatchController::class, 'calendarDays'])->name('vehicle-dispatch.calendar-days');
    Route::put('vehicle-dispatch/{vehicle_dispatch}', [VehicleDispatchController::class, 'update'])->name('vehicle-dispatch.update');
    Route::post('vehicle-dispatch/import', [VehicleDispatchController::class, 'import'])->name('vehicle-dispatch.import');
    Route::post('vehicle-dispatch/save', [VehicleDispatchController::class, 'saveImport'])->name('vehicle-dispatch.save');
    Route::post('dispatch-reports/generate', [GenerateDispatchReportController::class, 'generate'])->name('dispatch-reports.generate');
    Route::get('vehicle-dispatch/dpr-export', DispatchReportDprExportController::class)->name('vehicle-dispatch.dpr-export');

    // Vehicle Work Orders
    Route::get('vehicle-workorders', [VehicleWorkorderController::class, 'index'])->name('vehicle-workorders.index');
    Route::get('vehicle-workorders/export', [VehicleWorkorderController::class, 'export'])->name('vehicle-workorders.export');
    Route::get('vehicle-workorders/export-transporters', [VehicleWorkorderController::class, 'exportTransporters'])->name('vehicle-workorders.export-transporters');
    Route::get('vehicle-workorders/create', [VehicleWorkorderController::class, 'create'])->name('vehicle-workorders.create');
    Route::get('vehicle-workorders/{vehicle_workorder}/edit', [VehicleWorkorderController::class, 'edit'])->name('vehicle-workorders.edit');
    Route::post('vehicle-workorders', [VehicleWorkorderController::class, 'store'])->name('vehicle-workorders.store');
    Route::put('vehicle-workorders/{vehicle_workorder}', [VehicleWorkorderController::class, 'update'])->name('vehicle-workorders.update');

    // Railway Receipts (RR) and Penalties
    Route::get('railway-receipts', [RrDocumentController::class, 'index'])->name('railway-receipts.index');
    Route::post('railway-receipts/import', [RrUploadController::class, 'store'])->name('railway-receipts.import');
    Route::post('railway-receipts/upload', [RrDocumentController::class, 'upload'])->name('railway-receipts.upload');
    Route::get('railway-receipts/create', [RrDocumentController::class, 'create'])->name('railway-receipts.create');
    Route::get('railway-receipts/rakes', [RrDocumentController::class, 'rakesForMonth'])->name('railway-receipts.rakes');
    Route::post('railway-receipts', [RrDocumentController::class, 'store'])->name('railway-receipts.store');
    Route::get('railway-receipts/{rrDocument}', [RrDocumentController::class, 'show'])->name('railway-receipts.show');
    Route::get('railway-receipts/{rrDocument}/pdf', [RrDocumentController::class, 'downloadPdf'])->name('railway-receipts.pdf');
    Route::put('railway-receipts/{rrDocument}', [RrDocumentController::class, 'update'])->name('railway-receipts.update');
    Route::delete('railway-receipts/{rrDocument}', [RrDocumentController::class, 'destroy'])->name('railway-receipts.destroy');
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

    // Weighments (historical rake weighment imports)
    Route::get('weighments', [WeighmentsController::class, 'index'])->name('weighments.index');
    Route::get('weighments/{weighment}', [WeighmentsController::class, 'show'])->whereNumber('weighment')->name('weighments.show');
    Route::get('weighments/{weighment}/download', [WeighmentsController::class, 'download'])->whereNumber('weighment')->name('weighments.download');
    Route::delete('weighments/{weighment}', [WeighmentsController::class, 'destroy'])->whereNumber('weighment')->name('weighments.destroy');
    Route::post('weighments/import', [WeighmentsController::class, 'store'])->name('weighments.import');
    Route::post('weighments/manual', [WeighmentsController::class, 'storeManual'])->name('weighments.manual');
    Route::get('weighments/template-xlsx', [WeighmentsController::class, 'downloadTemplateXlsx'])->name('weighments.template-xlsx');

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

Route::middleware(['auth', 'feature:onboarding', 'redirect.settings'])->group(function (): void {
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

    // User Password (redirect to dashboard for all users)...
    Route::get('settings/password', [UserPasswordController::class, 'edit'])
        ->middleware('redirect.settings')
        ->name('password.edit');
    Route::put('settings/password', [UserPasswordController::class, 'update'])
        ->middleware(['throttle:6,1', 'redirect.settings'])
        ->name('password.update');

    // Appearance (redirect to dashboard for all users)...
    Route::get('settings/appearance', fn () => Inertia::render('appearance/update'))
        ->middleware(['feature:appearance_settings', 'redirect.settings'])
        ->name('appearance.edit');

    // Personal data export (redirect to dashboard for all users)...
    Route::get('settings/personal-data-export', fn () => Inertia::render('settings/personal-data-export'))
        ->middleware(['feature:personal_data_export', 'redirect.settings'])
        ->name('personal-data-export.edit');
    Route::post('settings/personal-data-export', PersonalDataExportController::class)
        ->middleware(['feature:personal_data_export', 'throttle:3,1', 'redirect.settings'])
        ->name('personal-data-export.store');

    // User Two-Factor Authentication (redirect to dashboard for all users)...
    Route::get('settings/two-factor', [UserTwoFactorAuthenticationController::class, 'show'])
        ->middleware(['feature:two_factor_auth', 'redirect.settings'])
        ->name('two-factor.show');

    // Gamification (redirect to dashboard for all users)...
    Route::get('settings/achievements', [AchievementsController::class, 'show'])
        ->middleware(['feature:gamification', 'redirect.settings'])
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
