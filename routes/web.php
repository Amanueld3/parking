<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

Route::redirect('/', 'admin/login');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Payments (Chapa)
Route::post('/payments/start/{vehicle}', [PaymentController::class, 'start'])
    ->middleware(['auth'])
    ->name('payments.start');
Route::post('/payments/webhook', [PaymentController::class, 'webhook'])->name('payments.webhook');
Route::get('/payments/return', [PaymentController::class, 'return'])->name('payments.return');

// Alias route name used in redirects after payment
Route::get('/checkout', function () {
    return redirect(\App\Filament\Pages\CheckoutParking::getUrl());
})->name('filament.pages.checkout');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__ . '/auth.php';

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

Route::get('/test-pwa', function () {
    return view('test-pwa');
});
