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
