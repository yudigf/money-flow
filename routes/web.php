<?php

use Illuminate\Support\Facades\Route;

// Serve Vue SPA for all frontend routes
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');
