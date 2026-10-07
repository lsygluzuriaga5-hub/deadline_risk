/* @extends('layouts.app')

@section('content')
<section class="auth-card" aria-labelledby="otp-title">
    <p class="auth-eyebrow">Email verification</p>
    <h1 id="otp-title">Check your email</h1>
    <p class="auth-intro">Enter the six-digit code sent to <strong>{{ $email }}</strong>. It expires in 10 minutes.</p>

    @if (session('status'))<div class="auth-alert" role="status">{{ session('status') }}</div>@endif
    @if ($errors->has('code'))<p class="auth-error" role="alert">{{ $errors->first('code') }}</p>@endif

    <form class="auth-form" method="POST" action="{{ route('login.otp.verify') }}">
        @csrf
        <div class="auth-field">
            <label for="code">Verification code</label>
            <input class="auth-input" id="code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" minlength="6" placeholder="000000" required autofocus>
        </div>
        <button class="auth-submit" type="submit">Verify and continue</button>
    </form>
    <form method="POST" action="{{ route('login.otp.resend') }}" style="margin-top:14px;text-align:center;">
        @csrf
        <button type="submit" style="padding:5px;border:0;background:none;color:#85dbcd;font:inherit;font-size:12px;cursor:pointer;">Send a new code</button>
    </form>
    <p class="auth-switch"><a href="{{ route('home') }}">Back to sign in</a></p>
</section>
@endsection

*/
