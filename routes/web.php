<?php

use App\Http\Controllers\Auth\SsoController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/planes', [HomeController::class, 'planes'])->name('planes');
Route::get('/plan/{id}', [HomeController::class, 'plandetail'])->name('plan');
Route::get('/subject/{id}', [HomeController::class, 'subjectdetail'])->name('subject');
Route::get('/centers', [HomeController::class, 'centers'])->name('centers');
Route::get('/center/{id}', [HomeController::class, 'centerdetail'])->name('center');

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
