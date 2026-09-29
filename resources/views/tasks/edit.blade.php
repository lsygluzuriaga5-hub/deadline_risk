<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Task</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <style>
        body { margin: 0; background: #f3f5f6; color: #18383a; font-family: Arial, sans-serif; }
        .container { max-width: 700px; margin: 40px auto; padding: 24px; }
        .panel { background: #fff; border-radius: 14px; padding: 24px; box-shadow: 0 10px 24px rgba(2, 35, 37, .08); }
        h1 { margin-top: 0; }
        .field { display: grid; gap: 8px; margin-bottom: 16px; }
        label { font-weight: 600; }
        input, textarea, select, button { width: 100%; padding: 10px 12px; border-radius: 10px; border: 1px solid #dfe5e5; font: inherit; }
        textarea { min-height: 120px; resize: vertical; }
        .button-row { display: flex; gap: 12px; margin-top: 12px; }
        .button { display: inline-block; padding: 10px 16px; border: none; border-radius: 10px; background: #0d8b98; color: #fff; text-decoration: none; cursor: pointer; font-weight: 600; }
        .secondary { background: #203335; color: #fff; text-decoration: none; }
        .error { color: #b43232; font-size: 13px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="panel">
            <h1>Edit Task</h1>

            <form method="POST" action="{{ route('tasks.update', $task) }}">
                @csrf
                @method('PUT')

                <div class="field">
                    <label for="title">Title</label>
                    <input id="title" name="title" type="text" value="{{ old('title', $task->title) }}" required>
                    @error('title')
                        <span class="error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="field">
                    <label for="description">Description</label>
                    <textarea id="description" name="description">{{ old('description', $task->description) }}</textarea>
                    @error('description')
                        <span class="error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="field">
                    <label for="deadline">Deadline</label>
                    <input id="deadline" name="deadline" type="date" value="{{ old('deadline', $task->deadline?->format('Y-m-d')) }}" required>
                    @error('deadline')
                        <span class="error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="field">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="pending" {{ old('status', $task->status) === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="in_progress" {{ old('status', $task->status) === 'in_progress' ? 'selected' : '' }}>In progress</option>
                        <option value="completed" {{ old('status', $task->status) === 'completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                    @error('status')
                        <span class="error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="field">
                    <label for="risk_level">Risk level</label>
                    <select id="risk_level" name="risk_level" required>
                        <option value="low" {{ old('risk_level', $task->risk_level) === 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ old('risk_level', $task->risk_level) === 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="high" {{ old('risk_level', $task->risk_level) === 'high' ? 'selected' : '' }}>High</option>
                    </select>
                    @error('risk_level')
                        <span class="error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="button-row">
                    <button class="button" type="submit">Update task</button>
                    <a class="button secondary" href="{{ route('tasks.index') }}">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
