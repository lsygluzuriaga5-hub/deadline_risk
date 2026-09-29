<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FriendController;
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
    Route::view('/settings', 'settings.index')->name('settings.index');
    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');
    Route::put('/courses/{course}', [CourseController::class, 'update'])->name('courses.update');
    Route::delete('/courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');
    Route::get('/friends', [FriendController::class, 'index'])->name('friends.index');
    Route::post('/friends/requests', [FriendController::class, 'store'])->middleware('throttle:10,1')->name('friends.requests.store');
    Route::post('/friends/requests/{friendRequest}/respond', [FriendController::class, 'respond'])->name('friends.requests.respond');
});

Route::resource('tasks', TaskController::class)
    ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])
    ->middleware('auth');

Route::post('/tasks/{task}/complete', [TaskController::class, 'complete'])->middleware('auth')->name('tasks.complete');
Route::post('/tasks/{task}/assistant', [TaskController::class, 'assistant'])->middleware(['auth', 'throttle:10,1'])->name('tasks.assistant');
Route::get('/risk-report', [DashboardController::class, 'report'])->middleware('auth')->name('risk-report');
Route::get('/calendar', [DashboardController::class, 'calendar'])->middleware('auth')->name('calendar');
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('auth')->name('dashboard');
