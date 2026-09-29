@extends('layouts.risk')

@section('title', 'Tasks')
@section('eyebrow', 'Your workload')
@section('heading', 'Tasks')
@section('subtitle', 'Search, sort, and prioritize work across your courses.')
@section('page-action')<a class="button" href="{{ route('tasks.create') }}">＋ Add task</a>@endsection

@section('content')
    <style>
        .task-filters { display: grid; grid-template-columns: minmax(190px, 1fr) repeat(3, minmax(125px, 170px)); gap: 9px; margin-bottom: 15px; }
        .filter-control { min-height: 38px; }
        .filter-tabs { display: flex; gap: 5px; overflow-x: auto; margin-bottom: 14px; padding-bottom: 3px; }
        .filter-tab { flex: 0 0 auto; padding: 8px 11px; border: 1px solid var(--panel-line); border-radius: 5px; color: #aec6c1; font-size: 11px; text-decoration: none; }
        .filter-tab:hover, .filter-tab.active { border-color: rgba(67,214,197,.35); background: rgba(67,214,197,.1); color: var(--text); }
        .task-card-list { display: grid; gap: 9px; }
        .task-card { display: grid; grid-template-columns: minmax(0, 1fr) 150px 130px 110px auto; align-items: center; gap: 17px; padding: 15px; border: 1px solid var(--panel-line); border-radius: 7px; background: #0d3031; transition: border-color .18s, transform .18s; }
        .task-card:hover { transform: translateY(-1px); border-color: rgba(67,214,197,.35); }
        .task-card-title { overflow: hidden; color: #eef8f4; font-size: 13px; font-weight: 700; text-decoration: none; text-overflow: ellipsis; white-space: nowrap; }
        .task-card-title:hover { color: var(--teal); }
        .task-card-meta { margin-top: 5px; color: var(--muted); font-size: 10px; }
        .task-card-progress { display: grid; gap: 6px; color: #c5dad5; font-size: 10px; }
        .task-card-actions { display: flex; justify-content: flex-end; gap: 10px; }
        @media (max-width: 850px) { .task-filters { grid-template-columns: repeat(2, minmax(0, 1fr)); } .task-card { grid-template-columns: minmax(0, 1fr) 130px auto; } .task-card-priority, .task-card-course { display: none; } }
        @media (max-width: 560px) { .task-filters { grid-template-columns: 1fr 1fr; } .task-card { grid-template-columns: minmax(0, 1fr) auto; gap: 10px; } .task-card-progress, .task-card-priority, .task-card-course { display: none; } }
    </style>

    <nav class="filter-tabs" aria-label="Filter tasks by risk or status">
        @foreach ([['label' => 'All', 'key' => null, 'value' => null], ['label' => 'High risk', 'key' => 'risk', 'value' => 'high'], ['label' => 'Medium risk', 'key' => 'risk', 'value' => 'medium'], ['label' => 'Low risk', 'key' => 'risk', 'value' => 'low'], ['label' => 'Completed', 'key' => 'status', 'value' => 'completed'], ['label' => 'Overdue', 'key' => 'status', 'value' => 'overdue']] as $tab)
            @php
                $active = $tab['key'] === null ? !request('risk') && !request('status') : request($tab['key']) === $tab['value'];
                $parameters = request()->except(['risk', 'status', 'page']);
                if ($tab['key']) $parameters[$tab['key']] = $tab['value'];
            @endphp
            <a class="filter-tab {{ $active ? 'active' : '' }}" href="{{ route('tasks.index', $parameters) }}">{{ $tab['label'] }}</a>
        @endforeach
    </nav>

    <form class="task-filters" method="GET" action="{{ route('tasks.index') }}">
        <input class="filter-control" type="search" name="q" value="{{ $filters['query'] }}" placeholder="Search title or description" aria-label="Search task title or description">
        <select class="filter-control" name="risk" aria-label="Filter by risk">
            <option value="">Any risk</option>
            @foreach (['high' => 'High risk', 'medium' => 'Medium risk', 'low' => 'Low risk'] as $value => $label)<option value="{{ $value }}" @selected($filters['risk'] === $value)>{{ $label }}</option>@endforeach
        </select>
        <select class="filter-control" name="status" aria-label="Filter by status">
            <option value="">Any status</option>
            @foreach (['pending' => 'Pending', 'in_progress' => 'In progress', 'completed' => 'Completed', 'overdue' => 'Overdue'] as $value => $label)<option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>@endforeach
        </select>
        <select class="filter-control" name="course" aria-label="Filter by course">
            <option value="">All courses</option>
            @foreach ($courses as $courseOption)<option value="{{ $courseOption }}" @selected($filters['course'] === $courseOption)>{{ $courseOption }}</option>@endforeach
        </select>
        <select class="filter-control" name="sort" aria-label="Sort tasks" style="grid-column:1/-1;justify-self:end;width:min(220px,100%);">
            <option value="deadline" @selected($filters['sort'] !== 'priority')>Sort: deadline</option>
            <option value="priority" @selected($filters['sort'] === 'priority')>Sort: priority</option>
        </select>
        <button class="button small" type="submit" style="grid-column:1/-1;justify-self:end;">Apply filters</button>
    </form>

    <section aria-label="Task results">
        <div class="panel-heading"><div><h2>{{ $tasks->count() }} {{ Str::plural('task', $tasks->count()) }}</h2><p>Risk is recalculated from the current deadline, progress, workload, and priority.</p></div></div>
        @if ($tasks->isNotEmpty())
            <div class="task-card-list">
                @foreach ($tasks as $task)
                    @php $assessment = $assessments[$task->id]; @endphp
                    <article class="task-card">
                        <div><a class="task-card-title" href="{{ route('tasks.show', $task) }}">{{ $task->title }}</a><div class="task-card-meta">Due {{ $task->deadline?->format('M j, Y') ?? 'not set' }} · {{ $task->course ?: 'Uncategorized' }}</div></div>
                        <div class="task-card-progress"><span>{{ $task->progress }}% complete</span><div class="progress-track"><span style="width:{{ $task->progress }}%"></span></div></div>
                        <div class="task-card-priority"><span class="task-card-meta">Priority</span><div style="font-size:12px;">{{ ucfirst($task->priority) }}</div></div>
                        <span class="risk-badge {{ $task->status === 'completed' ? 'completed' : $assessment['level'] }}"><i class="risk-dot"></i>{{ $task->status === 'completed' ? 'Completed' : $assessment['level'].' risk' }}</span>
                        <div class="task-card-actions"><a class="text-button" href="{{ route('tasks.show', $task) }}">View</a>@if ($task->status !== 'completed')<form method="POST" action="{{ route('tasks.complete', $task) }}">@csrf<button class="text-button" type="submit">Mark complete</button></form>@endif</div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="panel empty-state">No tasks match these filters. Adjust your search or add a task.</div>
        @endif
    </section>
@endsection
