<?php

use App\Http\Controllers\Auth\SsoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Authentication routes redirect to Filament login
Route::get('/login', function () {
    return redirect('/app/login');
})->name('login');

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
})->name('logout');

Route::middleware('guest')->group(function (): void {
    Route::get('/sso/login', [SsoController::class, 'login'])->name('sso.login');
});

Route::get('/sso/callback', [SsoController::class, 'callback'])->name('sso.callback');

Route::middleware('auth')->group(function (): void {
    Route::get('/sso/session-check/start', [SsoController::class, 'startSessionCheck'])->name('sso.session-check.start');
    Route::get('/sso/session-check/callback', [SsoController::class, 'completeSessionCheck'])->name('sso.session-check.callback');
});

Route::get('/sso/frontchannel-logout', [SsoController::class, 'frontchannelLogout'])
    ->name('sso.frontchannel-logout');

// Dashboard redirect based on role
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', fn () => redirect('/app/dashboard'))->name('dashboard');

    // Redirect classic profile route to Filament profile
    Route::get('/profile', function () {
        return redirect('/app');
    })->name('profile.edit');
});
