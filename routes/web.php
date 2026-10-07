<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CanvasController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\FriendController;
use App\Http\Controllers\FriendMessageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return view('home');
})->name('home');

Route::get('/login', function () {
    return redirect()->route('home');
})->name('login');

Route::get('/register', function () {
    return view('auth.register');
})->middleware('guest')->name('register');

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.store');
Route::get('/login/otp', [AuthController::class, 'showOtp'])->name('login.otp');
Route::post('/login/otp', [AuthController::class, 'verifyOtp'])->middleware('throttle:10,1')->name('login.otp.verify');
Route::post('/login/otp/resend', [AuthController::class, 'resendOtp'])->middleware('throttle:3,1')->name('login.otp.resend');
Route::post('/register', [AuthController::class, 'register'])->middleware(['guest', 'throttle:5,1'])->name('register.store');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/feedback', [FeedbackController::class, 'store'])->middleware('throttle:5,1')->name('feedback.store');
    Route::get('/settings/{section?}', function (?string $section = 'school-profile') {
        $sections = ['school-profile', 'account', 'canvas', 'appearance', 'accessibility', 'notifications', 'emails', 'password-auth', 'web-session', 'blocked-users'];

        abort_unless(in_array($section, $sections, true), 404);

        return view('settings.index', [
            'section' => $section,
            'sections' => $sections,
            'canvasApiKeyConfigured' => filled(config('services.canvas.api_key')),
        ]);
    })->name('settings.index');
    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');
    Route::post('/courses/canvas-sync', [CourseController::class, 'syncFromCanvas'])->name('courses.canvas-sync');
    Route::put('/courses/{course}', [CourseController::class, 'update'])->name('courses.update');
    Route::delete('/courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');
    Route::get('/friends', [FriendController::class, 'index'])->name('friends.index');
    Route::post('/friends/requests', [FriendController::class, 'store'])->middleware('throttle:10,1')->name('friends.requests.store');
    Route::post('/friends/messages', [FriendMessageController::class, 'store'])->middleware('throttle:20,1')->name('friends.messages.store');
    Route::post('/friends/requests/{friendRequest}/respond', [FriendController::class, 'respond'])->name('friends.requests.respond');
    Route::get('/canvas/connect', [CanvasController::class, 'connect'])->name('canvas.connect');
    Route::get('/canvas/callback', [CanvasController::class, 'callback'])->name('canvas.callback');
    Route::delete('/canvas/disconnect', [CanvasController::class, 'disconnect'])->name('canvas.disconnect');
    Route::get('/ai/test', [CanvasController::class, 'aiTest'])->middleware('throttle:5,1')->name('ai.test');
    Route::get('/canvas/test', [CanvasController::class, 'test'])->name('canvas.test');
    Route::get('/canvas/assignments', [CanvasController::class, 'assignments'])->name('canvas.assignments');
    Route::post('/canvas/sync', [CanvasController::class, 'sync'])->name('canvas.sync');
});

Route::resource('tasks', TaskController::class)
    ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])
    ->middleware('auth');

Route::post('/tasks/{task}/complete', [TaskController::class, 'complete'])->middleware('auth')->name('tasks.complete');
Route::post('/tasks/{task}/assistant', [TaskController::class, 'assistant'])->middleware(['auth', 'throttle:10,1'])->name('tasks.assistant');
Route::post('/tasks/{task}/gemini-risk', [TaskController::class, 'analyzeGeminiRisk'])->middleware(['auth', 'throttle:5,1'])->name('tasks.gemini-risk');
Route::post('/tasks/{task}/canvas-submit', [TaskController::class, 'submitCanvasAnswer'])->middleware(['auth', 'throttle:5,1'])->name('tasks.canvas-submit');
Route::post('/tasks/{task}/canvas-ai-submit', [TaskController::class, 'generateAndSubmitCanvasAnswer'])->middleware(['auth', 'throttle:3,1'])->name('tasks.canvas-ai-submit');
Route::get('/risk-report', [DashboardController::class, 'report'])->middleware('auth')->name('risk-report');
Route::get('/calendar', [DashboardController::class, 'calendar'])->middleware('auth')->name('calendar');
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('auth')->name('dashboard');
