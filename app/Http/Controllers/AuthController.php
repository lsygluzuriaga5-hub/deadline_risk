<?php

namespace App\Http\Controllers;

use App\Mail\LoginOtpMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();
        $storedPassword = $user?->getAuthPassword();
        $authenticated = false;

        if ($user && $storedPassword !== null && password_get_info($storedPassword)['algoName'] === 'unknown') {
            if (hash_equals($storedPassword, $credentials['password'])) {
                $user->password = Hash::make($credentials['password']);
                $user->save();
                $authenticated = true;
            }
        } else {
            $authenticated = $user !== null && Hash::check($credentials['password'], $storedPassword);
        }

        if (! $authenticated) {
            return back()
                ->withErrors(['email' => 'These credentials do not match our records.'])
                ->onlyInput('email');
        }

        return $this->sendLoginOtp($request, $user, $request->boolean('remember'));
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        return $this->sendLoginOtp($request, $user, $request->boolean('remember'));
    }

    public function showOtp(Request $request): View|RedirectResponse
    {
        $user = $this->pendingOtpUser($request);

        if (! $user) {
            return redirect()->route('home')->withErrors(['email' => 'Request a new verification code by signing in again.']);
        }

        return view('auth.otp', ['email' => $user->email]);
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);
        $challenge = $request->session()->get('auth_otp');
        $user = $this->pendingOtpUser($request);

        if (! $user || ! is_array($challenge)) {
            $request->session()->forget('auth_otp');

            return redirect()->route('home')->withErrors(['email' => 'Request a new verification code by signing in again.']);
        }

        if ($challenge['expires_at'] <= now()->timestamp) {
            $request->session()->forget('auth_otp');

            return back()->withErrors(['code' => 'That code has expired. Request a new one.']);
        }

        if (! Hash::check($validated['code'], $challenge['code_hash'])) {
            $challenge['attempts']++;

            if ($challenge['attempts'] >= 5) {
                $request->session()->forget('auth_otp');

                return redirect()->route('home')->withErrors(['email' => 'Too many incorrect codes. Sign in again to request a new code.']);
            }

            $request->session()->put('auth_otp', $challenge);

            return back()->withErrors(['code' => 'That verification code is incorrect.']);
        }

        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        Auth::login($user, $challenge['remember']);
        $request->session()->forget('auth_otp');
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function resendOtp(Request $request): RedirectResponse
    {
        $user = $this->pendingOtpUser($request);

        if (! $user) {
            $request->session()->forget('auth_otp');

            return redirect()->route('home')->withErrors(['email' => 'Sign in again to request a verification code.']);
        }

        $remember = (bool) $request->session()->get('auth_otp.remember', false);

        return $this->sendLoginOtp($request, $user, $remember)
            ->with('status', 'A new verification code is being sent to '.$user->email.'.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'You have been signed out.');
    }

    private function sendLoginOtp(Request $request, User $user, bool $remember): RedirectResponse
    {
        $code = (string) random_int(100000, 999999);

        $request->session()->put('auth_otp', [
            'user_id' => $user->id,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10)->timestamp,
            'attempts' => 0,
            'remember' => $remember,
        ]);

        Mail::to($user->email)->queue(new LoginOtpMail($code));

        return redirect()->route('login.otp')->with('status', 'A verification code is being sent to '.$user->email.'.');
    }

    private function pendingOtpUser(Request $request): ?User
    {
        $userId = $request->session()->get('auth_otp.user_id');

        return is_numeric($userId) ? User::find($userId) : null;
    }
}
