@extends('layouts.app')

@section('content')
<section class="auth-card" aria-labelledby="login-title">
    <p class="auth-eyebrow">Student workspace</p>
    <h1 id="login-title">Welcome back</h1>
    <p class="auth-intro">Sign in to see your deadlines and decide what to tackle next.</p>

    @if (session('status'))<div class="auth-alert" role="status">{{ session('status') }}</div>@endif
    @if (session('error'))<div class="auth-alert" role="alert">{{ session('error') }}</div>@endif

    <form class="auth-form" method="POST" action="{{ route('login.store') }}">
        @csrf
        <div class="auth-field">
            <label for="email">Email address</label>
            <div class="auth-input-shell">
                <svg class="auth-input-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="3" stroke="currentColor" stroke-width="1.7"/><path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <input class="auth-input" id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
            </div>
            @error('email')<span class="auth-error">{{ $message }}</span>@enderror
        </div>
        <div class="auth-field">
            <label for="password">Password</label>
            <div class="auth-input-shell">
                <svg class="auth-input-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2.5" stroke="currentColor" stroke-width="1.7"/><path d="M8 10V7a4 4 0 0 1 8 0v3m-4 5v2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                <input class="auth-input" id="password" type="password" name="password" required autocomplete="current-password">
            </div>
            @error('password')<span class="auth-error">{{ $message }}</span>@enderror
        </div>
        <label class="auth-check"><input type="checkbox" name="remember" value="1"> Remember me</label>
        <button class="auth-submit" type="submit">Sign in</button>
    </form>
    <p class="auth-switch">New here? <a href="{{ route('register') }}">Create an account</a></p>
</section>
@endsection
