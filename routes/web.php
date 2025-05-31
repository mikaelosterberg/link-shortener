<?php

use App\Http\Controllers\RedirectController;
use App\Http\Controllers\QrCodeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// QR Code routes (must come before the redirect route)
Route::get('/qr/{link}/download', [QrCodeController::class, 'generate'])
    ->name('qr.download')
    ->middleware('auth');
    
Route::get('/qr/{link}/display', [QrCodeController::class, 'display'])
    ->name('qr.display')
    ->middleware('auth');

// Redirect route with rate limiting (must be last)
Route::get('/{shortCode}', [RedirectController::class, 'redirect'])
    ->name('redirect')
    ->middleware('throttle:60,1')
    ->where('shortCode', '[a-zA-Z0-9\-_]+');
