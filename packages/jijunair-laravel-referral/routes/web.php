<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Jijunair\LaravelReferral\Controllers\ReferralController;

Route::middleware('web')->group(function () {
    Route::get(config('referral.route_prefix').'/{referralCode}', [ReferralController::class, 'assignReferrer'])
        ->name('referralLink');

    Route::get('generate-ref-accounts', [ReferralController::class, 'createReferralCodeForExistingUsers'])
        ->name('generateReferralCodes');
});
