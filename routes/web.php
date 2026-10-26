<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CurriculumController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LearnController;
use App\Http\Controllers\NotificationScheduleController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\AiTopicController;
use App\Http\Controllers\ManualMessageController;
use App\Http\Controllers\PushoverController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\TopicController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'));

// Auth
Route::middleware('guest')->group(function () {
    Route::get('/login',    [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',   [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register',[AuthController::class, 'register'])->middleware('throttle:5,1');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Curriculum
    Route::get('/curriculum',           [CurriculumController::class, 'index'])->name('curriculum.index');
    Route::get('/curriculum/create',    [CurriculumController::class, 'create'])->name('curriculum.create');
    Route::post('/curriculum',          [CurriculumController::class, 'store'])->name('curriculum.store');
    Route::get('/curriculum/{curriculum}/edit',   [CurriculumController::class, 'edit'])->name('curriculum.edit');
    Route::put('/curriculum/{curriculum}',        [CurriculumController::class, 'update'])->name('curriculum.update');
    Route::delete('/curriculum/{curriculum}',     [CurriculumController::class, 'destroy'])->name('curriculum.destroy');

    // Topics (nested under curriculum)
    Route::get('/curriculum/{curriculum}/topics',               [TopicController::class, 'index'])->name('curriculum.topics.index');
    Route::get('/curriculum/{curriculum}/topics/create',        [TopicController::class, 'create'])->name('curriculum.topics.create');
    Route::post('/curriculum/{curriculum}/topics',              [TopicController::class, 'store'])->name('curriculum.topics.store');
    Route::get('/curriculum/{curriculum}/topics/{topic}/edit',  [TopicController::class, 'edit'])->name('curriculum.topics.edit');
    Route::put('/curriculum/{curriculum}/topics/{topic}',       [TopicController::class, 'update'])->name('curriculum.topics.update');
    Route::delete('/curriculum/{curriculum}/topics/{topic}',    [TopicController::class, 'destroy'])->name('curriculum.topics.destroy');

    // AI topic tools
    Route::post('/curriculum/{curriculum}/ai/suggest',    [AiTopicController::class, 'suggest'])->name('ai.topics.suggest')->middleware('throttle:10,1');
    Route::post('/curriculum/{curriculum}/ai/bulk-store', [AiTopicController::class, 'bulkStore'])->name('ai.topics.bulk-store');

    // Notification schedule
    Route::get('/schedule',  [NotificationScheduleController::class, 'index'])->name('schedule.index');
    Route::post('/schedule', [NotificationScheduleController::class, 'save'])->name('schedule.save');

    // Push subscription
    Route::post('/push/subscribe',    [PushSubscriptionController::class, 'store'])->name('push.subscribe');
    Route::post('/push/unsubscribe',  [PushSubscriptionController::class, 'destroy'])->name('push.unsubscribe');

    // Pushover
    Route::post('/pushover/pin/send',    [PushoverController::class, 'sendPin'])->name('pushover.pin.send')->middleware('throttle:5,1');
    Route::post('/pushover/pin/verify',  [PushoverController::class, 'verifyPin'])->name('pushover.pin.verify')->middleware('throttle:10,1');
    Route::post('/pushover/disconnect',  [PushoverController::class, 'disconnect'])->name('pushover.disconnect');
    Route::post('/pushover/test',        [PushoverController::class, 'test'])->name('pushover.test')->middleware('throttle:5,1');

    // SMS
    Route::post('/sms/code/send',   [SmsController::class, 'sendCode'])->name('sms.code.send')->middleware('throttle:5,1');
    Route::post('/sms/code/verify', [SmsController::class, 'verifyCode'])->name('sms.code.verify')->middleware('throttle:10,1');
    Route::post('/sms/disconnect',  [SmsController::class, 'disconnect'])->name('sms.disconnect');

    // Manual message
    Route::post('/message/send', [ManualMessageController::class, 'send'])->name('message.send')->middleware('throttle:10,1');
});

// Admin
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users',              [AdminController::class, 'index'])->name('users.index');
    Route::get('/users/{user}/edit',  [AdminController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}',       [AdminController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}',    [AdminController::class, 'destroy'])->name('users.destroy');
});

// VAPID public key — must be accessible without auth so SW can fetch it
Route::get('/push/vapid-key', [PushSubscriptionController::class, 'vapidPublicKey'])->name('push.vapid-key');

// Learn sessions — require login + valid token
Route::middleware('auth')->group(function () {
    Route::get('/learn/{token}',                [LearnController::class, 'show'])->name('learn.session');
    Route::post('/learn/{token}/chat',          [LearnController::class, 'chat'])->name('learn.chat')->middleware('throttle:30,1');
    Route::post('/learn/{token}/suggest-topic', [AiTopicController::class, 'suggestFromChat'])->name('learn.suggest-topic')->middleware('throttle:5,1');
    Route::post('/learn/{token}/save-topic',    [AiTopicController::class, 'saveFromChat'])->name('learn.save-topic')->middleware('throttle:10,1');
});
