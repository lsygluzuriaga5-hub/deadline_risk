<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tasks</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <style>
        body {
            margin: 0;
            background: #f3f5f6;
            color: #18383a;
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 960px;
            margin: 40px auto;
            padding: 24px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
        }
        .header h1 { margin: 0; }
        .button {
            display: inline-block;
            padding: 10px 16px;
            border-radius: 10px;
            background: #0d8b98;
            color: #fff;
            text-decoration: none;
            font-weight: 600;
        }
        .panel {
            background: #fff;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 10px 24px rgba(2, 35, 37, .08);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            text-align: left;
            padding: 12px 10px;
            border-bottom: 1px solid #e6ecec;
        }
        .status-pill {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 999px;
            background: #eaf6f7;
            font-size: 12px;
            text-transform: capitalize;
        }
        .alert {
            margin-bottom: 20px;
            padding: 12px 16px;
            border-radius: 10px;
            background: #eafaf2;
            color: #1d5f42;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Tasks</h1>
            <div>
                <a class="button" href="{{ route('tasks.create') }}">Add task</a>
                <a class="button" href="{{ route('dashboard') }}" style="background:#203335; margin-left:8px;">Dashboard</a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert">{{ session('success') }}</div>
        @endif

        <div class="panel">
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Deadline</th>
                        <th>Status</th>
                        <th>Risk</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tasks as $task)
                        <tr>
                            <td>{{ $task->title }}</td>
                            <td>{{ $task->deadline?->format('d M Y') ?? 'No deadline' }}</td>
                            <td><span class="status-pill">{{ str_replace('_', ' ', $task->status) }}</span></td>
                            <td>{{ ucfirst($task->risk_level) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">No tasks yet. Create your first task to get started.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
