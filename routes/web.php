<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AIController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BiasController;
use Illuminate\Support\Facades\Artisan;



Route::get('/force-migrate', function () {
    Artisan::call('migrate:fresh', ['--force' => true]);
    return nl2br(Artisan::output());
});
Route::get('/debug-db', function () {
    return response()->json([
        'DB_HOST' => env('DB_HOST'),
        'DB_PORT' => env('DB_PORT'),
        'DB_DATABASE' => env('DB_DATABASE'),
        'DB_USERNAME' => env('DB_USERNAME'),
        // do not show password in production
        'DB_PASSWORD' => env('DB_PASSWORD') ? 'SET' : 'NOT SET',
    ]);
});

// Visiting the root → go to login page
Route::get('/', function () {
    return redirect()->route('login.show');
});

// Register + Login
Route::get('/register', [AuthController::class, 'showRegister'])->name('register.show');
Route::post('/register', [AuthController::class, 'register'])->name('register');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login.show');
Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


/*
|--------------------------------------------------------------------------
| Protected Routes (must login)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    Route::get('/ai/new', [AIController::class, 'showForm'])->name('ai.new');
    Route::get('/ai/form', [AIController::class, 'showForm'])->name('ai.form');
    Route::post('/ai/predict', [AIController::class, 'predict'])->name('ai.predict');
    Route::post('/ai/decisions/{decision}/override', [AIController::class, 'overrideDecision'])->name('ai.override');

    Route::get('/ai', [DashboardController::class, 'index'])->name('ai.index');
    Route::get('/ai/decision/{id}', [DashboardController::class, 'showDecision'])->name('ai.decision_show');
    Route::delete('/ai/delete/{decision}', [DashboardController::class, 'delete'])->name('ai.delete');

    Route::get('/ai/bias', [BiasController::class, 'index'])->name('ai.bias');
});