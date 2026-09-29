@extends('layouts.app')

@section('content')
<section class="auth-card" aria-labelledby="register-title">
    <p class="auth-eyebrow">Student workspace</p>
    <h1 id="register-title">Create your account</h1>
    <p class="auth-intro">Keep coursework, progress, and deadlines in one place.</p>

    <form class="auth-form" method="POST" action="{{ route('register.store') }}">
        @csrf
        <div class="auth-field">
            <label for="name">Name</label>
            <input class="auth-input" id="name" type="text" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>
            @error('name')<span class="auth-error">{{ $message }}</span>@enderror
        </div>
        <div class="auth-field">
            <label for="email">Email address</label>
            <input class="auth-input" id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
            @error('email')<span class="auth-error">{{ $message }}</span>@enderror
        </div>
        <div class="auth-field">
            <label for="password">Password</label>
            <input class="auth-input" id="password" type="password" name="password" required autocomplete="new-password">
            @error('password')<span class="auth-error">{{ $message }}</span>@enderror
        </div>
        <div class="auth-field">
            <label for="password-confirm">Confirm password</label>
            <input class="auth-input" id="password-confirm" type="password" name="password_confirmation" required autocomplete="new-password">
        </div>
        <button class="auth-submit" type="submit">Create account</button>
    </form>
    <p class="auth-switch">Already registered? <a href="{{ route('login') }}">Sign in</a></p>
</section>
@endsection
