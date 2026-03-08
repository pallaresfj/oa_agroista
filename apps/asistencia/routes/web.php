<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Auth\SsoController;

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
    Route::get('/dashboard', function () {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->isSoporte()) {
            return redirect('/app/soporte-dashboard');
        }

        if ($user->isDirectivo()) {
            return redirect('/app/directivo-dashboard');
        }

        return redirect('/app/docente-dashboard');
    })->name('dashboard');

    // Redirect classic profile route to Filament profile
    Route::get('/profile', function () {
        return redirect('/app');
    })->name('profile.edit');
});

Route::get('/media/{path}', function (string $path) {
    if (str_contains($path, '..')) {
        abort(404);
    }

    $disk = Storage::disk('public');

    if (! $disk->exists($path)) {
        abort(404);
    }

    return response()->file($disk->path($path));
})->where('path', '.*')->name('media.public');

// Docente routes - Redirect to Filament panel
Route::middleware(['auth', 'role:docente,directivo'])->prefix('docente')->name('docente.')->group(function () {
    Route::get('/dashboard', function () {
        return redirect('/app/docente-dashboard');
    })->name('dashboard');
    Route::get('/scanner', function () {
        return redirect('/app/docente-dashboard');
    })->name('scanner');
});

// Directivo routes - Redirect to Filament panel
Route::middleware(['auth', 'role:directivo'])->prefix('directivo')->name('directivo.')->group(function () {
    Route::get('/dashboard', function () {
        return redirect('/app/directivo-dashboard');
    })->name('dashboard');
    Route::get('/reports', function () {
        return redirect('/app/directivo-dashboard');
    })->name('reports');
});

// API-like routes for attendance
Route::middleware(['auth', 'role:docente,directivo'])->prefix('attendance')->name('attendance.')->group(function () {
    Route::post('/register', function () {
        // Handled by Livewire component
        return response()->json(['message' => 'Use Livewire component']);
    })->name('register');
});
