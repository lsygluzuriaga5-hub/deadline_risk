@extends('layouts.risk')

@section('title', $task ? 'Edit task' : 'Add task')
@section('eyebrow', $task ? 'Update task details' : 'Plan a deadline')
@section('heading', $task ? 'Edit task' : 'Add task')
@section('subtitle', 'Track the deadline, effort, and progress so risk guidance stays useful.')

@section('content')
    <style>
        .form-panel { max-width: 760px; }
        .form-panel .field { margin-bottom: 15px; }
        .form-panel .form-grid { gap: 15px; }
        .form-panel .field label { font-size: 12px; }
        .form-panel .field input, .form-panel .field select, .form-panel .field textarea { min-height: 42px; }
        .form-panel .field textarea { min-height: 110px; }
        .form-footer { display: flex; gap: 9px; justify-content: flex-end; margin-top: 20px; }
    </style>

    <form class="panel form-panel" method="POST" action="{{ $task ? route('tasks.update', $task) : route('tasks.store') }}">
        @csrf
        @if ($task) @method('PUT') @else<input type="hidden" name="return_to" value="tasks.index">@endif
        <div class="form-grid">
            <div class="field full"><label for="title">Task name</label><input id="title" name="title" maxlength="255" value="{{ old('title', $task?->title) }}" required></div>
            <div class="field full"><label for="description">Description</label><textarea id="description" name="description">{{ old('description', $task?->description) }}</textarea></div>
            <div class="field"><label for="deadline">Due date</label><input id="deadline" name="deadline" type="date" value="{{ old('deadline', $task?->deadline?->format('Y-m-d')) }}"></div>
            <div class="field"><label for="priority">Priority</label><select id="priority" name="priority" required>@foreach (['normal' => 'Normal', 'high' => 'High', 'low' => 'Low'] as $value => $label)<option value="{{ $value }}" @selected(old('priority', $task?->priority ?? 'normal') === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="field"><label for="estimated_hours">Estimated hours</label><input id="estimated_hours" name="estimated_hours" type="number" min="0" max="9999" step="0.5" value="{{ old('estimated_hours', $task?->estimated_hours) }}"></div>
            <div class="field"><label for="course">Course / category</label><input id="course" name="course" maxlength="100" value="{{ old('course', $task?->course) }}" placeholder="e.g. Web Development"></div>
            <p class="field full" style="margin-top:-8px;color:var(--muted);font-size:11px;">Create a course from the <a href="{{ route('courses.index') }}">Courses page</a> to attach professor details and grades to matching task labels.</p>
            <div class="field"><label for="progress">Progress (%)</label><input id="progress" name="progress" type="number" min="0" max="100" value="{{ old('progress', $task?->progress ?? 0) }}" required></div>
            <div class="field"><label for="status">Status</label><select id="status" name="status" required>@foreach (['pending' => 'Pending', 'in_progress' => 'In progress', 'completed' => 'Completed'] as $value => $label)<option value="{{ $value }}" @selected(old('status', $task?->status ?? 'pending') === $value)>{{ $label }}</option>@endforeach</select></div>
        </div>
        <div class="form-footer"><a class="button secondary" href="{{ $task ? route('tasks.show', $task) : route('tasks.index') }}">Cancel</a><button class="button" type="submit">{{ $task ? 'Save changes' : 'Add task' }}</button></div>
    </form>
@endsection
