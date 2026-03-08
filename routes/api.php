<?php

use App\Http\Controllers\Api\EcosystemAppsController;
use App\Http\Controllers\Api\EcosystemInstitutionController;
use Illuminate\Support\Facades\Route;

Route::prefix('ecosystem')->group(function (): void {
    Route::middleware(['passport.scope:ecosystem.read'])->group(function (): void {
        Route::get('/institution', [EcosystemInstitutionController::class, 'show']);
        Route::get('/apps', [EcosystemAppsController::class, 'index']);
    });

    Route::put('/institution', [EcosystemInstitutionController::class, 'update'])
        ->middleware(['auth:api', 'passport.scope:ecosystem.write']);
});
