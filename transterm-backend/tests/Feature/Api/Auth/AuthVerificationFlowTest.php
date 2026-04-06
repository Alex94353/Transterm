<?php

namespace Tests\Feature\Api\Auth;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthVerificationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;
    protected string $seeder = DatabaseSeeder::class;

    public function test_register_sends_verification_email(): void
    {
        Notification::fake();

        $this->postJson('/api/auth/register', [
            'username' => 'qa_user',
            'email' => 'qa_user@student.ukf.sk',
            'name' => 'QA',
            'surname' => 'User',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertCreated()->assertJsonPath('verification_email_sent', true);

        $user = User::where('email', 'qa_user@student.ukf.sk')->firstOrFail();
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_resend_verification_email_uses_normalized_email_and_sends_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'resend@student.ukf.sk',
            'username' => 'resend_user',
            'activated' => false,
            'email_verified_at' => null,
        ]);

        $this->postJson('/api/auth/email/verification-notification', [
            'email' => '  RESEND@student.ukf.sk  ',
        ])->assertOk();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_verify_email_activates_account_and_marks_verified(): void
    {
        $user = User::factory()->create([
            'email' => 'verify@student.ukf.sk',
            'username' => 'verify_user',
            'activated' => false,
            'email_verified_at' => null,
        ]);

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(30),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $this->getJson($url)
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertTrue((bool) $user->fresh()->activated);
    }

    public function test_verify_email_returns_invalid_for_wrong_hash(): void
    {
        $user = User::factory()->create([
            'email' => 'invalid@student.ukf.sk',
            'username' => 'invalid_user',
            'activated' => false,
            'email_verified_at' => null,
        ]);

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(30),
            [
                'id' => $user->id,
                'hash' => sha1('wrong@email.test'),
            ]
        );

        $this->getJson($url)
            ->assertStatus(422)
            ->assertJsonPath('status', 'invalid');
    }

    public function test_me_endpoint_blocks_inactive_and_banned_users(): void
    {
        $inactive = User::factory()->create([
            'email' => 'inactive@student.ukf.sk',
            'username' => 'inactive_user',
            'activated' => false,
            'banned' => false,
            'email_verified_at' => now(),
        ]);
        Sanctum::actingAs($inactive);

        $this->getJson('/api/auth/me')
            ->assertForbidden()
            ->assertJsonPath('message', 'Your account is not activated.');

        $banned = User::factory()->create([
            'email' => 'banned@student.ukf.sk',
            'username' => 'banned_user',
            'activated' => true,
            'banned' => true,
            'email_verified_at' => now(),
        ]);
        Sanctum::actingAs($banned);

        $this->getJson('/api/auth/me')
            ->assertForbidden()
            ->assertJsonPath('message', 'Your account is banned.');
    }
}
