<?php

use App\Models\FriendRequest as FriendRequestModel;
use App\Models\Task;
use App\Models\User;
use App\Services\AiTaskAssistant;
use App\Services\TaskRiskAssessment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes for Deadline Risk Android App
|--------------------------------------------------------------------------
*/

// --- Tasks API ---
Route::get('/tasks', function (Request $request, TaskRiskAssessment $riskAssessment) {
    $user = $request->user() ?? User::first();

    if (! $user) {
        return response()->json([
            'status' => 'success',
            'message' => 'No user found in database.',
            'data' => [],
        ]);
    }

    $allTasks = Task::where('user_id', $user->id)->orderBy('deadline')->get();

    return response()->json([
        'status' => 'success',
        'message' => 'Tasks retrieved successfully',
        'data' => $allTasks,
    ]);
});

Route::post('/tasks', function (Request $request, TaskRiskAssessment $riskAssessment) {
    $user = $request->user() ?? User::first();

    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'deadline' => 'required|date',
        'status' => 'required|in:pending,in_progress,completed',
        'progress' => 'required|integer|between:0,100',
        'estimated_hours' => 'nullable|numeric|min:0|max:9999',
        'priority' => 'required|in:low,normal,high',
        'course' => 'nullable|string|max:100',
    ]);

    if ($validated['status'] === 'completed') {
        $validated['progress'] = 100;
    }

    $task = new Task($validated + ['user_id' => $user->id]);
    $task->risk_level = $riskAssessment->assess($task)['level'];
    $task->save();

    return response()->json([
        'status' => 'success',
        'message' => 'Task created successfully',
        'data' => $task,
    ], 201);
});

Route::get('/tasks/{task}', function (Task $task) {
    return response()->json([
        'status' => 'success',
        'message' => 'Task retrieved',
        'data' => $task,
    ]);
});

Route::put('/tasks/{task}', function (Request $request, Task $task, TaskRiskAssessment $riskAssessment) {
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'deadline' => 'required|date',
        'status' => 'required|in:pending,in_progress,completed',
        'progress' => 'required|integer|between:0,100',
        'estimated_hours' => 'nullable|numeric|min:0|max:9999',
        'priority' => 'required|in:low,normal,high',
        'course' => 'nullable|string|max:100',
    ]);

    $task->update($validated);
    $task->update(['risk_level' => $riskAssessment->assess($task)['level']]);

    return response()->json([
        'status' => 'success',
        'message' => 'Task updated successfully',
        'data' => $task,
    ]);
});

Route::delete('/tasks/{task}', function (Task $task) {
    $task->delete();

    return response()->json([
        'status' => 'success',
        'message' => 'Task deleted successfully',
        'data' => null,
    ]);
});

Route::post('/tasks/{task}/complete', function (Task $task, TaskRiskAssessment $riskAssessment) {
    $task->update(['status' => 'completed', 'progress' => 100]);
    $task->update(['risk_level' => $riskAssessment->assess($task)['level']]);

    return response()->json([
        'status' => 'success',
        'message' => 'Task marked as completed',
        'data' => $task,
    ]);
});

Route::post('/tasks/{task}/assistant', function (Request $request, Task $task, AiTaskAssistant $assistant) {
    $validated = $request->validate([
        'question' => 'required|string|max:2000',
    ]);

    try {
        $answer = $assistant->answer($task, $validated['question']);

        return response()->json([
            'status' => 'success',
            'message' => 'AI Assistant answer generated',
            'data' => $answer,
        ]);
    } catch (RuntimeException $exception) {
        return response()->json([
            'status' => 'error',
            'message' => $exception->getMessage(),
            'data' => null,
        ], 500);
    }
});

// --- Friends API ---
Route::get('/friends', function (Request $request) {
    $user = $request->user() ?? User::first();

    if (! $user) {
        return response()->json([
            'status' => 'success',
            'message' => 'No user found in database.',
            'data' => [],
        ]);
    }

    $requests = FriendRequestModel::query()
        ->with(['sender', 'receiver'])
        ->where(fn ($query) => $query
            ->where('sender_id', $user->id)
            ->orWhere('receiver_id', $user->id))
        ->get();

    return response()->json([
        'status' => 'success',
        'message' => 'Friends retrieved',
        'data' => $requests,
    ]);
});
