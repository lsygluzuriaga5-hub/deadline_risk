<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Deadline Risk AI') · Deadline Risk AI</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=montserrat:400,500,600,700,800|raleway:500,600,700,800" rel="stylesheet">
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
        :root {
            color-scheme: dark;
            --page: #071E20;
            --sidebar: #0B292B;
            --panel: #103638;
            --panel-line: #1D4B4D;
            --text: #F1F7F6;
            --muted: #8FAEAE;
            --teal: #43D6C5;
            --mint: #d8f0e9;
            --ink: #071E20;
            --purple: #A78BFA;
            --red: #FB7185;
            --orange: #FBBF24;
            --green: #4ADE80;
            --blue: #60A5FA;
            --radius: 8px;
        }
        :root[data-theme="light"] {
            color-scheme: light;
            --page: #F4F8F7;
            --sidebar: #E7F0EF;
            --panel: #FFFFFF;
            --panel-line: #C8D9D7;
            --text: #173638;
            --muted: #526D6C;
            --teal: #087F75;
            --mint: #D8F0E9;
            --ink: #071E20;
            --purple: #7353C3;
            --red: #C83F59;
            --orange: #946000;
            --green: #16733A;
            --blue: #2868B2;
        }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; background: var(--page); color: var(--text); font-family: 'Montserrat', sans-serif; }
        button, input, select, textarea { font: inherit; }
        a { color: inherit; }
        .app-shell { display: grid; grid-template-columns: 236px minmax(0, 1fr); min-height: 100vh; transition: grid-template-columns .22s ease; }
        .sidebar { position: sticky; top: 0; display: flex; flex-direction: column; min-height: 100vh; height: 100vh; padding: 24px 16px 18px; background: var(--sidebar); border-right: 1px solid var(--panel-line); }
        .brand { display: flex; align-items: center; gap: 11px; margin: 0 0 35px 4px; color: var(--text); text-decoration: none; font-family: 'Raleway', sans-serif; font-size: 16px; font-weight: 800; white-space: nowrap; }
        .brand-mark-svg { display: block; width: 38px; height: 38px; flex: 0 0 38px; padding: 7px; border-radius: 9px; background: var(--teal); color: #062426; }
        .nav-label { margin: 0 0 10px 12px; color: var(--muted); font-size: 10px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
        .side-nav { display: grid; gap: 5px; }
        .side-nav a, .sidebar-toggle { display: flex; align-items: center; gap: 12px; min-height: 42px; padding: 0 12px; border: 1px solid transparent; border-radius: 6px; color: var(--muted); font-size: 13px; text-decoration: none; transition: background .18s, color .18s; }
        .side-nav a:hover, .side-nav a.active { background: rgba(67, 214, 197, .13); color: var(--text); }
        .side-nav a.active { border-color: rgba(67, 214, 197, .18); box-shadow: inset 2px 0 var(--teal); }
        .nav-icon { width: 19px; flex: 0 0 19px; color: var(--teal); font-size: 17px; text-align: center; }
        .nav-icon.nav-blue { color: var(--blue); }
        .nav-icon.nav-purple { color: var(--purple); }
        .nav-icon.nav-orange { color: var(--orange); }
        .nav-icon.nav-green { color: var(--green); }
        .nav-icon-svg { width: 21px; height: 21px; flex: 0 0 21px; color: var(--teal); }
        .nav-icon-svg.nav-blue { color: var(--blue); }
        .nav-icon-svg.nav-purple { color: var(--purple); }
        .nav-icon-svg.nav-orange { color: var(--orange); }
        .nav-icon-svg.nav-green { color: var(--green); }
        .sidebar-support { margin-top: auto; padding-top: 22px; }
        .sidebar-bottom { display: grid; gap: 12px; margin-top: 14px; }
        .sidebar-user { display: flex; align-items: center; gap: 10px; padding: 12px 7px 4px; border-top: 1px solid var(--panel-line); }
        .sidebar-user img { width: 36px; height: 36px; flex: 0 0 36px; border-radius: 50%; object-fit: cover; }
        .side-nav a, .logout-button { font-weight: 700; }
        .brand-name { font-weight: 800; }
        .avatar { display: grid; width: 32px; height: 32px; flex: 0 0 32px; place-items: center; border-radius: 50%; background: #d9efe8; color: #173c3a; font-size: 12px; font-weight: 700; }
        .sidebar-user strong { display: block; overflow: hidden; max-width: 145px; font-size: 12px; text-overflow: ellipsis; white-space: nowrap; }
        .sidebar-user small { color: var(--muted); font-size: 11px; }
        .logout-button { width: 100%; padding: 8px 12px; border: 0; border-radius: 6px; background: transparent; color: #a9c6c0; text-align: left; cursor: pointer; }
        .logout-button:hover { background: rgba(255,255,255,.06); color: white; }
        .main-area { min-width: 0; }
        .topbar { position: sticky; z-index: 5; top: 0; display: flex; align-items: center; gap: 15px; min-height: 70px; padding: 0 34px; border-bottom: 1px solid var(--panel-line); background: rgba(7, 30, 32, .94); backdrop-filter: blur(14px); }
        .mobile-menu, .collapse-button { display: grid; width: 36px; height: 36px; place-items: center; border: 1px solid var(--panel-line); border-radius: 6px; background: transparent; color: var(--text); cursor: pointer; }
        .collapse-button { margin: 0 0 26px auto; }
        .global-search { display: flex; align-items: center; gap: 9px; width: min(410px, 52vw); height: 38px; padding: 0 12px; border: 1px solid var(--panel-line); border-radius: 6px; background: #0c292b; color: var(--muted); }
        .global-search input { width: 100%; border: 0; outline: 0; background: transparent; color: var(--text); font-size: 13px; }
        .global-search input::placeholder { color: #789995; }
        .topbar-spacer { flex: 1; }
        .date-label { color: var(--muted); font-size: 20px; font-style: oblique; white-space: nowrap; }
        .notification-menu { position: relative; }
        .notification-menu summary { position: relative; display: grid; width: 38px; height: 38px; place-items: center; border: 1px solid var(--panel-line); border-radius: 6px; color: var(--text); cursor: pointer; list-style: none; }
        .notification-menu summary::-webkit-details-marker { display: none; }
        .notification-count { position: absolute; top: 4px; right: 4px; display: grid; min-width: 15px; height: 15px; place-items: center; padding: 0 3px; border-radius: 8px; background: var(--red); color: white; font-size: 9px; }
        .notification-panel { position: absolute; z-index: 10; top: 48px; right: 0; width: min(330px, calc(100vw - 24px)); padding: 14px; border: 1px solid var(--panel-line); border-radius: 8px; background: var(--sidebar); box-shadow: 0 15px 45px rgba(0,0,0,.35); }
        .notification-panel h2 { margin: 0 0 10px; font-family: 'Raleway', sans-serif; font-size: 14px; }
        .notification-item { display: flex; width: 100%; gap: 9px; padding: 10px 2px; border: 0; border-top: 1px solid var(--panel-line); background: transparent; color: #d9e9e5; font-size: 12px; text-align: left; cursor: pointer; }
        .notification-item:hover { color: white; }
        .notification-item.is-read { opacity: .45; }
        .notice-dot { width: 8px; height: 8px; flex: 0 0 8px; margin-top: 4px; border-radius: 50%; background: var(--teal); }
        .notice-dot.high { background: var(--red); }
        .notice-dot.medium { background: var(--orange); }
        .notice-dot.low { background: var(--green); }
        .page-wrap { width: min(1380px, 100%); margin: 0 auto; padding: 31px 34px 55px; }
        .page-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; margin-bottom: 23px; }
        .page-heading h1 { margin: 0; font-family: 'Raleway', sans-serif; font-size: 25px; font-weight: 700; }
        .page-heading p { margin: 5px 0 0; color: var(--muted); font-size: 13px; }
        .eyebrow { margin: 0 0 7px; color: var(--teal); font-size: 10px; font-weight: 700; letter-spacing: .13em; text-transform: uppercase; }
        .button { display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 38px; padding: 0 14px; border: 1px solid transparent; border-radius: 6px; background: var(--teal); color: var(--ink); font-size: 12px; font-weight: 700; text-decoration: none; cursor: pointer; transition: transform .18s, filter .18s; }
        .button:hover { transform: translateY(-1px); filter: brightness(1.08); color: var(--ink); }
        .button.secondary { border-color: var(--panel-line); background: transparent; color: var(--text); }
        .button.danger { background: var(--red); color: #fff; }
        .button.small { min-height: 31px; padding: 0 10px; font-size: 11px; }
        .flash { margin: 0 0 16px; padding: 11px 14px; border: 1px solid rgba(74,222,128,.28); border-radius: 6px; background: rgba(74,222,128,.1); color: var(--green); font-size: 13px; }
        .error-list { margin: 0 0 16px; padding: 11px 14px 11px 32px; border: 1px solid rgba(251,113,133,.35); border-radius: 6px; background: rgba(251,113,133,.1); color: var(--red); font-size: 13px; }
        .panel { min-width: 0; padding: 20px; border: 1px solid var(--panel-line); border-radius: var(--radius); background: var(--panel); }
        .panel h2 { margin: 0; font-family: 'Raleway', sans-serif; font-size: 15px; font-weight: 700; }
        .panel-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 15px; margin-bottom: 17px; }
        .panel-heading p { margin: 4px 0 0; color: var(--muted); font-size: 11px; }
        .muted { color: var(--muted); }
        .empty-state { padding: 25px 10px; color: var(--muted); font-size: 13px; text-align: center; }
        .risk-badge { display: inline-flex; align-items: center; gap: 6px; padding: 5px 8px; border-radius: 4px; background: rgba(74,222,128,.12); color: var(--green); font-size: 10px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; white-space: nowrap; }
        .risk-badge.high { background: rgba(251,113,133,.14); color: var(--red); }
        .risk-badge.medium { background: rgba(251,191,36,.15); color: var(--orange); }
        .risk-badge.low { background: rgba(74,222,128,.12); color: var(--green); }
        .risk-badge.completed { background: rgba(74,222,128,.12); color: var(--green); }
        .risk-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
        .progress-track { height: 6px; overflow: hidden; border-radius: 8px; background: rgba(255,255,255,.12); }
        .progress-track span { display: block; height: 100%; border-radius: inherit; background: var(--teal); }
        dialog.task-dialog { width: min(540px, calc(100% - 24px)); max-height: min(90vh, 780px); padding: 0; overflow: auto; border: 1px solid #316467; border-radius: 9px; background: #0b292b; color: var(--text); box-shadow: 0 28px 90px rgba(0,0,0,.5); }
        dialog.task-dialog::backdrop { background: rgba(1,15,16,.74); backdrop-filter: blur(3px); }
        .dialog-inner { padding: 22px; }
        .dialog-heading { display: flex; justify-content: space-between; align-items: center; margin-bottom: 17px; }
        .dialog-heading h2 { margin: 0; font-family: 'Raleway', sans-serif; font-size: 19px; }
        .close-dialog { width: 30px; height: 30px; border: 0; border-radius: 5px; background: #163d3f; color: var(--text); font-size: 19px; cursor: pointer; }
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 13px; }
        .field { display: grid; gap: 6px; min-width: 0; }
        .field.full { grid-column: 1 / -1; }
        .field label { color: #bed4cf; font-size: 11px; font-weight: 600; }
        .field input, .field select, .field textarea, .filter-control { width: 100%; min-height: 38px; padding: 8px 10px; border: 1px solid #315557; border-radius: 5px; outline: none; background: #092325; color: var(--text); font-size: 12px; }
        .field textarea { min-height: 78px; resize: vertical; }
        .field input:focus, .field select:focus, .field textarea:focus, .filter-control:focus { border-color: var(--teal); }
        .dialog-actions { display: flex; justify-content: flex-end; gap: 9px; margin-top: 18px; }
        .field-error { color: #ff9b96; font-size: 11px; }
        .theme-toggle { display: inline-flex; align-items: center; justify-content: center; gap: 7px; min-height: 36px; padding: 0 10px; border: 1px solid var(--panel-line); border-radius: 6px; background: transparent; color: var(--text); font-size: 11px; cursor: pointer; white-space: nowrap; }
        .theme-toggle:hover { border-color: var(--teal); }
        :root[data-theme="light"] body { background: var(--page); }
        :root[data-theme="light"] .topbar { background: rgba(244,248,247,.94); }
        :root[data-theme="light"] .nav-label,
        :root[data-theme="light"] .side-nav a,
        :root[data-theme="light"] .logout-button,
        :root[data-theme="light"] .date-label { color: var(--muted); }
        :root[data-theme="light"] .side-nav a:hover,
        :root[data-theme="light"] .side-nav a.active { color: var(--text); }
        :root[data-theme="light"] .logout-button:hover,
        :root[data-theme="light"] .notification-item:hover,
        :root[data-theme="light"] .calendar-nav a:hover { color: var(--teal); }
        :root[data-theme="light"] .global-search,
        :root[data-theme="light"] .notification-panel,
        :root[data-theme="light"] dialog.task-dialog,
        :root[data-theme="light"] .close-dialog,
        :root[data-theme="light"] .field input,
        :root[data-theme="light"] .field select,
        :root[data-theme="light"] .field textarea,
        :root[data-theme="light"] .filter-control { background: var(--panel); color: var(--text); }
        :root[data-theme="light"] .global-search input,
        :root[data-theme="light"] .notification-item { color: var(--text); }
        :root[data-theme="light"] .global-search input::placeholder { color: var(--muted); }
        :root[data-theme="light"] .field label,
        :root[data-theme="light"] .detail-description,
        :root[data-theme="light"] .distribution-row,
        :root[data-theme="light"] .recommendation-list li { color: var(--text); }
        :root[data-theme="light"] .task-card,
        :root[data-theme="light"] .welcome-strip,
        :root[data-theme="light"] .report-metric,
        :root[data-theme="light"] .quick-action,
        :root[data-theme="light"] .copilot-panel,
        :root[data-theme="light"] .risk-metric,
        :root[data-theme="light"] .detail-stat { background: var(--panel); color: var(--text); }
        :root[data-theme="light"] .task-card-title,
        :root[data-theme="light"] .filter-tab,
        :root[data-theme="light"] .task-card-progress,
        :root[data-theme="light"] .quick-action,
        :root[data-theme="light"] .welcome-summary p,
        :root[data-theme="light"] .timeline-copy a,
        :root[data-theme="light"] .course-meta,
        :root[data-theme="light"] .day-count,
        :root[data-theme="light"] .copilot-heading h2,
        :root[data-theme="light"] .copilot-response { color: var(--text); }
        :root[data-theme="light"] .ai-alert { border-color: #D5C8F1; background: #F5F1FF; }
        :root[data-theme="light"] .ai-alert-copy h2 { color: #35245D; }
        :root[data-theme="light"] .ai-alert-copy > p,
        :root[data-theme="light"] .alert-row,
        :root[data-theme="light"] .alert-reason { color: #574778; }
        :root[data-theme="light"] .copilot-prompt { border-color: var(--panel-line); background: #F4F8F7; color: var(--text); }
        :root[data-theme="light"] .copilot-form input,
        :root[data-theme="light"] .feedback-form textarea { border-color: var(--panel-line); background: var(--panel); color: var(--text); }
        :root[data-theme="light"] .calendar-day-number,
        :root[data-theme="light"] .calendar-nav a { color: var(--text); }
        :root[data-theme="light"] .calendar-day.today { background: #E9F8F5; }
        :root[data-theme="light"] .calendar-day { background: #FFFFFF; }
        :root[data-theme="light"] .calendar-holiday { color: #6242AE; }
        :root[data-theme="light"] .calendar-holiday.working { color: #08675F; }
        :root[data-theme="light"] .assistant-answer { color: var(--text); }
        :root[data-theme="light"] .explanation-box { color: #45336F; }
        :root[data-theme="light"] .friend-avatar { background: #DDEBE8; color: var(--text); }
        :root[data-theme="light"] .friend-row,
        :root[data-theme="light"] .request-form input,
        :root[data-theme="light"] .request-form label,
        :root[data-theme="light"] .request-item { color: var(--text); }
        :root[data-theme="light"] .report-metric strong[style*="color"] { color: var(--text) !important; }
        :root[data-theme="light"] .report-metric:nth-child(1) strong { color: var(--red) !important; }
        :root[data-theme="light"] .report-metric:nth-child(2) strong { color: var(--orange) !important; }
        :root[data-theme="light"] .report-metric:nth-child(3) strong { color: var(--green) !important; }
        :root[data-theme="light"] .report-metric:nth-child(4) strong { color: var(--red) !important; }
        :root[data-theme="light"] .calendar-grid { background: #FFFFFF; }
        :root[data-theme="light"] .chart-plot { background: repeating-linear-gradient(to bottom, transparent 0, transparent 30px, #E5EEED 31px); }
        :root[data-theme="light"] .flash { color: var(--green); }
        :root[data-theme="light"] .error-list,
        :root[data-theme="light"] .field-error,
        :root[data-theme="light"] .assistant-error { color: var(--red); }
        .app-shell.is-collapsed { grid-template-columns: 76px minmax(0, 1fr); }
        .is-collapsed .brand { justify-content: center; margin-left: 0; }
        .is-collapsed .brand-name, .is-collapsed .nav-label, .is-collapsed .side-nav .nav-text, .is-collapsed .sidebar-user > div, .is-collapsed .logout-text { display: none; }
        .is-collapsed .sidebar-user > span:last-child { display: none; }
        .is-collapsed .side-nav a, .is-collapsed .logout-button { justify-content: center; padding: 0; }
        .is-collapsed .sidebar-user { justify-content: center; padding-left: 0; padding-right: 0; }
        @media (max-width: 900px) {
            .app-shell { grid-template-columns: 76px minmax(0, 1fr); }
            .brand { justify-content: center; margin-left: 0; }
            .brand-name, .nav-label, .side-nav .nav-text, .sidebar-user > div, .logout-text { display: none; }
            .side-nav a, .logout-button { justify-content: center; padding: 0; }
            .sidebar-user { justify-content: center; padding-left: 0; padding-right: 0; }
            .collapse-button { display: none; }
            .app-shell:not(.is-collapsed) { grid-template-columns: 236px minmax(0, 1fr); }
            .app-shell:not(.is-collapsed) .brand { justify-content: flex-start; margin-left: 4px; }
            .app-shell:not(.is-collapsed) .brand-name,
            .app-shell:not(.is-collapsed) .nav-label,
            .app-shell:not(.is-collapsed) .side-nav .nav-text,
            .app-shell:not(.is-collapsed) .sidebar-user > span:last-child,
            .app-shell:not(.is-collapsed) .logout-text { display: block; }
            .app-shell:not(.is-collapsed) .side-nav a,
            .app-shell:not(.is-collapsed) .logout-button { justify-content: flex-start; padding: 0 12px; }
            .app-shell:not(.is-collapsed) .sidebar-user { justify-content: flex-start; padding-left: 7px; padding-right: 7px; }
            .topbar { padding: 0 22px; }
            .page-wrap { padding: 26px 22px 44px; }
        }
        @media (max-width: 620px) {
            .app-shell, .app-shell.is-collapsed { display: block; }
            .sidebar { position: fixed; z-index: 20; top: 0; bottom: 0; left: 0; width: 250px; height: 100dvh; min-height: 0; transform: translateX(-102%); transition: transform .22s ease; box-shadow: 16px 0 50px rgba(0,0,0,.3); }
            .app-shell.menu-open .sidebar { transform: translateX(0); }
            .brand { justify-content: flex-start; margin-left: 4px; }
            .brand-name, .nav-label, .side-nav .nav-text, .sidebar-user > div, .logout-text { display: block; }
            .side-nav a, .logout-button { justify-content: flex-start; padding: 0 12px; }
            .sidebar-user { justify-content: flex-start; padding-left: 7px; padding-right: 7px; }
            .topbar { min-height: 62px; gap: 9px; padding: 0 14px; }
            .mobile-menu { display: grid; flex: 0 0 34px; width: 34px; height: 34px; }
            .global-search { flex: 1; width: auto; }
            .date-label { display: none; }
            .page-wrap { padding: 23px 14px 34px; }
            .page-heading { align-items: flex-start; flex-direction: column; gap: 13px; }
            .form-grid { grid-template-columns: 1fr; }
            .field.full { grid-column: auto; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="app-shell" id="app-shell">
        <aside class="sidebar" aria-label="Main navigation">
            <a class="brand" href="{{ route('dashboard') }}">
                <svg class="brand-mark-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/><path d="m5 17 4-4 3 2 6-6"/></svg>
                <span class="brand-name">Deadline Risk AI</span>
            </a>
            <a class="sidebar-user" href="{{ route('settings.index', 'school-profile') }}">@if (auth()->user()->profile_image)<img src="{{ asset('storage/'.auth()->user()->profile_image) }}" alt="">@else<span class="avatar">{{ strtoupper(substr(auth()->user()->nickname ?: auth()->user()->name, 0, 1)) }}</span>@endif<span><strong>{{ auth()->user()->nickname ?: auth()->user()->name }}</strong><small>Student profile · Edit</small></span></a>
            <p class="nav-label">Main</p>
            <nav class="side-nav">
                <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><svg class="nav-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3v18h18"/><path d="m7 14 4-4 4 3 6-7"/><path d="M17 6h4v4"/></svg><span class="nav-text">Overview</span></a>
                <a class="{{ request()->routeIs('tasks.*') ? 'active' : '' }}" href="{{ route('tasks.index') }}"><svg class="nav-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 5h11M9 12h11M9 19h11"/><path d="m3 5 1 1 2-2M3 12l1 1 2-2M3 19l1 1 2-2"/></svg><span class="nav-text">Tasks</span></a>
                <a class="{{ request()->routeIs('courses.*') ? 'active' : '' }}" href="{{ route('courses.index') }}"><svg class="nav-icon-svg nav-blue" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v17H6.5A2.5 2.5 0 0 0 4 22z"/><path d="M4 5.5v14A2.5 2.5 0 0 1 6.5 17H20M8 7h8M8 11h8"/></svg><span class="nav-text">Courses</span></a>
                <a class="{{ request()->routeIs('calendar') ? 'active' : '' }}" href="{{ route('calendar') }}"><svg class="nav-icon-svg nav-orange" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18M8 14h.01M12 14h.01M16 14h.01M8 17h.01M12 17h.01"/></svg><span class="nav-text">Calendar</span></a>
                <a class="{{ request()->routeIs('risk-report') ? 'active' : '' }}" href="{{ route('risk-report') }}"><svg class="nav-icon-svg nav-purple" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19V5M4 19h17"/><path d="m7 15 4-4 3 2 6-7"/><path d="M16 6h4v4"/></svg><span class="nav-text">Risk report</span></a>
                <a class="{{ request()->routeIs('friends.*') ? 'active' : '' }}" href="{{ route('friends.index') }}"><svg class="nav-icon-svg nav-green" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="4"/><path d="M20 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg><span class="nav-text">Friends</span></a>
            </nav>
            <div class="sidebar-support">
                <p class="nav-label">Support</p>
                <nav class="side-nav">
                    <a href="{{ route('dashboard') }}#feedback"><svg class="nav-icon-svg nav-blue" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg><span class="nav-text">Feedback</span></a>
                    <a href="{{ route('dashboard') }}#help"><span class="nav-icon" aria-hidden="true">?</span><span class="nav-text">Help</span></a>
                    <a class="{{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}"><span class="nav-icon" aria-hidden="true">⚙</span><span class="nav-text">Settings</span></a>
                </nav>
            </div>
            <div class="sidebar-bottom">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="logout-button" type="submit"><span class="logout-text">Sign out</span></button>
                </form>
            </div>
        </aside>

        <div class="main-area">
            <header class="topbar">
                <button class="mobile-menu" id="mobile-menu" type="button" aria-label="Toggle sidebar navigation" aria-expanded="true" title="Toggle sidebar navigation">☰</button>
                <form class="global-search" method="GET" action="{{ request()->routeIs('friends.*') ? route('friends.index') : route('tasks.index') }}" role="search">
                    <span aria-hidden="true">⌕</span>
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ request()->routeIs('friends.*') ? 'Search a friend...' : 'Search tasks...' }}" aria-label="{{ request()->routeIs('friends.*') ? 'Search a friend' : 'Search tasks' }}">
                </form>
                <span class="topbar-spacer"></span>
                <span class="date-label" id="live-clock" aria-live="off">{{ now()->format('H:i:s') }}</span>
                <button class="theme-toggle" id="theme-toggle" type="button" aria-label="Switch to light mode" aria-pressed="false" title="Switch to light mode">☀</button>
                @isset($notifications)
                    <details class="notification-menu">
                        <summary aria-label="Notifications" title="Notifications">🔔 @if (count($notifications) > 0)<span class="notification-count">{{ count($notifications) }}</span>@endif</summary>
                        <div class="notification-panel">
                            <h2>Notifications <span class="muted">· {{ count($notifications) }}</span></h2>
                            @forelse ($notifications as $notification)
                                <button class="notification-item" type="button" data-notification-id="{{ $notification['id'] }}" data-href="{{ route('tasks.show', $notification['task_id']) }}">
                                    <span class="notice-dot {{ $notification['level'] }}"></span><span>{{ $notification['message'] }}</span>
                                </button>
                            @empty
                                <p class="muted" style="margin:0;font-size:12px;">You’re all caught up.</p>
                            @endforelse
                        </div>
                    </details>
                @endisset
            </header>

            <main class="page-wrap">
                @if (session('success'))<div class="flash" role="status">{{ session('success') }}</div>@endif
                @if (session('error'))<div class="error-list" role="alert">{{ session('error') }}</div>@endif
                @if ($errors->any())
                    <ul class="error-list" role="alert">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                @endif
                <header class="page-heading">
                    <div>
                        @hasSection('eyebrow')<p class="eyebrow">@yield('eyebrow')</p>@endif
                        <h1>@yield('heading', 'Overview')</h1>
                        @hasSection('subtitle')<p>@yield('subtitle')</p>@endif
                    </div>
                    @yield('page-action')
                </header>
                @yield('content')
            </main>
        </div>
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

        const appShell = document.getElementById('app-shell');
        const sidebarToggle = document.getElementById('mobile-menu');
        const sidebarStorageKey = 'deadline-risk-sidebar-collapsed';
        const mobileSidebar = window.matchMedia('(max-width: 620px)');

        if (!mobileSidebar.matches && localStorage.getItem(sidebarStorageKey) === 'true') {
            appShell.classList.add('is-collapsed');
        }

        const updateSidebarToggle = () => {
            const isExpanded = mobileSidebar.matches
                ? appShell.classList.contains('menu-open')
                : !appShell.classList.contains('is-collapsed');

            sidebarToggle.setAttribute('aria-expanded', String(isExpanded));
            sidebarToggle.setAttribute('aria-label', `${isExpanded ? 'Close' : 'Open'} sidebar navigation`);
            sidebarToggle.title = `${isExpanded ? 'Close' : 'Open'} sidebar navigation`;
        };

        sidebarToggle.addEventListener('click', () => {
            if (mobileSidebar.matches) {
                appShell.classList.toggle('menu-open');
            } else {
                appShell.classList.toggle('is-collapsed');
                localStorage.setItem(sidebarStorageKey, String(appShell.classList.contains('is-collapsed')));
            }

            updateSidebarToggle();
        });

        mobileSidebar.addEventListener('change', () => {
            appShell.classList.remove('menu-open');
            updateSidebarToggle();
        });

        updateSidebarToggle();
        const liveClock = document.getElementById('live-clock');
        if (liveClock) {
            window.setInterval(() => {
                liveClock.textContent = new Date().toLocaleTimeString('en-GB');
            }, 1000);
        }
        document.addEventListener('click', (event) => {
            if (mobileSidebar.matches && appShell.classList.contains('menu-open') && !event.target.closest('.sidebar') && !event.target.closest('#mobile-menu')) {
                appShell.classList.remove('menu-open');
                updateSidebarToggle();
            }
        });

        document.querySelectorAll('.notification-item').forEach((item) => {
            const key = `deadline-risk-read-${item.dataset.notificationId}`;
            if (localStorage.getItem(key) === 'true') item.classList.add('is-read');
            item.addEventListener('click', () => {
                localStorage.setItem(key, 'true');
                item.classList.add('is-read');
                updateUnreadCount();
                window.location.href = item.dataset.href;
            });
        });

        function updateUnreadCount() {
            const unreadCount = [...document.querySelectorAll('.notification-item')].filter((item) => {
                return localStorage.getItem(`deadline-risk-read-${item.dataset.notificationId}`) !== 'true';
            }).length;
            const badge = document.querySelector('.notification-count');
            if (badge) {
                badge.textContent = unreadCount;
                badge.hidden = unreadCount === 0;
            }
        }

        updateUnreadCount();
    </script>
    @stack('scripts')
</body>
</html>
