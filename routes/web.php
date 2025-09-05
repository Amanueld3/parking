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

// PWA advanced handlers
Route::post('/share/receive', function (\Illuminate\Http\Request $request) {
    // TODO: handle shared content, files, etc.
    return response()->json(['ok' => true]);
});

Route::get('/files/open', function (\Illuminate\Http\Request $request) {
    // TODO: open file logic based on query params
    return response('File handler invoked');
});

Route::get('/protocol', function (\Illuminate\Http\Request $request) {
    // TODO: handle custom protocol url param
    return response('Protocol handler: ' . $request->query('url'));
});

Route::view('/widgets/status', 'widgets.status');

// Push subscription endpoint (replace storage with DB later)
Route::post('/push/subscribe', function (\Illuminate\Http\Request $request) {
    $sub = $request->all();
    session(['push.subscription' => $sub]);
    return response()->json(['ok' => true]);
});
