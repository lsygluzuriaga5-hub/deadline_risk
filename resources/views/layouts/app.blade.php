<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Deadline Risk AI</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=montserrat:400,500,600,700,800|raleway:500,600,700,800" rel="stylesheet">

    <!-- Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @endif
    <script>
        try {
            const savedTheme = localStorage.getItem('deadline-risk-theme');
            if (savedTheme === 'light' || savedTheme === 'dark') document.documentElement.dataset.theme = savedTheme;
        } catch (error) {}
    </script>
    <style>
        :root { color-scheme: dark; --page: #071E20; --sidebar: #0B292B; --panel: #103638; --line: #1D4B4D; --text: #F1F7F6; --muted: #8FAEAE; --teal: #43D6C5; --ink: #071E20; --red: #FB7185; --green: #4ADE80; }
        :root[data-theme="light"] { color-scheme: light; --page: #F4F8F7; --sidebar: #E7F0EF; --panel: #FFFFFF; --line: #C8D9D7; --text: #173638; --muted: #526D6C; --teal: #087F75; --ink: #071E20; --red: #C83F59; --green: #16733A; }
        body { min-height: 100vh; background: radial-gradient(ellipse at 76% 4%, rgba(67,214,197,.1), transparent 34%), repeating-linear-gradient(0deg, transparent 0, transparent 43px, rgba(196,230,222,.025) 44px), var(--page); color: var(--text); font-family: 'Montserrat', sans-serif; }
        .auth-header { min-height: 68px; border-bottom: 1px solid var(--line); background: rgba(11,41,43,.94); }
        .auth-nav { display: flex; align-items: center; justify-content: space-between; gap: 16px; width: min(100% - 32px, 1120px); min-height: 68px; margin: 0 auto; }
        .auth-brand { display: inline-flex; align-items: center; gap: 10px; min-width: 0; color: var(--text); font-family: 'Raleway', sans-serif; font-size: 16px; font-weight: 800; text-decoration: none; }
        .auth-brand:hover { color: white; }
        .auth-brand-mark { display: grid; width: 34px; height: 34px; flex: 0 0 34px; place-items: center; border-radius: 8px; background: var(--teal); color: #082729; }
        .auth-links { display: flex; align-items: center; gap: 16px; padding: 0; margin: 0; list-style: none; }
        .auth-links a { color: #bbd3ce; font-size: 13px; text-decoration: none; }
        .auth-links a:hover { color: white; }
        .auth-main { position: relative; isolation: isolate; display: flex; justify-content: center; min-height: calc(100vh - 68px); padding: clamp(38px, 9vh, 86px) 16px 48px; overflow: hidden; }
        .auth-scene { position: absolute; z-index: -1; inset: 0; overflow: hidden; color: var(--teal); opacity: .2; pointer-events: none; }
        .auth-scene svg { width: 100%; height: 100%; }
        .scene-calendar, .scene-clock, .scene-chart { transform-box: fill-box; transform-origin: center; animation: scene-float 9s ease-in-out infinite; }
        .scene-clock { animation-delay: -3s; }
        .scene-chart { animation-delay: -6s; }
        .scene-clock-hand { opacity: .85; }
        .scene-progress { stroke-dasharray: 12 9; animation: progress-dash 16s linear infinite; }
        .auth-main::before { position: absolute; z-index: -1; inset: -30%; background: radial-gradient(ellipse at 22% 32%, rgba(67,214,197,.13), transparent 28%), radial-gradient(ellipse at 80% 70%, rgba(167,139,250,.1), transparent 27%); content: ''; animation: ambient-drift 24s ease-in-out infinite alternate; }
        @keyframes scene-float { 0%, 100% { translate: 0 0; } 50% { translate: 0 -10px; } }
        @keyframes progress-dash { to { stroke-dashoffset: -168; } }
        @keyframes ambient-drift { from { transform: translate3d(-2%, -1%, 0) scale(.98); } to { transform: translate3d(2%, 1%, 0) scale(1.04); } }
        .auth-card { position: relative; z-index: 1; box-sizing: border-box; width: min(100%, 578px); height: fit-content; padding: clamp(24px, 6vw, 38px); border: 1px solid var(--line); border-radius: 12px; background: rgba(16,54,56,.96); box-shadow: 0 24px 80px rgba(0,0,0,.24); backdrop-filter: blur(12px); }
        .auth-card, .auth-card * { box-sizing: border-box; }
        .auth-eyebrow { margin: 0 0 9px; color: var(--teal); font-size: 10px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
        .auth-card h1 { margin: 0; color: var(--text); font-family: 'Raleway', sans-serif; font-size: 24px; font-weight: 700; }
        .auth-intro { margin: 7px 0 23px; color: var(--muted); font-size: 13px; line-height: 1.5; }
        .auth-form { display: grid; gap: 18px; }
        .auth-field { display: grid; gap: 8px; min-width: 0; }
        .auth-field label { color: #c5dad5; font-size: 12px; font-weight: 600; }
        .auth-input-shell { position: relative; width: 100%; min-width: 0; }
        .auth-input { display: block; width: 100%; min-width: 0; min-height: 52px; padding: 0 14px; border: 1px solid #315557; border-radius: 8px; outline: none; background: #092325; color: var(--text); font: inherit; font-size: 14px; transition: border-color .18s, box-shadow .18s, background .18s; }
        .auth-input-shell .auth-input { padding-left: 44px; }
        .auth-input:hover { border-color: var(--teal); }
        .auth-input:focus { border-color: var(--teal); box-shadow: 0 0 0 4px rgba(67,214,197,.15); }
        .auth-input-icon { position: absolute; top: 50%; left: 15px; width: 18px; height: 18px; color: var(--muted); pointer-events: none; transform: translateY(-50%); transition: color .18s; }
        .auth-input-shell:focus-within .auth-input-icon { color: var(--teal); }
        .auth-check { display: flex; align-items: center; gap: 9px; color: #c5dad5; font-size: 12px; }
        .auth-check input { width: 16px; height: 16px; margin: 0; accent-color: var(--teal); }
        .auth-submit { width: 100%; min-height: 44px; border: 0; border-radius: 5px; background: var(--teal); color: var(--ink); font-size: 13px; font-weight: 700; cursor: pointer; }
        .auth-submit:hover { filter: brightness(1.08); }
        .auth-switch { margin: 17px 0 0; color: var(--muted); font-size: 12px; text-align: center; }
        .auth-switch a { color: #85dbcd; text-decoration: none; }
        .auth-error { color: var(--red); font-size: 11px; }
        .auth-alert { margin-bottom: 16px; padding: 10px 12px; border: 1px solid rgba(74,222,128,.25); border-radius: 5px; color: var(--green); font-size: 12px; }
        .theme-toggle { display: inline-flex; align-items: center; justify-content: center; min-width: 36px; min-height: 34px; padding: 0 9px; border: 1px solid var(--line); border-radius: 5px; background: transparent; color: var(--text); font-size: 11px; cursor: pointer; }
        .theme-toggle:hover { border-color: var(--teal); }
        :root[data-theme="light"] body { background: var(--page); }
        :root[data-theme="light"] .auth-header { background: rgba(231,240,239,.96); }
        :root[data-theme="light"] .auth-card { border-color: rgba(29,75,77,.18); background: rgba(255,255,255,.94); }
        :root[data-theme="light"] .auth-links a,
        :root[data-theme="light"] .auth-field label,
        :root[data-theme="light"] .auth-check { color: var(--text); }
        :root[data-theme="light"] .auth-brand:hover,
        :root[data-theme="light"] .auth-links a:hover { color: var(--teal); }
        :root[data-theme="light"] .auth-input { border-color: #B9CFCC; background: rgba(248,251,250,.94); color: var(--text); }
        :root[data-theme="light"] .auth-scene { opacity: .13; }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { scroll-behavior: auto !important; animation-duration: .01ms !important; animation-iteration-count: 1 !important; transition-duration: .01ms !important; }
        }
        @media (max-width: 600px) {
            .auth-scene { display: none; }
            .auth-main::before { inset: 0; }
        }
        :root[data-theme="light"] .auth-switch a { color: var(--teal); }
        @media (max-width: 400px) {
            .auth-nav { width: calc(100% - 24px); gap: 9px; }
            .auth-brand { gap: 7px; font-size: 14px; }
            .auth-brand-mark { width: 30px; height: 30px; flex-basis: 30px; }
            .auth-links { gap: 10px; }
            .auth-links a { font-size: 12px; }
            .auth-main { padding: 30px 12px 32px; }
            .auth-card { padding: 21px 18px 23px; }
        }
    </style>
</head>
<body>
    <div id="app">
        <header class="auth-header">
            <nav class="auth-nav" aria-label="Main navigation">
                <a class="auth-brand" href="{{ url('/') }}"><span class="auth-brand-mark" aria-hidden="true">D</span><span>Deadline Risk AI</span></a>
                <ul class="auth-links">
                    <li><button class="theme-toggle" id="theme-toggle" type="button" aria-label="Switch to light mode" aria-pressed="false" title="Switch to light mode">☀</button></li>
                    @guest
                        <li><a href="{{ route('login') }}">Log in</a></li>
                        <li><a href="{{ route('register') }}">Register</a></li>
                    @else
                        <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    @endguest
                </ul>
            </nav>
        </header>

        <main class="auth-main">
            <div class="auth-scene" aria-hidden="true">
                <svg viewBox="0 0 1920 900" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                    <g class="scene-calendar" transform="translate(230 220)" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="0" y="18" width="190" height="174" rx="16" stroke-width="3" />
                        <path d="M0 62h190M46 0v37M144 0v37" stroke-width="5" />
                        <path d="M35 91h22m22 0h22m22 0h22M35 124h22m22 0h22m22 0h22M35 157h22m22 0h22" stroke-width="7" opacity=".65" />
                        <rect x="112" y="111" width="32" height="25" rx="6" fill="currentColor" opacity=".35" stroke="none" />
                    </g>
                    <g class="scene-clock" transform="translate(1480 500)" fill="none" stroke="currentColor" stroke-linecap="round">
                        <circle cx="92" cy="92" r="82" stroke-width="3" />
                        <circle cx="92" cy="92" r="67" stroke-width="1.5" stroke-dasharray="2 13" opacity=".75" />
                        <g class="scene-clock-hand" stroke-width="5"><path d="M92 91V43M92 92l35 24" /></g>
                        <circle cx="92" cy="92" r="6" fill="currentColor" stroke="none" />
                    </g>
                    <g class="scene-chart" transform="translate(470 660)" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M0 118h260M0 118V0" stroke-width="2" opacity=".5" />
                        <path class="scene-progress" d="m18 95 58-31 55 15 57-48 55 13" stroke-width="5" />
                        <circle cx="243" cy="44" r="8" fill="currentColor" stroke="none" />
                    </g>
                    <path d="M492 294c150-82 265-74 378-23m510 157c-86-44-148-52-217-33" fill="none" stroke="currentColor" stroke-width="2" stroke-dasharray="5 12" opacity=".45" />
                    <circle cx="465" cy="350" r="5" fill="currentColor" opacity=".55" />
                    <circle cx="1375" cy="300" r="4" fill="currentColor" opacity=".45" />
                </svg>
            </div>
            @yield('content')
        </main>
    </div>
    <script>
        const themeToggle = document.getElementById('theme-toggle');
        const themeKey = 'deadline-risk-theme';
        const setThemeButton = () => {
            const isLight = document.documentElement.dataset.theme === 'light';
            themeToggle.textContent = isLight ? '☾' : '☀';
            themeToggle.setAttribute('aria-label', `Switch to ${isLight ? 'dark' : 'light'} mode`);
            themeToggle.setAttribute('aria-pressed', isLight);
            themeToggle.title = `Switch to ${isLight ? 'dark' : 'light'} mode`;
        };
        setThemeButton();
        themeToggle.addEventListener('click', () => {
            const nextTheme = document.documentElement.dataset.theme === 'light' ? 'dark' : 'light';
            document.documentElement.dataset.theme = nextTheme;
            try {
                localStorage.setItem(themeKey, nextTheme);
            } catch (error) {}
            setThemeButton();
        });
    </script>
</body>
</html>
