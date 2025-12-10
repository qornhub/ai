<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

// Simple home route just to confirm app is running
Route::get('/', function () {
    return 'Laravel AI backend is running';
});

// TEMPORARY ROUTE to run migrations and show output
Route::get('/run-migrate', function () {
    Artisan::call('migrate', ['--force' => true]);

    // Show raw artisan output in the browser
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
