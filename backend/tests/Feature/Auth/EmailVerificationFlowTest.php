<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use HiEvents\Models\AccountConfiguration;
use HiEvents\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class EmailVerificationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        AccountConfiguration::firstOrCreate(['id' => 1], [
            'id' => 1,
            'name' => 'Default',
            'is_system_default' => true,
            'application_fees' => [
                'percentage' => 1.5,
                'fixed' => 0,
            ],
        ]);
    }

    public function test_unverified_user_can_verify_email_login_and_access_dashboard(): void
    {
        $password = fake()->password(16);
        $user = User::factory()->unverified()->password($password)->withAccount()->create();
        $account = $user->accounts()->first();

        $loginResponse = $this->postJson('/auth/login', [
            'email' => $user->email,
            'password' => $password,
        ]);
        $loginResponse->assertSuccessful();
        $token = $loginResponse->headers->get('X-Auth-Token');

        $meBeforeVerification = $this->getJson('/users/me', [
            'Authorization' => 'Bearer ' . $token,
        ]);
        $meBeforeVerification->assertSuccessful();
        $this->assertNull($meBeforeVerification->json('user.email_verified_at'));

        $verificationCode = '54321';
        Cache::put('email_verification_code:' . $user->email, $verificationCode, now()->addMinutes(30));

        $verifyResponse = $this->postJson(
            "/users/{$user->id}/confirm-email-with-code",
            ['code' => $verificationCode],
            ['Authorization' => 'Bearer ' . $token],
        );
        $verifyResponse->assertSuccessful();

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);

        $verifyAgainResponse = $this->postJson(
            "/users/{$user->id}/confirm-email-with-code",
            ['code' => $verificationCode],
            ['Authorization' => 'Bearer ' . $token],
        );
        $verifyAgainResponse->assertStatus(409);

        $meAfterVerification = $this->getJson('/users/me', [
            'Authorization' => 'Bearer ' . $token,
        ]);
        $meAfterVerification->assertSuccessful();
        $meAfterVerification->assertJsonStructure([
            'user',
            'accounts',
        ]);
        $this->assertNotNull($meAfterVerification->json('user.email_verified_at'));
        $this->assertSame($account->id, $meAfterVerification->json('accounts.0.id'));
    }
}
