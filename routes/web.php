<?php

declare(strict_types=1);

/*
 * All routes must have ->name() for RBAC (permission:sync-routes). Run:
 *   php artisan permission:sync-routes
 * after adding or changing routes.
 */

use App\Http\Controllers\Blog\BlogController;
use App\Http\Controllers\Changelog\ChangelogController;
use App\Http\Controllers\ContactSubmissionController;
use App\Http\Controllers\CookieConsentController;
use App\Http\Controllers\HelpCenter\HelpCenterController;
use App\Http\Controllers\HelpCenter\RateHelpArticleController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PersonalDataExportController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\Settings\AchievementsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserEmailResetNotificationController;
use App\Http\Controllers\UserEmailVerificationController;
use App\Http\Controllers\UserEmailVerificationNotificationController;
use App\Http\Controllers\UserPasswordController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\UserTwoFactorAuthenticationController;
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

Route::get('/', fn () => Inertia::render('welcome'))->name('home');

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

Route::get('contact', [ContactSubmissionController::class, 'create'])
    ->middleware('feature:contact')
    ->name('contact.create');
Route::post('contact', [ContactSubmissionController::class, 'store'])
    ->middleware(['feature:contact', ProtectAgainstSpam::class])
    ->name('contact.store');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('dashboard', fn () => Inertia::render('dashboard'))->name('dashboard');
    Route::get('profile/export-pdf', App\Http\Controllers\ProfileExportPdfController::class)
        ->middleware('feature:profile_pdf_export')
        ->name('profile.export-pdf');
});

Route::middleware(['auth', 'feature:onboarding'])->group(function (): void {
    Route::get('onboarding', [OnboardingController::class, 'show'])->name('onboarding');
    Route::post('onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');
});

Route::middleware('auth')->group(function (): void {
    Route::impersonate();
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
