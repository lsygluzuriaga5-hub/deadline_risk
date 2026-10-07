@extends('layouts.risk')

@section('title', 'Calendar')
@section('eyebrow', 'Plan your month')
@section('heading', 'Calendar')
@section('subtitle', 'See assignments and deadlines at a glance.')
@section('page-action')@endsection

@section('content')
    <style>
        .calendar-panel { overflow: hidden; }
        .calendar-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 17px; }
        .calendar-toolbar h2 { margin: 0; font-family: 'Raleway', sans-serif; font-size: 17px; }
        .calendar-nav { display: flex; align-items: center; gap: 7px; }
        .calendar-nav a { display: grid; min-width: 38px; height: 36px; padding: 0 10px; place-items: center; border: 1px solid var(--panel-line); border-radius: 5px; color: var(--text); text-decoration: none; background: var(--panel); }
        .calendar-nav a:hover { border-color: var(--teal); color: white; }
        .calendar-grid { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); border-top: 1px solid var(--panel-line); border-left: 1px solid var(--panel-line); }
        .calendar-weekday { padding: 9px 6px; color: var(--muted); font-size: 9px; font-weight: 700; letter-spacing: .06em; text-align: center; text-transform: uppercase; }
        .calendar-day { min-height: 102px; padding: 7px; overflow: hidden; border-right: 1px solid var(--panel-line); border-bottom: 1px solid var(--panel-line); background: rgba(255,255,255,.012); }
        .calendar-day.outside { opacity: .42; }
        .calendar-day.today { background: rgba(67,214,197,.08); box-shadow: inset 0 2px var(--teal); }
        .calendar-day-number { display: flex; align-items: center; justify-content: space-between; color: #d3e4df; font-size: 10px; }
        .calendar-day.today .calendar-day-number { color: var(--teal); font-weight: 700; }
        .calendar-event { display: block; overflow: hidden; margin-top: 6px; padding: 4px 5px; border-left: 2px solid var(--blue); border-radius: 3px; background: rgba(96,165,250,.11); color: var(--blue); font-size: 9px; text-decoration: none; text-overflow: ellipsis; white-space: nowrap; }
        .calendar-event.high, .calendar-event.overdue { border-color: var(--red); background: rgba(251,113,133,.12); color: var(--red); }
        .calendar-event.medium { border-color: #8b5cf6; background: rgba(139,92,246,.2); color: #c4a7ff; }
        :root[data-theme="light"] .calendar-event.medium { border-color: #6d28d9; background: #f0eaff; color: #4c1d95; }
        .calendar-event.low, .calendar-event.completed { border-color: var(--green); background: rgba(74,222,128,.1); color: var(--green); }
        .calendar-holiday { display: block; overflow: hidden; margin-top: 6px; padding: 4px 5px; border-left: 2px solid var(--purple); border-radius: 3px; background: rgba(167,139,250,.12); color: var(--purple); font-size: 9px; text-overflow: ellipsis; white-space: nowrap; }
        .calendar-holiday.working { border-color: var(--teal); background: rgba(67,214,197,.1); color: var(--teal); }
        .calendar-legend { display: flex; flex-wrap: wrap; gap: 13px; margin-top: 14px; color: var(--muted); font-size: 10px; }
        .calendar-legend span { display: flex; align-items: center; gap: 6px; }
        .calendar-legend i { width: 7px; height: 7px; border-radius: 2px; background: var(--red); }
        .calendar-legend .medium { background: var(--orange); }
        .calendar-legend .low { background: var(--green); }
        .calendar-legend .completed { background: var(--green); }
        .calendar-legend .holiday { background: var(--purple); }
        .calendar-legend .working { background: var(--teal); }
        @media (max-width: 650px) { .calendar-day { min-height: 78px; padding: 5px 3px; } .calendar-weekday { font-size: 8px; } .calendar-event { padding: 3px 2px; font-size: 8px; } }
        @media (max-width: 420px) { .calendar-day { min-height: 60px; } .calendar-event { height: 5px; padding: 0; color: transparent; } }
    </style>

    <section class="panel calendar-panel" aria-label="Monthly deadline calendar">
        <div class="calendar-toolbar"><h2>{{ $month->format('F Y') }}</h2><nav class="calendar-nav" aria-label="Calendar month navigation"><a href="{{ route('calendar', ['month' => $month->copy()->subMonth()->format('Y-m')]) }}" aria-label="Previous month">←</a><a href="{{ route('calendar') }}" aria-label="Current month">Today</a><a href="{{ route('calendar', ['month' => $month->copy()->addMonth()->format('Y-m')]) }}" aria-label="Next month">→</a></nav></div>
        <div class="calendar-grid" role="grid" aria-label="{{ $month->format('F Y') }}">
            @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $weekday)<div class="calendar-weekday" role="columnheader">{{ $weekday }}</div>@endforeach
            @foreach ($days as $week)
                @foreach ($week as $day)
                    <div class="calendar-day {{ $day['date']->month !== $month->month ? 'outside' : '' }} {{ $day['date']->isToday() ? 'today' : '' }}" role="gridcell" aria-label="{{ $day['date']->format('l, F j') }}">
                        <span class="calendar-day-number">{{ $day['date']->day }} @if ($day['date']->isToday())<small>Today</small>@endif</span>
                        @if ($day['holiday'])
                            <span class="calendar-holiday {{ $day['holiday']['category'] === 'working' ? 'working' : '' }}" title="{{ $day['holiday']['name'] }} — {{ $day['holiday']['category'] === 'regular' ? 'Regular Holiday' : ($day['holiday']['category'] === 'working' ? 'Special Working Day' : 'Special Non-Working Day') }}">{{ $day['holiday']['name'] }}</span>
                        @endif
                        @foreach ($day['tasks']->take(3) as $task)
                            @php $assessment = $assessments[$task->id]; $eventClass = $task->status === 'completed' ? 'completed' : ($assessment['overdue'] ? 'overdue' : $assessment['level']); @endphp
                            <a class="calendar-event {{ $eventClass }}" href="{{ route('tasks.show', $task) }}" title="{{ $task->title }} · {{ $task->status === 'completed' ? 'Completed' : ucfirst($assessment['level'].' risk') }}">{{ $task->title }}</a>
                        @endforeach
                        @if ($day['tasks']->count() > 3)<span class="muted" style="font-size:8px;">+{{ $day['tasks']->count() - 3 }} more</span>@endif
                    </div>
                @endforeach
            @endforeach
        </div>
        <div class="calendar-legend"><span><i></i>High risk / overdue</span><span><i class="medium"></i>Moderate risk</span><span><i class="low"></i>Low risk</span><span><i class="completed"></i>Completed</span><span><i class="holiday"></i>Holiday</span><span><i class="working"></i>Special working day</span></div>
        <p class="muted" style="margin:12px 0 0;font-size:10px;">Eidul Fitr and Eidul Adha dates will be added when officially announced.</p>
    </section>
@endsection
