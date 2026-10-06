@extends('layouts.risk')

@section('title', $task->title)
@section('eyebrow', 'Task detail')
@section('heading', $task->title)
@section('subtitle', $task->course ?: 'Uncategorized')
@section('page-action')
    <div style="display:flex;flex-wrap:wrap;gap:8px;">
        @if ($task->canvas_url)
            <a class="button secondary" href="{{ $task->canvas_url }}" target="_blank" rel="noopener noreferrer">Open in Canvas</a>
        @endif
        <a class="button secondary" href="{{ route('tasks.edit', $task) }}">Edit task</a>
    </div>
@endsection

@section('content')
    <style>
        .detail-grid { display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(270px, .8fr); gap: 14px; }
        .detail-stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; margin: 18px 0; }
        .detail-stat { padding: 12px; border: 1px solid var(--panel-line); border-radius: 6px; background: rgba(255,255,255,.025); }
        .detail-stat span { display: block; color: var(--muted); font-size: 10px; }
        .detail-stat strong { display: block; margin-top: 6px; font-size: 14px; }
        .detail-description { color: #c8dad6; font-size: 13px; line-height: 1.7; white-space: pre-line; }
        .explanation-box { margin-top: 15px; padding: 14px; border-left: 2px solid var(--purple); background: rgba(167,139,250,.07); color: #cee0db; font-size: 12px; line-height: 1.6; }
        .activity-list { display: grid; gap: 14px; margin-top: 15px; }
        .activity-entry { display: flex; justify-content: space-between; gap: 12px; color: #cfdfdb; font-size: 11px; }
        .danger-zone { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--panel-line); }
        .assistant-form { display: grid; gap: 10px; margin-top: 14px; }
        .assistant-panel .eyebrow { color: var(--purple); }
        .assistant-form textarea { width: 100%; min-height: 84px; padding: 10px; border: 1px solid var(--panel-line); border-radius: 6px; background: rgba(0,0,0,.16); color: inherit; font: inherit; resize: vertical; }
        .assistant-answer { margin-top: 14px; padding: 14px; border: 1px solid var(--panel-line); border-radius: 6px; color: #cee0db; font-size: 12px; line-height: 1.7; white-space: pre-line; }
        .assistant-error { margin-top: 12px; color: #ffb5a8; font-size: 12px; }
        @media (max-width: 760px) { .detail-grid { grid-template-columns: 1fr; } }
        @media (max-width: 460px) { .detail-stats { grid-template-columns: 1fr 1fr; } }
    </style>

    <div class="detail-grid">
        <section class="panel">
            <div class="panel-heading"><div><p class="eyebrow">Description</p><h2>Work details</h2></div><span class="risk-badge {{ $task->status === 'completed' ? 'completed' : $assessment['level'] }}"><i class="risk-dot"></i>{{ $task->status === 'completed' ? 'Completed' : $assessment['level'].' risk' }}</span></div>
            <p class="detail-description">{{ $task->description ?: 'No description has been added.' }}</p>
            <div class="detail-stats">
                <div class="detail-stat"><span>Deadline</span><strong>{{ $task->deadline?->format('M j, Y') ?? 'Not set' }}</strong></div>
                <div class="detail-stat"><span>Priority</span><strong>{{ ucfirst($task->priority) }}</strong></div>
                <div class="detail-stat"><span>Course</span><strong>{{ $task->course ?: 'Uncategorized' }}</strong></div>
                <div class="detail-stat"><span>Estimated hours</span><strong>{{ $task->estimated_hours ?? 'Not set' }}</strong></div>
                <div class="detail-stat"><span>Remaining estimate</span><strong>{{ $assessment['remaining_hours'] }} hrs</strong></div>
                <div class="detail-stat"><span>Progress</span><strong>{{ $task->progress }}%</strong></div>
            </div>
            <span class="muted" style="display:block;margin-bottom:7px;font-size:10px;">Completion</span>
            <div class="progress-track"><span style="width:{{ $task->progress }}%"></span></div>
        </section>

        <aside>
            <section class="panel assistant-panel" style="margin-bottom:14px;">
                <p class="eyebrow">Task assistant</p>
                <h2>Need help with this task?</h2>
                <p class="detail-description">Ask for a plan, help breaking down the work, or advice on what to do next.</p>
                <form class="assistant-form" method="POST" action="{{ route('tasks.assistant', $task) }}">
                    @csrf
                    <label class="muted" for="assistant-question" style="font-size:11px;">Your question</label>
                    <textarea id="assistant-question" name="question" maxlength="2000" required placeholder="How should I break this task into steps?">{{ old('question') }}</textarea>
                    @error('question')<span class="assistant-error">{{ $message }}</span>@enderror
                    <button class="button small" type="submit">Ask AI</button>
                </form>
                @if (session('assistant_error'))<p class="assistant-error" role="alert">{{ session('assistant_error') }}</p>@endif
                @if (session('assistant_answer'))
                    <div class="assistant-answer" aria-live="polite"><strong>You asked:</strong> {{ session('assistant_question') }}<br><br>{{ session('assistant_answer') }}</div>
                @endif
            </section>
            @if ($task->source === 'canvas' && $task->canvas_course_id && $task->canvas_assignment_id)
                <section class="panel" style="margin-bottom:14px;">
                    <p class="eyebrow">Canvas submission</p>
                    <h2>Send an answer to Canvas</h2>
                    <p class="detail-description">This works for assignments that accept online text-entry submissions. The AI option submits its generated response immediately.</p>
                    @if (session('canvas_submit_error'))<p class="assistant-error" role="alert">{{ session('canvas_submit_error') }}</p>@endif
                    @if (session('canvas_ai_answer'))<div class="assistant-answer" aria-live="polite"><strong>AI answer submitted to Canvas:</strong><br><br>{{ session('canvas_ai_answer') }}</div>@endif
                    <form class="assistant-form" method="POST" action="{{ route('tasks.canvas-submit', $task) }}">
                        @csrf
                        <label class="muted" for="canvas-answer" style="font-size:11px;">Your written answer</label>
                        <textarea id="canvas-answer" name="answer" maxlength="100000" required>{{ old('answer') }}</textarea>
                        @error('answer')<span class="assistant-error">{{ $message }}</span>@enderror
                        <button class="button small" type="submit">Submit written answer</button>
                    </form>
                    <form class="assistant-form" method="POST" action="{{ route('tasks.canvas-ai-submit', $task) }}">
                        @csrf
                        <button class="button secondary small" type="submit">Generate with AI and submit to Canvas</button>
                    </form>
                </section>
            @endif
            <section class="panel">
                <p class="eyebrow">AI risk explanation</p>
                <h2>Why this level?</h2>
                <div class="explanation-box">{{ $assessment['reason'] }}</div>
                <p class="eyebrow" style="margin-top:19px;">Recommended action</p>
                <p class="detail-description">{{ $assessment['recommendation'] }}</p>
                @if ($assessment['overdue'])<p class="risk-badge high" style="margin:0;">Overdue · {{ abs($assessment['days_remaining']) }} {{ abs($assessment['days_remaining']) === 1 ? 'day' : 'days' }}</p>@endif
                <form class="assistant-form" method="POST" action="{{ route('tasks.gemini-risk', $task) }}">
                    @csrf
                    <button class="button secondary small" type="submit">Get Gemini analysis</button>
                </form>
                @if (session('gemini_error'))<p class="assistant-error" role="alert">{{ session('gemini_error') }}</p>@endif
                @if (session('gemini_analysis'))<div class="assistant-answer" aria-live="polite"><strong>Gemini analysis</strong><br><br>{{ session('gemini_analysis') }}</div>@endif
            </section>

            <section class="panel" style="margin-top:14px;">
                <p class="eyebrow">Activity history</p>
                <h2>Recent activity</h2>
                <div class="activity-list">
                    <div class="activity-entry"><span>Task created</span><time>{{ $task->created_at?->format('M j, Y · g:i a') }}</time></div>
                    @if ($task->updated_at?->ne($task->created_at))<div class="activity-entry"><span>Task updated</span><time>{{ $task->updated_at?->format('M j, Y · g:i a') }}</time></div>@endif
                </div>
                <div class="danger-zone">
                    @if ($task->status !== 'completed')
                        <form method="POST" action="{{ route('tasks.complete', $task) }}">@csrf<button class="button small" type="submit">Mark complete</button></form>
                    @else
                        <span class="risk-badge completed">Completed</span>
                    @endif
                    <form method="POST" action="{{ route('tasks.destroy', $task) }}" onsubmit="return confirm('Delete this task? This cannot be undone.')">@csrf @method('DELETE')<button class="button danger small" type="submit">Delete task</button></form>
                </div>
            </section>
        </aside>
    </div>
@endsection
