@extends('layouts.risk')

@section('title', 'Risk Report')
@section('eyebrow', 'Planning intelligence')
@section('heading', 'Risk report')
@section('subtitle', 'A current view of workload pressure and where attention will matter most.')

@section('content')
    <style>
        .report-metrics { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; margin-bottom: 14px; }
        .report-metric { padding: 16px; border: 1px solid var(--panel-line); border-radius: 7px; background: #0d3031; }
        .report-metric span { color: var(--muted); font-size: 11px; }
        .report-metric strong { display: block; margin-top: 8px; font-family: 'Raleway', sans-serif; font-size: 25px; }
        .report-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .distribution { display: grid; gap: 14px; }
        .distribution-row { display: grid; grid-template-columns: 95px minmax(0, 1fr) 28px; align-items: center; gap: 10px; color: #cadbd7; font-size: 11px; }
        .distribution-row strong { text-align: right; }
        .distribution-track { height: 9px; overflow: hidden; border-radius: 5px; background: rgba(255,255,255,.09); }
        .distribution-track span { display: block; height: 100%; border-radius: inherit; background: var(--teal); }
        .distribution-track .high { background: var(--red); }
        .distribution-track .medium { background: var(--orange); }
        .distribution-track .low { background: var(--green); }
        .recommendation-list { display: grid; gap: 10px; padding: 0; margin: 0; list-style: none; }
        .recommendation-list li { display: flex; gap: 10px; align-items: flex-start; padding: 11px; border: 1px solid var(--panel-line); border-radius: 6px; color: #d0e0dc; font-size: 12px; line-height: 1.5; }
        .recommendation-mark { color: var(--teal); font-weight: 700; }
        .pressure-list { display: grid; }
        .pressure-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 0; border-top: 1px solid var(--panel-line); }
        .pressure-row:first-child { border-top: 0; }
        .pressure-row a { font-size: 12px; font-weight: 600; text-decoration: none; }
        .pressure-row small { display: block; margin-top: 4px; color: var(--muted); font-size: 10px; }
        @media (max-width: 780px) { .report-grid { grid-template-columns: 1fr; } }
        @media (max-width: 560px) { .report-metrics { grid-template-columns: repeat(2, 1fr); } }
    </style>

    <section class="report-metrics" aria-label="Risk totals">
        <article class="report-metric"><span>High risk</span><strong style="color:#ff9b96;">{{ $summary['counts']['high'] }}</strong></article>
        <article class="report-metric"><span>Medium risk</span><strong style="color:#ffc476;">{{ $summary['counts']['medium'] }}</strong></article>
        <article class="report-metric"><span>Low risk</span><strong style="color:#a9dfb4;">{{ $summary['counts']['low'] }}</strong></article>
        <article class="report-metric"><span>Overdue</span><strong style="color:#ff9b96;">{{ $summary['counts']['overdue'] }}</strong></article>
    </section>

    <div class="report-grid">
        <section class="panel">
            <div class="panel-heading"><div><h2>Risk distribution</h2><p>Current open tasks grouped by calculated risk</p></div></div>
            <div class="distribution">
                @foreach (['high' => 'High risk', 'medium' => 'Medium risk', 'low' => 'Low risk'] as $level => $label)
                    @php $width = $summary['counts']['open'] === 0 ? 0 : (int) round(($summary['counts'][$level] / $summary['counts']['open']) * 100); @endphp
                    <div class="distribution-row"><span>{{ $label }}</span><div class="distribution-track"><span class="{{ $level }}" style="width:{{ $width }}%"></span></div><strong>{{ $summary['counts'][$level] }}</strong></div>
                @endforeach
            </div>
            <div class="exposure" style="margin-bottom:0;"><span>Total risk exposure</span><strong>{{ $summary['exposure'] }}<small style="font-size:13px;">%</small></strong></div>
        </section>

        <section class="panel">
            <div class="panel-heading"><div><h2>AI recommendations</h2><p>Facts derived from your current task data</p></div></div>
            <ul class="recommendation-list">@foreach ($recommendations as $recommendation)<li><span class="recommendation-mark">→</span><span>{{ $recommendation }}</span></li>@endforeach</ul>
        </section>

        <section class="panel">
            <div class="panel-heading"><div><h2>Deadline distribution</h2><p>Open tasks arranged by days remaining</p></div></div>
            <div class="distribution">
                @foreach ([['label' => 'Overdue', 'filter' => 'overdue'], ['label' => 'Next 3 days', 'filter' => 'soon'], ['label' => '4–7 days', 'filter' => 'week'], ['label' => 'Later', 'filter' => 'later']] as $bucket)
                    @php
                        $bucketCount = $tasks->where('status', '!=', 'completed')->filter(function ($task) use ($assessments, $bucket) {
                            $days = $assessments[$task->id]['days_remaining'];
                            return match ($bucket['filter']) { 'overdue' => $days < 0, 'soon' => $days >= 0 && $days <= 3, 'week' => $days >= 4 && $days <= 7, default => $days > 7 };
                        })->count();
                        $bucketWidth = $summary['counts']['open'] === 0 ? 0 : (int) round(($bucketCount / $summary['counts']['open']) * 100);
                    @endphp
                    <div class="distribution-row"><span>{{ $bucket['label'] }}</span><div class="distribution-track"><span style="width:{{ $bucketWidth }}%"></span></div><strong>{{ $bucketCount }}</strong></div>
                @endforeach
            </div>
        </section>

        <section class="panel">
            <div class="panel-heading"><div><h2>Completion vs deadline</h2><p>Progress for open work, ordered by upcoming due date</p></div></div>
            @php $openTasks = $tasks->where('status', '!=', 'completed')->sortBy('deadline')->take(5); @endphp
            @if ($openTasks->isNotEmpty())
                <div class="pressure-list">
                    @foreach ($openTasks as $task)
                        <div class="pressure-row"><div style="min-width:0;flex:1;"><a href="{{ route('tasks.show', $task) }}">{{ $task->title }}</a><small>Due {{ $task->deadline?->format('M j, Y') }}</small></div><span style="min-width:34px;color:#d8ebe5;font-size:11px;text-align:right;">{{ $task->progress }}%</span><div class="progress-track" style="width:90px;"><span style="width:{{ $task->progress }}%"></span></div></div>
                    @endforeach
                </div>
            @else
                <div class="empty-state">No open deadlines to compare.</div>
            @endif
        </section>
    </div>
@endsection
