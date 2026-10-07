@php
    $totalTasks = $tasks->count();
    $completedTasks = $tasks->where('status', 'completed')->count();
    $highRiskTasks = $tasks->where('risk_level', 'high')->count();
    $openTasks = $tasks->where('status', '!=', 'completed')->count();
    $deadlineTrend = collect(range(0, 13))->map(function ($offset) use ($tasks) {
        $date = now()->startOfDay()->addDays($offset);
        $count = $tasks->filter(fn ($task) => $task->deadline?->isSameDay($date))->count();

        return [
            'date' => $date->toDateString(),
            'label' => $date->format('d M'),
            'count' => $count,
        ];
    });

    $smoothedTrend = $deadlineTrend->map(function ($point, $index) use ($deadlineTrend) {
        $prev = $index > 0 ? $deadlineTrend[$index - 1]['count'] : $point['count'];
        $next = $index < $deadlineTrend->count() - 1 ? $deadlineTrend[$index + 1]['count'] : $point['count'];

        return [
            'date' => $point['date'],
            'label' => $point['label'],
            'count' => (int) round(($prev + ($point['count'] * 2) + $next) / 4),
        ];
    });

    $trendMax = max(3, $smoothedTrend->max('count'));
    $trendPoints = $smoothedTrend->values()->map(function ($point, $index) use ($smoothedTrend, $trendMax) {
        $x = $smoothedTrend->count() > 1 ? $index * (100 / ($smoothedTrend->count() - 1)) : 50;
        $y = 92 - (($point['count'] / $trendMax) * 70);

        return ['x' => round($x, 2), 'y' => round($y, 2), 'count' => $point['count']];
    })->values();

    $trendPath = '';
    if ($trendPoints->isNotEmpty()) {
        $firstPoint = $trendPoints->first();
        $trendPath = 'M ' . $firstPoint['x'] . ' ' . $firstPoint['y'];

        foreach ($trendPoints->slice(1) as $index => $point) {
            $previous = $trendPoints[$index];
            $controlX = ($previous['x'] + $point['x']) / 2;
            $trendPath .= ' Q ' . $previous['x'] . ' ' . $previous['y'] . ' ' . $controlX . ' ' . (($previous['y'] + $point['y']) / 2);
        }

        $lastPoint = $trendPoints->last();
        $trendPath .= ' T ' . $lastPoint['x'] . ' ' . $lastPoint['y'];
    }

    $barChartMax = max(3, $smoothedTrend->max('count'));
@endphp

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Deadline Risk</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @endif
    <style>
        :root {
            --ink: #e8f4f3;
            --muted: #9bb8b8;
            --panel: #0b3a3d;
            --panel-light: #b8e3e8;
            --accent: #17a1ad;
            --accent-dark: #087a85;
            --page: #062f32;
        }

        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; background: #202020; color: var(--ink); font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        .risk-dashboard { display: grid; grid-template-columns: 248px minmax(0, 1fr); min-height: 100vh; background: var(--page); }
        .sidebar { position: sticky; top: 0; align-self: start; display: flex; flex-direction: column; gap: 42px; height: 100vh; overflow: hidden; padding: 28px 18px; background: linear-gradient(180deg, #16a1ad 0%, #0d8b98 100%); border-radius: 0 14px 14px 0; }
        .brand { width: fit-content; padding: 15px 16px; border-radius: 14px; background: #f1f1ed; color: #152e31; font-family: Georgia, serif; font-size: 19px; box-shadow: 0 8px 18px rgba(2, 35, 37, .18); }
        .side-nav { display: grid; gap: 14px; }
        .side-nav a, .logout-button { display: block; width: 100%; padding: 14px 16px; border: 1px solid transparent; border-radius: 14px; background: rgba(244, 244, 239, .88); color: #18383a; font-size: 14px; text-align: left; text-decoration: none; box-shadow: 0 5px 12px rgba(2, 35, 37, .14); }
        .side-nav a:hover, .logout-button:hover { background: #fff; color: #18383a; }
        .logout-form { margin-top: auto; }
        .logout-button { cursor: pointer; }
        .dashboard-main { min-width: 0; padding: 30px 30px 44px; }
        .topbar { display: flex; align-items: center; gap: 22px; margin-bottom: 34px; }
        .search { width: min(58%, 500px); margin: 0 auto; padding: 8px 20px; border: 0; border-radius: 999px; background: #e8e8e8; color: #203335; text-align: center; outline: none; }
        .clock { color: var(--ink); font-size: 22px; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .eyebrow { margin: 0 0 7px; color: var(--muted); font-size: 12px; letter-spacing: .15em; text-transform: uppercase; }
        .dashboard-title { margin: 0 0 25px; font-size: clamp(24px, 3vw, 38px); font-weight: 500; }
        .metric-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 22px; margin-bottom: 34px; }
        .metric { min-height: 105px; padding: 18px; background: var(--panel-light); color: #173437; box-shadow: 0 10px 22px rgba(2, 35, 37, .2); }
        .metric-label { display: block; font-size: 13px; }
        .metric-value { display: block; margin-top: 9px; font-size: 31px; font-weight: 600; }
        .content-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(260px, .78fr); gap: 22px; margin-bottom: 34px; }
        .panel { min-width: 0; padding: 24px; background: var(--panel-light); color: #173437; box-shadow: 0 10px 24px rgba(2, 35, 37, .2); }
        .panel h2 { margin: 0 0 22px; font-size: 18px; font-weight: 600; }
        .risk-bars { display: grid; gap: 15px; }
        .risk-row { display: grid; grid-template-columns: 76px 1fr 36px; align-items: center; gap: 10px; font-size: 13px; }
        .bar { height: 12px; overflow: hidden; background: rgba(23, 52, 55, .13); }
        .bar span { display: block; height: 100%; background: var(--accent-dark); }
        .bar .medium { background: #d49e38; }
        .bar .high { background: #b94d4d; }
        .bar-chart { width: 100%; height: 170px; overflow: visible; }
        .bar-chart-grid { stroke: rgba(23, 52, 55, .18); stroke-width: 1; }
        .bar-chart-bar { fill: rgba(8, 122, 133, .85); }
        .bar-chart-bar.medium { fill: rgba(212, 158, 56, .9); }
        .bar-chart-bar.high { fill: rgba(185, 77, 77, .9); }
        .line-chart-labels { display: grid; grid-template-columns: repeat(7, 1fr); color: #49676a; font-size: 11px; text-align: center; }
        .task-panel { padding-bottom: 10px; }
        .task-list { display: grid; gap: 0; }
        .task-row { display: grid; grid-template-columns: minmax(0, 1fr) 120px 80px; align-items: center; gap: 16px; padding: 15px 0; border-top: 1px solid rgba(23, 52, 55, .18); font-size: 14px; }
        .task-title { overflow: hidden; font-weight: 600; text-overflow: ellipsis; white-space: nowrap; }
        .task-meta { color: #49676a; font-size: 12px; }
        .status { font-size: 12px; text-transform: capitalize; }
        .empty-state { color: #49676a; }
        @media (max-width: 850px) { .risk-dashboard { grid-template-columns: 1fr; } .sidebar { position: relative; top: auto; align-self: auto; height: auto; overflow: visible; gap: 20px; border-radius: 0; } .side-nav { grid-template-columns: repeat(3, 1fr); } .logout-form { margin-top: 0; } .metric-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 600px) { .dashboard-main { padding: 22px 16px 32px; } .topbar { align-items: stretch; flex-direction: column; } .search { width: 100%; } .content-grid { grid-template-columns: 1fr; } .side-nav { grid-template-columns: 1fr; } .task-row { grid-template-columns: minmax(0, 1fr) 76px; } .task-row .status { display: none; } }
        .risk-dashboard { grid-template-columns: 210px minmax(0, 1fr); gap: 0; }
        .sidebar { gap: 38px; padding: 24px 16px; }
        .brand { font-size: 17px; }
        .side-nav { gap: 16px; }
        .side-nav a, .logout-button { padding: 11px 13px; font-size: 13px; }
        .dashboard-main { padding: 26px 30px 36px; }
        .topbar { margin-bottom: 22px; }
        .search { margin-left: 0; margin-right: auto; width: min(48%, 410px); }
        .dashboard-title { margin-bottom: 20px; font-size: clamp(26px, 3vw, 36px); }
        .metric-grid { gap: 12px; margin-bottom: 18px; }
        .metric { min-height: 84px; padding: 14px; }
        .metric-value { margin-top: 5px; font-size: 24px; }
        .content-grid { grid-template-columns: minmax(0, 1.15fr) minmax(280px, .85fr); gap: 12px; margin-bottom: 18px; }
        .panel { padding: 18px; }
        .panel h2 { margin-bottom: 14px; font-size: 16px; }
        .task-panel { padding-bottom: 6px; }
        .task-row { padding: 11px 0; }
        .feature-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
        .feature-card { padding: 16px; background: rgba(244, 244, 239, .62); color: #173437; box-shadow: 0 5px 12px rgba(2, 35, 37, .12); }
        .feature-card h3 { margin: 0 0 8px; font-size: 15px; }
        .feature-card p { margin: 0; color: #49676a; font-size: 13px; line-height: 1.5; }
        .settings-list { display: grid; gap: 12px; max-width: 500px; }
        .settings-list label { display: flex; align-items: center; gap: 10px; color: #173437; font-size: 14px; }
        .feedback-form { display: grid; gap: 12px; max-width: 620px; }
        .feedback-form textarea { min-height: 100px; padding: 12px; border: 1px solid rgba(23, 52, 55, .2); background: rgba(244, 244, 239, .72); color: #173437; resize: vertical; }
        .feedback-form button { width: fit-content; padding: 10px 16px; border: 0; border-radius: 14px; background: var(--accent-dark); color: var(--ink); cursor: pointer; }
        .feedback-message { display: none; color: #087a85; font-size: 13px; }
        @media (max-width: 600px) { .feature-grid { grid-template-columns: 1fr; } }
        @media (max-width: 850px) { .risk-dashboard { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="risk-dashboard">
        <aside class="sidebar">
            <div class="brand">Deadline Risk</div>
            <nav class="side-nav" aria-label="Dashboard navigation">
                <a href="#overview">Overview</a>
                <a href="#tasks">Tasks</a>
                <a href="#risk">Risk report</a>
                <a href="#settings">Settings</a>
                <a href="#help">Help</a>
                <a href="#feedback">Feedback</a>
                <a href="#courses">Courses</a>
            </nav>
            <form class="logout-form" method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="logout-button" type="submit">Sign out</button>
            </form>
        </aside>

        <main class="dashboard-main">
            <header class="topbar">
                <time class="clock" id="realtime-clock" datetime="{{ now()->toIso8601String() }}">{{ now()->format('H:i:s') }}</time>
                <input class="search" type="search" placeholder="Search tasks" aria-label="Search tasks">
                <span class="task-meta">{{ now()->format('d M Y') }}</span>
            </header>

            <div id="overview">
                <p class="eyebrow">Deadline Risk / Overview</p>
                <h1 class="dashboard-title">Your Analytical Board</h1>
            </div>

            <section class="panel board-summary" aria-label="Task performance summary">
                <p class="eyebrow">Smart deadline distribution</p>
                <h2>Task performance</h2>
                <p class="task-meta">Risk-aware task metrics across deadlines and completion.</p>
                <div class="metric-grid">
                    <div class="metric"><span class="metric-label">Total tasks</span><strong class="metric-value">{{ $totalTasks }}</strong></div>
                    <div class="metric"><span class="metric-label">Open tasks</span><strong class="metric-value">{{ $openTasks }}</strong></div>
                    <div class="metric"><span class="metric-label">Completed</span><strong class="metric-value">{{ $completedTasks }}</strong></div>
                    <div class="metric"><span class="metric-label">High risk</span><strong class="metric-value">{{ $highRiskTasks }}</strong></div>
                </div>
            </section>

            <section class="content-grid" id="risk">
                <div class="panel">
                    <h2>Deadline activity</h2>
                    <svg class="bar-chart" viewBox="0 0 100 100" preserveAspectRatio="none" role="img" aria-label="Tasks due over the next fourteen days">
                        <line class="bar-chart-grid" x1="0" y1="22" x2="100" y2="22"></line>
                        <line class="bar-chart-grid" x1="0" y1="57" x2="100" y2="57"></line>
                        <line class="bar-chart-grid" x1="0" y1="92" x2="100" y2="92"></line>
                        @foreach ($smoothedTrend as $index => $point)
                            @php
                                $x = 3 + ($index * 6.1);
                                $barWidth = 3.8;
                                $height = ($point['count'] / $barChartMax) * 58;
                                $y = 92 - $height;
                                $riskClass = $point['count'] >= max(2, (int) ceil($barChartMax * 0.7)) ? 'high' : ($point['count'] >= max(1, (int) ceil($barChartMax * 0.4)) ? 'medium' : '');
                            @endphp
                            <title>{{ $point['date'] }}: {{ $point['count'] }} task(s)</title>
                            <rect class="bar-chart-bar {{ $riskClass }}" x="{{ $x }}" y="{{ $y }}" width="{{ $barWidth }}" height="{{ $height }}" rx="1.5"></rect>
                        @endforeach
                    </svg>
                    <div class="line-chart-labels">
                        @foreach ($smoothedTrend as $point)
                            <span>{{ $point['label'] }}</span>
                        @endforeach
                    </div>
                </div>
                <div class="panel">
                    <h2>Risk overview</h2>
                    <p class="eyebrow">Current exposure</p>
                    <strong class="metric-value">{{ $highRiskTasks }} high-risk {{ Str::plural('task', $highRiskTasks) }}</strong>
                    <p class="task-meta">Review deadlines before the next planning cycle.</p>
                </div>
            </section>

            <section class="panel task-panel" id="tasks">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:12px;">
                    <h2 style="margin:0;">Top opportunities</h2>
                    <a href="{{ route('tasks.create') }}" style="display:inline-block; padding:10px 16px; border-radius:12px; background:#0d8b98; color:#fff; text-decoration:none; font-weight:600;">Add task</a>
                </div>
                <div class="task-list">
                    @forelse ($tasks->take(6) as $task)
                        <div class="task-row">
                            <span class="task-title">{{ $task->title }}</span>
                            <span class="task-meta">{{ $task->deadline?->format('d M Y') ?? 'No deadline' }}</span>
                            <span class="status">{{ str_replace('_', ' ', $task->status) }}</span>
                        </div>
                    @empty
                        <p class="empty-state">No tasks yet. Add your first deadline to start tracking risk.</p>
                    @endforelse
                </div>
            </section>

            <section class="panel" id="courses">
                <p class="eyebrow">Learning center</p>
                <h2>Courses</h2>
                <div class="feature-grid">
                    <article class="feature-card"><h3>Deadline planning</h3><p>Build realistic delivery dates and spot schedule pressure early.</p></article>
                    <article class="feature-card"><h3>Risk triage</h3><p>Learn how to prioritize high-risk work before it becomes overdue.</p></article>
                    <article class="feature-card"><h3>Team handoffs</h3><p>Create clearer ownership and smoother task transitions.</p></article>
                </div>
            </section>

            <section class="panel" id="settings">
                <p class="eyebrow">Workspace preferences</p>
                <h2>Settings</h2>
                <div class="settings-list">
                    <label><input type="checkbox" checked> Show deadline reminders</label>
                    <label><input type="checkbox" checked> Highlight high-risk tasks</label>
                    <label><input type="checkbox"> Compact task rows</label>
                </div>
            </section>

            <section class="panel" id="help">
                <p class="eyebrow">Support</p>
                <h2>Help</h2>
                <div class="feature-grid">
                    <article class="feature-card"><h3>How risk is calculated</h3><p>Risk levels combine deadline proximity, progress, and task status.</p></article>
                    <article class="feature-card"><h3>Need a new task?</h3><p>Use the Tasks area to review deadlines and keep work moving.</p></article>
                    <article class="feature-card"><h3>Still stuck?</h3><p>Send feedback and describe what you need improved.</p></article>
                </div>
            </section>

            <section class="panel" id="feedback">
                <p class="eyebrow">Make the board better</p>
                <h2>Feedback</h2>
                <form class="feedback-form" id="feedback-form">
                    <textarea name="feedback" placeholder="Tell us what would make Deadline Risk more useful..." required></textarea>
                    <button type="submit">Send feedback</button>
                    <span class="feedback-message" id="feedback-message">Thanks, your feedback has been noted.</span>
                </form>
            </section>
        </main>
    </div>
    <script>
        const realtimeClock = document.getElementById('realtime-clock');

        function updateRealtimeClock() {
            realtimeClock.textContent = new Date().toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false,
            });
        }

        updateRealtimeClock();
        setInterval(updateRealtimeClock, 1000);

        const taskSearch = document.querySelector('.search');
        const taskRows = document.querySelectorAll('.task-row');

        taskSearch.addEventListener('input', (event) => {
            const query = event.target.value.toLowerCase().trim();

            taskRows.forEach((row) => {
                row.hidden = query !== '' && !row.textContent.toLowerCase().includes(query);
            });
        });

        const feedbackForm = document.getElementById('feedback-form');
        const feedbackMessage = document.getElementById('feedback-message');

        feedbackForm.addEventListener('submit', (event) => {
            event.preventDefault();
            feedbackForm.reset();
            feedbackMessage.style.display = 'block';
        });
    </script>
</body>
</html>
