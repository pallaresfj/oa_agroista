<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login as FilamentLogin;
use App\Filament\Pages\DirectivoDashboard;
use App\Filament\Pages\DocenteDashboard;
use App\Filament\Pages\SoporteDashboard;
use App\Http\Middleware\EnsureIdpSessionIsAlive;
use App\Support\Institution\InstitutionTheme;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Enums\UserMenuPosition;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Assets\Css;
use Illuminate\Support\Facades\Vite;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('app')
            ->path('app')
            ->login(FilamentLogin::class)
            ->profile(null)
            ->userMenu(position: UserMenuPosition::Sidebar)
            ->colors(InstitutionTheme::filamentColors())
            ->favicon(asset('images/favicon.png'))
            ->renderHook(
                'panels::head.end',
                fn () => view('filament.hooks.mobile-actions-styles'),
            )
            ->renderHook(
                'panels::body.end',
                fn (): \Illuminate\Contracts\View\View => view('filament.hooks.session-watchdog'),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                // Dashboards personalizados según rol
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                // Widgets personalizados descubiertos automáticamente
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                EnsureIdpSessionIsAlive::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
