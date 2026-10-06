<?php

use App\Models\FriendRequest as FriendRequestModel;
use App\Models\Task;
use App\Models\User;
use App\Services\AiTaskAssistant;
use App\Services\TaskRiskAssessment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Laravel API Contract for Deadline Risk Android App
|--------------------------------------------------------------------------
*/

// --- Authentication ---
Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required|string',
    ]);

    $user = User::where('email', $credentials['email'])->first();
    $storedPassword = $user?->getAuthPassword();
    $authenticated = false;

    if ($user && $storedPassword !== null && password_get_info($storedPassword)['algoName'] === 'unknown') {
        if (hash_equals($storedPassword, $credentials['password'])) {
            $user->password = Hash::make($credentials['password']);
            $user->save();
            $authenticated = true;
        }
    } else {
        $authenticated = $user !== null && Hash::check($credentials['password'], $storedPassword);
    }

    if (! $authenticated) {
        return response()->json([
            'status' => 'error',
            'message' => 'These credentials do not match our records.',
            'data' => null,
        ], 401);
    }

    $token = bin2hex(random_bytes(32));

    return response()->json([
        'status' => 'success',
        'message' => 'Logged in successfully',
        'data' => [
            'token' => $token,
            'user' => $user,
            'requires_otp' => false,
        ],
    ]);
});

Route::post('/register', function (Request $request) {
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:8',
    ]);

    $user = User::create([
        'name' => $validated['name'],
        'email' => $validated['email'],
        'password' => Hash::make($validated['password']),
    ]);

    $token = bin2hex(random_bytes(32));

    return response()->json([
        'status' => 'success',
        'message' => 'Registered successfully',
        'data' => [
            'token' => $token,
            'user' => $user,
            'requires_otp' => false,
        ],
    ], 201);
});

Route::post('/verify-otp', function (Request $request) {
    return response()->json([
        'status' => 'success',
        'message' => 'OTP verified successfully',
        'data' => null,
    ]);
});

Route::post('/logout', function (Request $request) {
    return response()->json([
        'status' => 'success',
        'message' => 'Logged out successfully',
        'data' => null,
    ]);
});

Route::get('/user', function (Request $request) {
    $user = $request->user() ?? User::first();

    return response()->json([
        'status' => 'success',
        'message' => 'User retrieved',
        'data' => $user,
    ]);
});

// --- Dashboard API ---
Route::get('/dashboard', function (Request $request, TaskRiskAssessment $riskAssessment) {
    $user = $request->user() ?? User::first();
    $tasks = Task::where('user_id', $user?->id)->orderBy('deadline')->get();
    $summary = $riskAssessment->summarize($tasks);

    return response()->json([
        'status' => 'success',
        'message' => 'Dashboard data retrieved',
        'data' => [
            'greeting' => 'Welcome back, '.($user?->name ?? 'Student').'!',
            'date' => now()->format('l, F j, Y'),
            'total_tasks' => $tasks->count(),
            'open_tasks' => $tasks->where('status', '!=', 'completed')->count(),
            'high_risk_tasks' => $summary['counts']['high'] ?? 0,
            'completed_tasks' => $tasks->where('status', 'completed')->count(),
            'tasks' => $tasks,
        ],
    ]);
});

// --- Tasks API ---
Route::get('/tasks', function (Request $request, TaskRiskAssessment $riskAssessment) {
    $user = $request->user() ?? User::first();
    $tasks = Task::where('user_id', $user?->id)->orderBy('deadline')->get();

    return response()->json([
        'status' => 'success',
        'message' => 'Tasks retrieved successfully',
        'data' => $tasks,
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
        'message' => 'Task updated',
        'data' => $task,
    ]);
});

Route::delete('/tasks/{task}', function (Task $task) {
    $task->delete();

    return response()->json([
        'status' => 'success',
        'message' => 'Task deleted',
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
    } catch (RuntimeException$exception) {
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

    $requests = FriendRequestModel::query()
        ->with(['sender', 'receiver'])
        ->where(fn ($query) => $query
            ->where('sender_id', $user?->id)
            ->orWhere('receiver_id', $user?->id))
        ->get();

    return response()->json([
        'status' => 'success',
        'message' => 'Friends retrieved',
        'data' => $requests,
    ]);
});
