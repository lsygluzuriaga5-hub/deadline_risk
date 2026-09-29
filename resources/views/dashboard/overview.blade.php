@extends('layouts.risk')

@section('title', 'Overview')
@section('eyebrow', 'Your study plan · '.now()->format('l, M j'))
@section('heading', (now()->hour < 12 ? 'Good morning' : (now()->hour < 18 ? 'Good afternoon' : 'Good evening')).', '.auth()->user()->name)
@section('subtitle', 'Your deadlines, risk signals, and next best steps.')
@section('page-action')
    <button class="button" type="button" data-open-task>＋ Add task</button>
@endsection

@section('content')
    <style>
        .welcome-strip { display:flex; align-items:center; justify-content:space-between; gap:16px; margin-bottom:15px; padding:13px 16px; border:1px solid var(--panel-line); border-radius:7px; background:#0d3031; }
        .welcome-summary { display:flex; align-items:center; gap:11px; min-width:0; }
        .welcome-spark { display:grid; width:32px; height:32px; flex:0 0 32px; place-items:center; border-radius:8px; background:rgba(167,139,250,.15); color:var(--purple); font-size:17px; }
        .welcome-summary p { margin:0; color:#d5e5e0; font-size:12px; line-height:1.45; }
        .welcome-summary small { display:block; margin-bottom:3px; color:var(--purple); font-size:9px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; }
        .due-today-stat { min-width:118px; padding-left:16px; border-left:1px solid var(--panel-line); text-align:right; }
        .due-today-stat strong { display:block; color:var(--orange); font-family:'Raleway',sans-serif; font-size:23px; line-height:1; }
        .due-today-stat span { display:block; margin-top:4px; color:var(--muted); font-size:10px; }
        .work-grid { display:grid; grid-template-columns:minmax(0,1.35fr) minmax(280px,.75fr); gap:14px; align-items:stretch; }
        .priority-panel { border-top:2px solid var(--teal); }
        .task-stack { display:grid; }
        .priority-task { display:grid; grid-template-columns:25px minmax(0,1fr) auto; align-items:center; gap:11px; padding:13px 0; border-top:1px solid var(--panel-line); }
        .priority-task:first-child { border-top:0; }
        .complete-button { display:grid; width:23px; height:23px; place-items:center; padding:0; border:1px solid #65847e; border-radius:50%; background:transparent; color:transparent; cursor:pointer; }
        .complete-button:hover { border-color:var(--green); background:rgba(74,222,128,.15); color:var(--green); }
        .priority-task-copy { min-width:0; }
        .priority-task-title { display:block; overflow:hidden; color:var(--text); font-size:13px; font-weight:700; text-decoration:none; text-overflow:ellipsis; white-space:nowrap; }
        .priority-task-title:hover { color:var(--teal); }
        .priority-task-meta { display:flex; flex-wrap:wrap; gap:4px 11px; margin-top:5px; color:var(--muted); font-size:10px; }
        .priority-task-side { display:grid; justify-items:end; gap:6px; }
        .status-badge { display:inline-flex; align-items:center; gap:5px; padding:5px 7px; border-radius:4px; background:rgba(96,165,250,.13); color:var(--blue); font-size:9px; font-weight:700; text-transform:uppercase; white-space:nowrap; }
        .status-badge.high, .status-badge.overdue { background:rgba(251,113,133,.15); color:var(--red); }
        .status-badge.medium, .status-badge.today { background:rgba(251,191,36,.15); color:var(--orange); }
        .status-badge.low { background:rgba(74,222,128,.13); color:var(--green); }
        .status-badge.completed { background:rgba(74,222,128,.13); color:var(--green); }
        .task-progress { display:flex; align-items:center; gap:6px; color:#bfd4cf; font-size:9px; }
        .task-progress-track { width:55px; height:4px; overflow:hidden; border-radius:4px; background:rgba(255,255,255,.12); }
        .task-progress-track span { display:block; height:100%; border-radius:inherit; background:var(--teal); }
        .timeline-panel { border-top:2px solid var(--orange); }
        .timeline { position:relative; display:grid; }
        .timeline::before { position:absolute; top:10px; bottom:12px; left:50px; width:1px; background:rgba(251,191,36,.25); content:''; }
        .timeline-item { position:relative; display:grid; grid-template-columns:40px 12px minmax(0,1fr); gap:9px; align-items:start; padding:10px 0; }
        .timeline-date { color:var(--orange); font-size:10px; font-weight:700; text-align:right; }
        .timeline-dot { z-index:1; width:9px; height:9px; margin-top:2px; border:2px solid #123638; border-radius:50%; background:var(--orange); }
        .timeline-dot.high { background:var(--red); }
        .timeline-dot.low { background:var(--blue); }
        .timeline-copy { min-width:0; }
        .timeline-copy a { display:block; overflow:hidden; color:#e5f0ec; font-size:11px; font-weight:600; text-decoration:none; text-overflow:ellipsis; white-space:nowrap; }
        .timeline-copy small { display:block; margin-top:3px; color:var(--muted); font-size:9px; }
        .ai-alert { display:grid; grid-template-columns:36px minmax(0,1fr) auto; gap:13px; align-items:start; margin-top:14px; padding:17px; border:1px solid rgba(167,139,250,.28); border-left:3px solid var(--purple); border-radius:7px; background:linear-gradient(110deg,rgba(74,54,104,.32),rgba(16,54,56,.96) 60%); }
        .ai-alert-icon { display:grid; width:34px; height:34px; place-items:center; border-radius:8px; background:rgba(167,139,250,.17); color:var(--purple); font-size:18px; }
        .ai-alert-copy h2 { margin:0; color:#f1eafe; font-family:'Raleway',sans-serif; font-size:14px; }
        .ai-alert-copy>p { margin:5px 0 11px; color:#cbbddb; font-size:11px; line-height:1.5; }
        .alert-list { display:grid; gap:6px; }
        .alert-row { display:flex; align-items:center; justify-content:space-between; gap:10px; color:#e0d6ee; font-size:10px; }
        .alert-reasons { display:flex; flex-wrap:wrap; gap:4px; }
        .alert-reason { padding:4px 6px; border:1px solid rgba(201,177,242,.16); border-radius:4px; color:#c7b5df; font-size:9px; }
        .copilot-panel { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:16px; align-items:end; margin-top:14px; border:1px solid rgba(67,214,197,.28); background:linear-gradient(115deg,#103b3b,#102f34 75%); }
        .copilot-heading { display:flex; align-items:center; gap:9px; margin-bottom:8px; }
        .copilot-heading h2 { margin:0; color:#e9faf5; font-family:'Raleway',sans-serif; font-size:15px; }
        .copilot-mark { display:grid; width:27px; height:27px; place-items:center; border-radius:7px; background:rgba(167,139,250,.18); color:var(--purple); }
        .copilot-response { min-height:35px; margin:0; color:#c1d9d3; font-size:11px; line-height:1.55; }
        .copilot-prompts { display:flex; flex-wrap:wrap; gap:6px; margin-top:10px; }
        .copilot-prompt { padding:6px 8px; border:1px solid rgba(205,235,229,.14); border-radius:5px; background:rgba(4,28,30,.28); color:#b8d3cc; font-size:9px; cursor:pointer; }
        .copilot-prompt:hover { border-color:var(--teal); color:white; }
        .copilot-form { display:flex; width:min(310px,32vw); gap:6px; }
        .copilot-form input { width:100%; min-width:0; min-height:37px; padding:0 10px; border:1px solid #315557; border-radius:5px; outline:0; background:#092325; color:var(--text); font-size:11px; }
        .copilot-form input:focus { border-color:var(--teal); }
        .copilot-send { width:38px; height:37px; flex:0 0 38px; border:0; border-radius:5px; background:var(--teal); color:#082c2b; cursor:pointer; }
        .secondary-grid { display:grid; grid-template-columns:minmax(0,1.2fr) minmax(250px,.8fr); gap:14px; margin-top:14px; }
        .risk-panel { border-top:2px solid var(--purple); }
        .risk-metrics { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:8px; margin-bottom:16px; }
        .risk-metric { padding:10px; border-radius:5px; background:rgba(255,255,255,.035); }
        .risk-metric span { display:block; color:var(--muted); font-size:9px; }
        .risk-metric strong { display:block; margin-top:4px; font-family:'Raleway',sans-serif; font-size:20px; }
        .risk-metric.high strong,.risk-metric.overdue strong { color:var(--red); }
        .risk-metric.medium strong { color:var(--orange); }
        .risk-metric.low strong { color:var(--green); }
        .activity-chart { display:grid; grid-template-columns:repeat(7,minmax(0,1fr)); gap:6px; align-items:end; min-height:130px; }
        .activity-day { position:relative; display:grid; grid-template-rows:98px auto; gap:7px; min-width:0; text-align:center; }
        .chart-plot { display:flex; align-items:flex-end; justify-content:center; height:98px; border-bottom:1px solid rgba(213,236,230,.12); background:repeating-linear-gradient(to bottom,transparent 0,transparent 30px,rgba(213,236,230,.06) 31px); }
        .chart-bar { display:flex; flex-direction:column-reverse; width:min(24px,70%); min-height:3px; overflow:hidden; border-radius:4px 4px 0 0; }
        .chart-segment { min-height:0; }
        .chart-segment.high { background:var(--red); }
        .chart-segment.medium { background:var(--orange); }
        .chart-segment.low { background:var(--green); }
        .chart-segment.completed { background:var(--green); }
        .day-count { position:absolute; top:0; left:50%; color:#bed4cf; font-size:9px; transform:translateX(-50%); }
        .day-label { color:#94b3ae; font-size:9px; white-space:nowrap; }
        .risk-legend { display:flex; flex-wrap:wrap; gap:10px; margin-top:12px; color:var(--muted); font-size:9px; }
        .legend-item { display:inline-flex; align-items:center; gap:5px; }
        .legend-dot { width:7px; height:7px; border-radius:2px; background:var(--red); }
        .legend-dot.medium { background:var(--orange); }
        .legend-dot.low { background:var(--blue); }
        .legend-dot.completed { background:var(--green); }
        .course-panel { border-top:2px solid var(--blue); }
        .course-list { display:grid; gap:14px; }
        .course-row { display:grid; gap:6px; }
        .course-meta { display:flex; justify-content:space-between; gap:10px; color:#d7e6e1; font-size:10px; }
        .course-meta small { color:var(--muted); font-size:9px; }
        .course-track { height:6px; overflow:hidden; border-radius:5px; background:rgba(255,255,255,.1); }
        .course-track span { display:block; height:100%; border-radius:inherit; background:var(--blue); }
        .quick-actions { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:9px; margin-top:14px; }
        .quick-action { display:flex; align-items:center; gap:9px; min-height:50px; padding:10px; border:1px solid var(--panel-line); border-radius:6px; background:#0e3032; color:#dcebe6; font-size:10px; font-weight:600; text-decoration:none; transition:border-color .18s,transform .18s; }
        .quick-action:hover { transform:translateY(-1px); border-color:rgba(67,214,197,.45); color:var(--text); }
        .quick-action-icon { color:var(--teal); font-size:15px; }
        .quick-action.course .quick-action-icon { color:var(--blue); }
        .quick-action.calendar .quick-action-icon { color:var(--orange); }
        .quick-action.report .quick-action-icon { color:var(--purple); }
        .empty-task { padding:22px 8px; color:var(--muted); font-size:11px; line-height:1.6; }
        .support-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; margin-top:24px; }
        .support-grid .panel { padding:15px; }
        .support-grid h2 { font-size:13px; }
        .support-grid p { color:var(--muted); font-size:10px; line-height:1.5; }
        .support-setting { display:flex; align-items:center; gap:8px; margin-top:10px; color:#d2e1dd; font-size:10px; }
        .support-setting input { accent-color:var(--teal); }
        .priority-panel.compact .priority-task { padding:7px 0; }
        .muted-risk .status-badge { filter:saturate(.45); }
        .feedback-form { display:grid; gap:8px; }
        .feedback-form textarea { width:100%; min-height:55px; padding:8px; border:1px solid #315557; border-radius:5px; background:#092325; color:var(--text); font-size:10px; resize:vertical; }
        .feedback-status { min-height:14px; color:var(--green); font-size:9px; }
        @media(max-width:940px) { .work-grid,.secondary-grid { grid-template-columns:1fr; } .copilot-panel { grid-template-columns:1fr; } .copilot-form { width:100%; } }
        @media(max-width:650px) { .welcome-strip { align-items:flex-start; flex-direction:column; } .due-today-stat { display:flex; align-items:center; gap:8px; min-width:0; padding:0; border:0; text-align:left; } .due-today-stat strong { font-size:18px; } .due-today-stat span { margin:0; } .priority-task { grid-template-columns:22px minmax(0,1fr); } .priority-task-side { grid-column:2; display:flex; justify-content:space-between; } .ai-alert { grid-template-columns:30px minmax(0,1fr); padding:13px; } .ai-alert-icon { width:29px; height:29px; } .ai-alert>.button { grid-column:2; justify-self:start; } .risk-metrics,.quick-actions,.support-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .alert-row { align-items:flex-start; flex-direction:column; } }
    </style>

    <section class="welcome-strip" aria-label="Today's summary">
        <div class="welcome-summary"><span class="welcome-spark" aria-hidden="true">✦</span><p><small>AI study brief · {{ now()->format('l, M j') }}</small>{{ $productivitySummary }}</p></div>
        <div class="due-today-stat"><strong>{{ $dueTodayCount }}</strong><span>{{ Str::plural('task', $dueTodayCount) }} due today</span></div>
    </section>

    <section class="work-grid" aria-label="Priority tasks and upcoming deadlines">
        <article class="panel priority-panel" aria-labelledby="today-tasks-title">
            <div class="panel-heading"><div><p class="eyebrow">Do this next</p><h2 id="today-tasks-title">Today’s tasks <span class="muted">· {{ $todayTasks->count() }}</span></h2><p>Due today and overdue work comes first.</p></div><a class="button secondary small" href="{{ route('tasks.index') }}">View all tasks →</a></div>
            @if ($todayTasks->isNotEmpty())
                <div class="task-stack">
                    @foreach ($todayTasks->take(6) as $task)
                        @php $assessment = $summary['assessments'][$task->id]; $badgeClass = $assessment['overdue'] ? 'overdue' : ($assessment['days_remaining'] === 0 ? 'today' : $assessment['level']); $badgeText = $assessment['overdue'] ? 'Overdue' : ($assessment['days_remaining'] === 0 ? 'Due today' : $assessment['level'].' risk'); @endphp
                        <article class="priority-task">
                            <form class="inline-form" method="POST" action="{{ route('tasks.complete', $task) }}">@csrf<button class="complete-button" type="submit" aria-label="Mark {{ $task->title }} complete">✓</button></form>
                            <div class="priority-task-copy"><a class="priority-task-title" href="{{ route('tasks.show', $task) }}">{{ $task->title }}</a><div class="priority-task-meta"><span>{{ $task->course ?: 'Uncategorized' }}</span><span>{{ ucfirst($task->priority) }} priority</span><span>{{ $task->estimated_hours ?? '—' }}h estimate</span><span>{{ $task->progress }}% complete</span></div></div>
                            <div class="priority-task-side"><span class="status-badge {{ $badgeClass }}">{{ $badgeText }}</span><span class="task-progress"><span class="task-progress-track"><span style="width:{{ $task->progress }}%"></span></span>{{ $task->progress }}%</span></div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="empty-task">Nothing is due today. Use the time to make progress on an upcoming assignment.</div>
            @endif
            @if ($upcomingTasks->isNotEmpty())<div style="padding-top:11px;border-top:1px solid var(--panel-line);"><a class="text-button" href="{{ route('tasks.index') }}">{{ $upcomingTasks->count() }} upcoming {{ Str::plural('assignment', $upcomingTasks->count()) }} · View all →</a></div>@endif
        </article>

        <article class="panel timeline-panel" aria-labelledby="upcoming-title">
            <div class="panel-heading"><div><p class="eyebrow" style="color:var(--orange);">Next 14 days</p><h2 id="upcoming-title">Upcoming deadlines</h2></div><a class="text-button" href="{{ route('calendar') }}">Calendar →</a></div>
            @if ($upcomingTasks->isNotEmpty())
                <div class="timeline">@foreach ($upcomingTasks->take(5) as $task)@php $assessment = $summary['assessments'][$task->id]; @endphp<div class="timeline-item"><span class="timeline-date">{{ $task->deadline?->format('M j') }}</span><i class="timeline-dot {{ $assessment['level'] }}"></i><div class="timeline-copy"><a href="{{ route('tasks.show', $task) }}">{{ $task->title }}</a><small>{{ $task->course ?: 'Uncategorized' }} · {{ $task->deadline?->diffForHumans() }}</small></div></div>@endforeach</div>
            @else
                <div class="empty-task">No upcoming deadlines in the next two weeks.</div>
            @endif
        </article>
    </section>

    <section class="ai-alert" aria-labelledby="risk-alert-title">
        <span class="ai-alert-icon" aria-hidden="true">✦</span>
        <div class="ai-alert-copy"><h2 id="risk-alert-title">AI Risk Alert</h2><p>AI detected {{ $attentionAlerts->count() }} {{ Str::plural('assignment', $attentionAlerts->count()) }} that may need attention.</p>
            @if ($attentionAlerts->isNotEmpty())<div class="alert-list">@foreach ($attentionAlerts->take(3) as $alert)<div class="alert-row"><span>{{ $alert['task']->title }}</span><span class="alert-reasons">@foreach ($alert['reasons'] as $reason)<span class="alert-reason">{{ $reason }}</span>@endforeach</span></div>@endforeach</div>@else<span class="alert-reason">No high-pressure patterns right now</span>@endif
        </div>
        @if ($focusTasks->isNotEmpty())<a class="button secondary small" href="{{ route('tasks.show', $focusTasks->first()) }}">Review priority →</a>@endif
    </section>

    <section class="panel copilot-panel" aria-labelledby="copilot-title">
        <div><div class="copilot-heading"><span class="copilot-mark" aria-hidden="true">✦</span><h2 id="copilot-title">AI Copilot</h2><span class="status-badge low">Task-aware</span></div><p class="copilot-response" id="copilot-response" role="status">Ask about your workload. I’ll use your deadlines, progress, and risk levels to suggest a next step.</p><div class="copilot-prompts" aria-label="Suggested Copilot questions"><button class="copilot-prompt" type="button" data-copilot-prompt="What should I work on next?">What next?</button><button class="copilot-prompt" type="button" data-copilot-prompt="Why is a task high risk?">Explain my risk</button><button class="copilot-prompt" type="button" data-copilot-prompt="How can I catch up this week?">Help me catch up</button></div></div>
        <form class="copilot-form" id="copilot-form"><input id="copilot-question" type="text" placeholder="Ask your study copilot..." aria-label="Ask your study copilot"><button class="copilot-send" type="submit" aria-label="Ask Copilot">→</button></form>
    </section>

    <div class="secondary-grid">
        <section class="panel risk-panel" aria-labelledby="risk-overview-title">
            <div class="panel-heading"><div><p class="eyebrow" style="color:var(--purple);">Analytics · secondary</p><h2 id="risk-overview-title">Deadline risk</h2><p>Understand the pressure without decoding a chart.</p></div><a class="text-button" href="{{ route('risk-report') }}">Risk report →</a></div>
            <div class="risk-metrics"><div class="risk-metric low"><span>Low risk</span><strong>{{ $summary['counts']['low'] }}</strong></div><div class="risk-metric medium"><span>Medium risk</span><strong>{{ $summary['counts']['medium'] }}</strong></div><div class="risk-metric high"><span>High risk</span><strong>{{ $summary['counts']['high'] }}</strong></div><div class="risk-metric overdue"><span>Overdue</span><strong>{{ $summary['counts']['overdue'] }}</strong></div></div>
            <div class="panel-heading" style="margin-bottom:5px;"><div><h2 style="font-size:12px;">Deadline activity</h2><p>Risk by day · next 7 days</p></div><span class="muted" style="font-size:10px;">{{ $activity->first()['date']->format('M j') }} – {{ $activity->last()['date']->format('M j') }}</span></div>
            <div class="activity-chart" role="img" aria-label="Deadline distribution for the next seven days">@foreach ($activity as $day)@php $barHeight = $day['total'] === 0 ? 3 : max(9, (int) round(($day['total'] / $chartMax) * 84)); @endphp<div class="activity-day" title="{{ $day['date']->format('F j') }}: {{ $day['total'] }} assignment(s)"><span class="day-count">{{ $day['total'] ?: '' }}</span><div class="chart-plot"><div class="chart-bar" style="height:{{ $barHeight }}px;">@foreach (['completed', 'low', 'medium', 'high'] as $level)@if ($day['levels'][$level] > 0)<span class="chart-segment {{ $level }}" style="flex:{{ $day['levels'][$level] }}"></span>@endif @endforeach</div></div><span class="day-label">{{ $day['label'] }}</span></div>@endforeach</div>
            <div class="risk-legend"><span class="legend-item"><i class="legend-dot"></i>High</span><span class="legend-item"><i class="legend-dot medium"></i>Medium</span><span class="legend-item"><i class="legend-dot low"></i>Low</span><span class="legend-item"><i class="legend-dot completed"></i>Completed</span></div>
        </section>

        <section class="panel course-panel" id="course-progress" aria-labelledby="course-progress-title">
            <div class="panel-heading"><div><p class="eyebrow" style="color:var(--blue);">Academic overview</p><h2 id="course-progress-title">Course progress</h2><p>Completion across task categories.</p></div></div>
            @if ($courseProgress->isNotEmpty())<div class="course-list">@foreach ($courseProgress->take(5) as $course)<div class="course-row"><div class="course-meta"><span>{{ $course['name'] }}</span><small>{{ $course['completed'] }}/{{ $course['count'] }} complete</small></div>@if ($course['professor'] || $course['grade'] !== null)<div class="course-meta"><small>{{ $course['professor'] ? 'Prof. '.$course['professor'] : 'Professor not set' }}</small><small>{{ $course['grade'] !== null ? 'Current grade · '.number_format($course['grade'], 1).'%' : 'No grades yet' }}</small></div>@endif<div class="course-track"><span style="width:{{ $course['progress'] }}%"></span></div><div class="course-meta"><small>{{ $course['count'] }} {{ Str::plural('assignment', $course['count']) }}</small><small>{{ $course['progress'] }}% task completion</small></div></div>@endforeach</div>@else<div class="empty-task">Add a course name when creating a task to track progress here.</div>@endif
            <a class="text-button" href="{{ route('courses.index') }}">Manage courses, professors &amp; grades →</a>
        </section>
    </div>

    <section class="quick-actions" aria-label="Quick actions"><button class="quick-action" type="button" data-open-task><span class="quick-action-icon" aria-hidden="true">＋</span>Add task</button><a class="quick-action course" href="{{ route('courses.index') }}#new-course"><span class="quick-action-icon" aria-hidden="true">▤</span>Add course</a><a class="quick-action calendar" href="{{ route('calendar') }}"><span class="quick-action-icon" aria-hidden="true">▦</span>View calendar</a><a class="quick-action report" href="{{ route('risk-report') }}"><span class="quick-action-icon" aria-hidden="true">⌁</span>Check risk report</a></section>

    <div class="support-grid">
        <section class="panel" id="settings" aria-labelledby="dashboard-settings-title"><h2 id="dashboard-settings-title">Settings</h2><p>Personalize this dashboard on this device.</p><label class="support-setting"><input type="checkbox" data-setting="compact"> Compact task rows</label><label class="support-setting"><input type="checkbox" data-setting="risk"> Soften risk colors</label></section>
        <section class="panel" id="help" aria-labelledby="dashboard-help-title"><h2 id="dashboard-help-title">Help</h2><p>Risk combines deadline proximity, unfinished progress, remaining effort, priority, and overdue status. Use the risk report for the full breakdown.</p><a class="text-button" href="{{ route('risk-report') }}">Open risk report →</a></section>
        <section class="panel" id="feedback" aria-labelledby="dashboard-feedback-title"><h2 id="dashboard-feedback-title">Feedback</h2><p>Save a note about your planning experience in this browser.</p><form class="feedback-form" id="feedback-form"><textarea maxlength="1200" placeholder="What would make planning easier?" required></textarea><div style="display:flex;align-items:center;justify-content:space-between;gap:8px;"><span class="feedback-status" id="feedback-status" role="status"></span><button class="button small" type="submit">Save note</button></div></form></section>
    </div>

    <dialog class="task-dialog" id="task-dialog" aria-labelledby="dialog-title">
        <form method="POST" action="{{ route('tasks.store') }}" class="dialog-inner">@csrf<input type="hidden" name="return_to" value="dashboard"><input type="hidden" name="status" value="pending">
            <div class="dialog-heading"><div><p class="eyebrow">New deadline</p><h2 id="dialog-title">Add task</h2></div><button class="close-dialog" type="button" data-close-task aria-label="Close dialog">×</button></div>
            <div class="form-grid"><div class="field full"><label for="new-title">Task name</label><input id="new-title" name="title" maxlength="255" value="{{ old('title') }}" required></div><div class="field full"><label for="new-description">Description</label><textarea id="new-description" name="description">{{ old('description') }}</textarea></div><div class="field"><label for="new-deadline">Due date</label><input id="new-deadline" name="deadline" type="date" min="{{ today()->toDateString() }}" value="{{ old('deadline') }}" required></div><div class="field"><label for="new-priority">Priority</label><select id="new-priority" name="priority"><option value="normal">Normal</option><option value="high">High</option><option value="low">Low</option></select></div><div class="field"><label for="new-hours">Estimated hours</label><input id="new-hours" name="estimated_hours" type="number" min="0" max="9999" step="0.5" value="{{ old('estimated_hours') }}"></div><div class="field"><label for="new-course">Course / category</label><input id="new-course" name="course" maxlength="100" value="{{ old('course') }}" placeholder="e.g. Biology"></div><div class="field full"><label for="new-progress">Progress (%)</label><input id="new-progress" name="progress" type="number" min="0" max="100" value="{{ old('progress', 0) }}" required></div></div>
            <div class="dialog-actions"><button class="button secondary" type="button" data-close-task>Cancel</button><button class="button" type="submit">Add task</button></div>
        </form>
    </dialog>
@endsection

@push('scripts')
<script>
    const taskDialog = document.getElementById('task-dialog');
    document.querySelectorAll('[data-open-task]').forEach((button) => button.addEventListener('click', () => taskDialog.showModal()));
    document.querySelectorAll('[data-close-task]').forEach((button) => button.addEventListener('click', () => taskDialog.close()));
    taskDialog.addEventListener('click', (event) => { if (event.target === taskDialog) taskDialog.close(); });
    @if ($errors->any()) taskDialog.showModal(); @endif

    const copilotTasks = @json($copilotTasks);
    const copilotForm = document.getElementById('copilot-form');
    const copilotQuestion = document.getElementById('copilot-question');
    const copilotResponse = document.getElementById('copilot-response');
    const taskOrder = [...copilotTasks].sort((first, second) => {
        const riskWeight = { high: 0, medium: 1, low: 2 };
        return riskWeight[first.risk] - riskWeight[second.risk] || first.days_remaining - second.days_remaining || second.remaining_hours - first.remaining_hours;
    });

    function answerCopilot(question) {
        const normalizedQuestion = question.toLowerCase();
        const task = taskOrder[0];
        if (copilotTasks.length === 0) return 'You have no open tasks right now. Add your next assignment and I can help you plan it.';
        if (normalizedQuestion.includes('week') || normalizedQuestion.includes('catch up') || normalizedQuestion.includes('workload')) {
            const upcoming = taskOrder.filter((item) => item.days_remaining <= 7);
            const hours = upcoming.reduce((total, item) => total + item.remaining_hours, 0);
            const nextTask = upcoming[0] || task;
            return `${upcoming.length} open ${upcoming.length === 1 ? 'assignment is' : 'assignments are'} due in the next 7 days, with about ${hours} hours of work remaining. Start with ${nextTask.title}: ${nextTask.recommendation}`;
        }
        if (normalizedQuestion.includes('why') || normalizedQuestion.includes('risk')) {
            const highRiskTask = taskOrder.find((item) => item.risk === 'high') || task;
            return `${highRiskTask.title} is ${highRiskTask.risk.toUpperCase()} risk. ${highRiskTask.reason} ${highRiskTask.recommendation}`;
        }
        const namedTask = taskOrder.find((item) => normalizedQuestion.includes(item.title.toLowerCase()));
        const nextTask = namedTask || task;
        const dueText = nextTask.overdue ? 'overdue' : nextTask.days_remaining === 0 ? 'due today' : `due in ${nextTask.days_remaining} days`;
        return `Work on ${nextTask.title} next. It is ${dueText}, ${nextTask.progress}% complete, and has ${nextTask.remaining_hours} estimated hours remaining. ${nextTask.recommendation}`;
    }

    copilotForm.addEventListener('submit', (event) => {
        event.preventDefault();
        copilotResponse.textContent = answerCopilot(copilotQuestion.value.trim() || 'What should I work on next?');
        copilotQuestion.value = '';
    });
    document.querySelectorAll('[data-copilot-prompt]').forEach((button) => button.addEventListener('click', () => {
        copilotResponse.textContent = answerCopilot(button.dataset.copilotPrompt);
    }));

    document.querySelectorAll('[data-setting]').forEach((setting) => {
        const key = `deadline-risk-setting-${setting.dataset.setting}`;
        setting.checked = localStorage.getItem(key) === 'true';
        setting.addEventListener('change', () => {
            localStorage.setItem(key, setting.checked);
            if (setting.dataset.setting === 'compact') document.querySelector('.priority-panel')?.classList.toggle('compact', setting.checked);
            if (setting.dataset.setting === 'risk') document.body.classList.toggle('muted-risk', setting.checked);
        });
        setting.dispatchEvent(new Event('change'));
    });

    const feedbackForm = document.getElementById('feedback-form');
    const feedbackInput = feedbackForm.querySelector('textarea');
    const feedbackStatus = document.getElementById('feedback-status');
    feedbackInput.value = localStorage.getItem('deadline-risk-feedback') || '';
    feedbackForm.addEventListener('submit', (event) => {
        event.preventDefault();
        localStorage.setItem('deadline-risk-feedback', feedbackInput.value);
        feedbackStatus.textContent = 'Saved in this browser.';
    });
</script>
@endpush
