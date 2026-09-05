<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class ReferralControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_assign_referrer_with_existing_cookie()
    {
        // Create a dummy referral code
        $referralCode = 'ABC123';

        // Set a dummy referral code cookie
        Cookie::queue(config('referral.cookie_name'), $referralCode);

        // Define the route for assigning a referrer
        Route::middleware('web')->get(config('referral.route_prefix').'/{referralCode}', [ReferralController::class, 'assignReferrer'])
            ->name('referralLink');

        // Call the route with the referral code
        $response = $this->get(route('referralLink', ['referralCode' => $referralCode]));

        // Assert that the response redirects to the configured route
        $response->assertRedirect(config('referral.redirect_route'));
    }

    public function test_assign_referrer_without_existing_cookie()
    {
        // Create a dummy referral code
        $referralCode = 'ABC123';

        // Define the route for assigning a referrer
        Route::middleware('web')->get(config('referral.route_prefix').'/{referralCode}', [ReferralController::class, 'assignReferrer'])
            ->name('referralLink');

        // Call the route with the referral code
        $response = $this->get(route('referralLink', ['referralCode' => $referralCode]));

        // Assert that the response redirects to the configured route
        $response->assertRedirect(config('referral.redirect_route'));

        // Assert that the referral code cookie has been set
        $this->assertTrue(Cookie::has(config('referral.cookie_name')));
    }

    public function test_create_referral_code_for_existing_users()
    {
        // Create some dummy users
        $users = factory(config('referral.user_model'), 5)->create();

        // Define the route for generating referral codes for existing users
        Route::middleware('web')->get('generate-ref-accounts', [ReferralController::class, 'createReferralCodeForExistingUsers'])
            ->name('generateReferralCodes');

        // Call the route to generate referral codes for existing users
        $response = $this->get(route('generateReferralCodes'));

        // Assert that the response is a JSON response
        $response->assertJson(['message' => 'Referral codes generated for existing users.']);

        // Assert that referral codes have been created for all existing users
        foreach ($users as $user) {
            $this->assertTrue($user->hasReferralAccount());
            $this->assertNotNull($user->getReferralCode());
        }
    }
}
