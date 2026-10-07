<?php

namespace Tests\Feature;

use App\Mail\LoginOtpMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_login(): void
    {
        Mail::fake();

        $this->post('/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertRedirect(route('login.otp'));

        $this->assertGuest();

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
        ]);

        $user = User::where('email', 'jane@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('secret123', $user->password));
        $code = null;
        Mail::assertSent(LoginOtpMail::class, function (LoginOtpMail $mail) use (&$code, $user): bool {
            $code = $mail->code;

            return $mail->hasTo($user->email);
        });

        $this->post(route('login.otp.verify'), ['code' => $code])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_login_form_uses_forwarded_https_scheme(): void
    {
        $this->withServerVariables([
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_HOST' => 'deadline.example.test',
            'HTTP_X_FORWARDED_PORT' => '443',
        ])->get('/')
            ->assertSee('action="https://deadline.example.test/login"', false);
    }

    public function test_valid_password_sends_otp_without_authenticating_user(): void
    {
        Mail::fake();
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('login.otp'));

        $this->get(route('login.otp'))->assertSee('Check your email');

        $this->assertGuest();
        Mail::assertSent(LoginOtpMail::class, fn (LoginOtpMail $mail): bool => $mail->hasTo($user->email));
    }

    public function test_login_redirects_to_dashboard_when_canvas_is_not_connected(): void
    {
        Mail::fake();
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('login.otp'));

        $code = null;
        Mail::assertQueued(LoginOtpMail::class, function (LoginOtpMail $mail) use (&$code): bool {
            $code = $mail->code;

            return true;
        });

        $this->post(route('login.otp.verify'), ['code' => $code])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_legacy_plaintext_password_is_hashed_after_successful_login(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        DB::table('users')->where('id', $user->id)->update(['password' => 'password']);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('login.otp'));

        $this->assertGuest();
        $this->assertTrue(Hash::check('password', $user->fresh()->password));

        $code = null;
        Mail::assertSent(LoginOtpMail::class, function (LoginOtpMail $mail) use (&$code): bool {
            $code = $mail->code;

            return true;
        });

        $this->post(route('login.otp.verify'), ['code' => $code])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_for_legacy_plaintext_account_is_rejected(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        DB::table('users')->where('id', $user->id)->update(['password' => 'password']);

        $this->from('/')->post('/login', [
            'email' => $user->email,
            'password' => 'incorrect',
        ])->assertRedirect('/')
            ->assertSessionHasErrors(['email' => 'These credentials do not match our records.']);

        $this->assertGuest();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'password' => 'password',
        ]);

        Mail::assertNotSent(LoginOtpMail::class);
    }

    public function test_incorrect_otp_does_not_authenticate_user(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('login.otp'));

        $this->from(route('login.otp'))
            ->post(route('login.otp.verify'), ['code' => '000000'])
            ->assertRedirect(route('login.otp'))
            ->assertSessionHasErrors(['code' => 'That verification code is incorrect.']);

        $this->assertGuest();
    }

    public function test_expired_otp_is_rejected(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('login.otp'));

        $this->travel(11)->minutes();

        $this->from(route('login.otp'))
            ->post(route('login.otp.verify'), ['code' => '123456'])
            ->assertRedirect(route('login.otp'))
            ->assertSessionHasErrors(['code' => 'That code has expired. Request a new one.']);

        $this->assertGuest();
    }

    public function test_five_incorrect_otp_attempts_revoke_the_challenge(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('login.otp'));

        foreach (range(1, 4) as $attempt) {
            $this->from(route('login.otp'))
                ->post(route('login.otp.verify'), ['code' => '000000']);
        }

        $this->from(route('login.otp'))
            ->post(route('login.otp.verify'), ['code' => '000000'])
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors(['email' => 'Too many incorrect codes. Sign in again to request a new code.']);
        $this->assertGuest();
    }
}
