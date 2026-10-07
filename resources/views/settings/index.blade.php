@extends('layouts.risk')

@section('title', 'Settings')

@section('content')
    <style>
        .settings-page { display: grid; grid-template-columns: 215px minmax(0, 1fr); gap: 22px; align-items: start; }
        .settings-menu, .settings-card { border: 1px solid var(--panel-line); border-radius: 10px; background: var(--panel); }
        .settings-menu { position: sticky; top: 88px; display: grid; gap: 4px; padding: 10px; }
        .settings-menu a { padding: 10px 12px; border-radius: 7px; color: var(--muted); font-size: 12px; text-decoration: none; }
        .settings-menu a:hover, .settings-menu a:focus-visible { background: color-mix(in srgb, var(--teal) 12%, transparent); color: var(--text); }
        .settings-content { display: grid; gap: 16px; min-width: 0; }
        .settings-card { padding: 20px; scroll-margin-top: 88px; }
        .settings-card h2 { margin: 0; font-family: 'Raleway', sans-serif; font-size: 18px; }
        .settings-card > p { margin: 6px 0 16px; color: var(--muted); font-size: 12px; line-height: 1.6; }
        .settings-row { display: flex; align-items: center; justify-content: space-between; gap: 18px; padding: 14px 0; border-top: 1px solid var(--panel-line); }
        .settings-row:first-of-type { border-top: 0; }
        .settings-row strong { display: block; font-size: 12px; }
        .settings-row small { display: block; margin-top: 4px; color: var(--muted); font-size: 11px; line-height: 1.5; }
        .settings-value { color: var(--muted); font-size: 12px; overflow-wrap: anywhere; text-align: right; }
        .settings-actions { display: flex; flex-wrap: wrap; gap: 8px; }
        .settings-action { min-height: 36px; padding: 0 12px; border: 1px solid var(--panel-line); border-radius: 7px; background: transparent; color: var(--text); cursor: pointer; font-size: 11px; }
        .settings-action[aria-pressed="true"], .settings-action:hover { border-color: var(--teal); color: var(--teal); }
        .settings-switch { position: relative; flex: 0 0 40px; width: 40px; height: 23px; }
        .settings-switch input { position: absolute; width: 1px; height: 1px; opacity: 0; }
        .settings-switch span { position: absolute; inset: 0; border: 1px solid var(--panel-line); border-radius: 20px; background: var(--page); cursor: pointer; transition: .18s ease; }
        .settings-switch span::before { position: absolute; top: 3px; left: 3px; width: 15px; height: 15px; border-radius: 50%; background: var(--muted); content: ''; transition: .18s ease; }
        .settings-switch input:checked + span { border-color: var(--teal); background: color-mix(in srgb, var(--teal) 28%, var(--page)); }
        .settings-switch input:checked + span::before { transform: translateX(17px); background: var(--teal); }
        .settings-switch input:focus-visible + span { outline: 2px solid var(--teal); outline-offset: 3px; }
        .settings-empty { padding: 16px; border: 1px dashed var(--panel-line); border-radius: 8px; color: var(--muted); font-size: 12px; }
        .settings-form { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
        .settings-form label { display: grid; gap: 6px; color: var(--muted); font-size: 11px; }
        .settings-form input { width: 100%; min-width: 0; min-height: 38px; padding: 8px 10px; border: 1px solid var(--panel-line); border-radius: 6px; background: var(--page); color: var(--text); }
        .settings-form .button { justify-self: start; grid-column: 1 / -1; }
        .settings-profile-photo { display: flex; align-items: center; gap: 12px; margin: 0 0 16px; }
        .settings-profile-photo img, .settings-profile-photo .avatar { width: 56px; height: 56px; flex: 0 0 56px; border-radius: 50%; object-fit: cover; }
        .settings-profile-photo p { margin: 0; color: var(--muted); font-size: 11px; }
        .settings-status { min-height: 18px; color: var(--green); font-size: 11px; }
        @media (max-width: 760px) { .settings-page { grid-template-columns: 1fr; } .settings-menu { position: static; display: flex; overflow-x: auto; } .settings-menu a { flex: 0 0 auto; } .settings-form { grid-template-columns: 1fr; } }
    </style>

    <div class="page-heading">
        <div><p class="eyebrow">Preferences &amp; security</p><h1>Settings</h1><p>Manage your student profile, how the app looks, and account access.</p></div>
    </div>

    <div class="settings-page">
        <nav class="settings-menu" aria-label="Settings sections">
            <a href="{{ route('settings.index', 'school-profile') }}" @if($section === 'school-profile') aria-current="page" @endif>School profile</a>
            <a href="{{ route('settings.index', 'account') }}" @if($section === 'account') aria-current="page" @endif>Account</a>
            <a href="{{ route('settings.index', 'canvas') }}" @if($section === 'canvas') aria-current="page" @endif>Canvas sync account</a>
            <a href="{{ route('settings.index', 'appearance') }}" @if($section === 'appearance') aria-current="page" @endif>Appearance</a>
            <a href="{{ route('settings.index', 'accessibility') }}" @if($section === 'accessibility') aria-current="page" @endif>Accessibility</a>
            <a href="{{ route('settings.index', 'notifications') }}" @if($section === 'notifications') aria-current="page" @endif>Notifications</a>
            <a href="{{ route('settings.index', 'emails') }}" @if($section === 'emails') aria-current="page" @endif>Emails</a>
            <a href="{{ route('settings.index', 'password-auth') }}" @if($section === 'password-auth') aria-current="page" @endif>Password &amp; authentication</a>
            <a href="{{ route('settings.index', 'web-session') }}" @if($section === 'web-session') aria-current="page" @endif>Web session</a>
            <a href="{{ route('settings.index', 'blocked-users') }}" @if($section === 'blocked-users') aria-current="page" @endif>Blocked users</a>
        </nav>

        <div class="settings-content">
            @if($section === 'school-profile')<section class="settings-card" id="school-profile" aria-labelledby="school-title">
                <h2>Student profile</h2>
                <p>Update the name and photo shown in your sidebar.</p>
                <div class="settings-profile-photo">
                    @if (auth()->user()->profile_image)
                        <img src="{{ asset('storage/'.auth()->user()->profile_image) }}" alt="{{ auth()->user()->nickname ?: auth()->user()->name }}'s profile photo">
                    @else
                        <span class="avatar" aria-hidden="true">{{ strtoupper(substr(auth()->user()->nickname ?: auth()->user()->name, 0, 1)) }}</span>
                    @endif
                    <p>{{ auth()->user()->profile_image ? 'Current profile photo' : 'No profile photo uploaded yet.' }}</p>
                </div>
                <form class="settings-form" method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                    @csrf @method('PUT')
                    <label>Full name<input name="name" maxlength="255" value="{{ old('name', auth()->user()->name) }}" required></label>
                    <label>Nickname<input name="nickname" maxlength="60" value="{{ old('nickname', auth()->user()->nickname) }}"></label>
                    <label>Email<input name="email" type="email" value="{{ old('email', auth()->user()->email) }}" required></label>
                    <label>Profile image<input name="profile_image" type="file" accept="image/*"></label>
                    <button class="button small" type="submit">Save student profile</button>
                </form>
            </section><section class="settings-card" id="school-profile" aria-labelledby="school-title">
                <h2 id="school-title">School profile</h2>
                <p>Add school details to personalize your workspace. These details are saved in this browser.</p>
                <form class="settings-form" id="school-profile-form">
                    <label>School or institution<input name="school" maxlength="120" placeholder="e.g. University name"></label>
                    <label>Program or course<input name="program" maxlength="120" placeholder="e.g. BS Computer Science"></label>
                    <label>Year level<input name="year" maxlength="40" placeholder="e.g. 2nd year"></label>
                    <button class="button small" type="submit">Save school profile</button>
                    <span class="settings-status" id="school-profile-status" role="status"></span>
                </form>
            </section>@endif

            @if($section === 'account')<section class="settings-card" id="account" aria-labelledby="account-title">
                <h2 id="account-title">Account</h2>
                <p>Your account identity and sign-in email.</p>
                <div class="settings-row"><div><strong>Name</strong><small>Account display name</small></div><span class="settings-value">{{ auth()->user()->name }}</span></div>
                <div class="settings-row"><div><strong>Email address</strong><small>Used for sign-in and account messages</small></div><span class="settings-value">{{ auth()->user()->email }}</span></div>
            </section>@endif

            @if($section === 'canvas')<section class="settings-card" id="canvas-account" aria-labelledby="canvas-account-title">
                <h2 id="canvas-account-title">Canvas sync account</h2>
                <p>Authorize your Canvas account so assignments sync into your task list. Your Canvas access token is stored encrypted.</p>
                @if(auth()->user()->canvas_access_token)
                    <div class="settings-row"><div><strong>Connected Canvas account</strong><small>{{ auth()->user()->canvas_user_name ?: 'Canvas user' }} · ID {{ auth()->user()->canvas_user_id }}</small></div><span class="settings-value">Connected</span></div>
                    <div class="settings-row"><div><strong>Manage authorization</strong><small>Reconnect to replace the Canvas account linked here, or disconnect it.</small></div><div class="settings-actions"><a class="button secondary small" href="{{ route('canvas.connect') }}">Replace account</a><form method="POST" action="{{ route('canvas.disconnect') }}" onsubmit="return confirm('Disconnect this Canvas account?')">@csrf @method('DELETE')<button class="button danger small" type="submit">Disconnect</button></form></div></div>
                @else
                    @if($canvasApiKeyConfigured)
                        <div class="settings-empty" style="margin-bottom:14px;">A Canvas API key is configured. You can sync Canvas assignments directly into your task list.</div>
                        <div class="settings-actions"><a class="button" href="{{ route('tasks.index') }}">Go to tasks and sync</a><a class="button secondary" href="{{ route('dashboard') }}">Continue to dashboard</a></div>
                    @else
                        <div class="settings-empty" style="margin-bottom:14px;">No Canvas account is connected yet.</div>
                        <div class="settings-actions"><a class="button" href="{{ route('canvas.connect') }}">Connect Canvas account</a><a class="button secondary" href="{{ route('dashboard') }}">Continue without Canvas</a></div>
                    @endif
                @endif
            </section>@endif

            @if($section === 'appearance')<section class="settings-card" id="appearance" aria-labelledby="appearance-title">
                <h2 id="appearance-title">Appearance</h2>
                <p>Choose the color theme for your workspace.</p>
                <div class="settings-row"><div><strong>Theme</strong><small>Your selection is saved on this device.</small></div>
                    <div class="settings-actions" role="group" aria-label="Color theme">
                        <button class="settings-action" type="button" data-theme-choice="light">Light</button>
                        <button class="settings-action" type="button" data-theme-choice="dark">Dark</button>
                    </div>
                </div>
            </section>@endif

            @if($section === 'accessibility')<section class="settings-card" id="accessibility" aria-labelledby="accessibility-title">
                <h2 id="accessibility-title">Accessibility</h2>
                <p>Adjust visual preferences for this browser.</p>
                <div class="settings-row"><div><strong>Larger text</strong><small>Increase the size of text across the workspace.</small></div><label class="settings-switch"><input type="checkbox" data-preference="large-text" aria-label="Larger text"><span></span></label></div>
                <div class="settings-row"><div><strong>Reduce motion</strong><small>Limit animated transitions and movement.</small></div><label class="settings-switch"><input type="checkbox" data-preference="reduce-motion" aria-label="Reduce motion"><span></span></label></div>
            </section>@endif

            @if($section === 'notifications')<section class="settings-card" id="notifications" aria-labelledby="notifications-title">
                <h2 id="notifications-title">Notifications</h2>
                <p>Choose which study reminders you want enabled in this browser.</p>
                <div class="settings-row"><div><strong>Deadline reminders</strong><small>Show reminders for approaching and overdue tasks.</small></div><label class="settings-switch"><input type="checkbox" data-preference="deadline-notifications" aria-label="Deadline reminders"><span></span></label></div>
                <div class="settings-row"><div><strong>Friend requests</strong><small>Show updates when someone sends or responds to a request.</small></div><label class="settings-switch"><input type="checkbox" data-preference="friend-notifications" aria-label="Friend request notifications"><span></span></label></div>
            </section>@endif

            @if($section === 'emails')<section class="settings-card" id="emails" aria-labelledby="emails-title">
                <h2 id="emails-title">Emails</h2>
                <p>Manage the email address associated with your account.</p>
                <div class="settings-row"><div><strong>Current email</strong><small>Email changes require verification.</small></div><span class="settings-value">{{ auth()->user()->email }}</span></div>
            </section>@endif

            @if($section === 'password-auth')<section class="settings-card" id="password-auth" aria-labelledby="password-title">
                <h2 id="password-title">Password &amp; authentication</h2>
                <p>Protect your account and control how you sign in.</p>
                <div class="settings-row"><div><strong>Password</strong><small>Password changes are managed through the secure account recovery flow.</small></div><a class="text-button" href="{{ route('home') }}#forgot-password">Password help</a></div>
                <div class="settings-row"><div><strong>Email verification</strong><small>A one-time code is used to verify sign-ins.</small></div><span class="settings-value">Enabled</span></div>
            </section>@endif

            @if($section === 'web-session')<section class="settings-card" id="web-session" aria-labelledby="session-title">
                <h2 id="session-title">Web session</h2>
                <p>Review the current browser session.</p>
                <div class="settings-row"><div><strong>Current session</strong><small>This browser · signed in as {{ auth()->user()->email }}</small></div><span class="settings-value">Active</span></div>
                <div class="settings-row"><div><strong>Sign out</strong><small>End this session on the current device.</small></div><form method="POST" action="{{ route('logout') }}">@csrf<button class="button secondary small" type="submit">Sign out</button></form></div>
            </section>@endif

            @if($section === 'blocked-users')<section class="settings-card" id="blocked-users" aria-labelledby="blocked-title">
                <h2 id="blocked-title">Moderation: Blocked users</h2>
                <p>Blocked users would appear here and could no longer send you friend requests.</p>
                <div class="settings-empty">You have not blocked anyone.</div>
            </section>@endif
        </div>
    </div>

    <script>
        (() => {
            const prefix = 'deadline-risk-setting-';
            const schoolForm = document.getElementById('school-profile-form');
            const schoolStatus = document.getElementById('school-profile-status');
            const schoolFields = ['school', 'program', 'year'];

            if (schoolForm) {
                try {
                    const schoolProfile = JSON.parse(localStorage.getItem(prefix + 'school-profile') || '{}');
                    schoolFields.forEach((field) => {
                        schoolForm.elements[field].value = schoolProfile[field] || '';
                    });
                } catch (error) {
                    // Keep the form usable when browser storage is unavailable or invalid.
                }

                schoolForm.addEventListener('submit', (event) => {
                    event.preventDefault();
                    const schoolProfile = Object.fromEntries(schoolFields.map((field) => [field, schoolForm.elements[field].value.trim()]));

                    try {
                        localStorage.setItem(prefix + 'school-profile', JSON.stringify(schoolProfile));
                        schoolStatus.textContent = 'School profile saved on this device.';
                    } catch (error) {
                        schoolStatus.textContent = 'Could not save in this browser.';
                    }
                });
            }

            document.querySelectorAll('[data-preference]').forEach((input) => {
                const key = prefix + input.dataset.preference;
                input.checked = localStorage.getItem(key) === 'true';
                input.addEventListener('change', () => {
                    localStorage.setItem(key, String(input.checked));
                    if (input.dataset.preference === 'large-text') {
                        document.documentElement.style.fontSize = input.checked ? '18px' : '';
                    }
                    if (input.dataset.preference === 'reduce-motion') {
                        document.documentElement.style.setProperty('--settings-motion', input.checked ? '0s' : '.18s');
                    }
                });
            });

            const savedTheme = localStorage.getItem('deadline-risk-theme') || 'dark';
            document.querySelectorAll('[data-theme-choice]').forEach((button) => {
                button.setAttribute('aria-pressed', String(button.dataset.themeChoice === savedTheme));
                button.addEventListener('click', () => {
                    const theme = button.dataset.themeChoice;
                    localStorage.setItem('deadline-risk-theme', theme);
                    document.documentElement.dataset.theme = theme;
                    document.querySelectorAll('[data-theme-choice]').forEach((choice) => {
                        choice.setAttribute('aria-pressed', String(choice === button));
                    });
                    document.getElementById('theme-toggle')?.setAttribute('aria-pressed', String(theme === 'light'));
                });
            });
        })();
    </script>
@endsection
