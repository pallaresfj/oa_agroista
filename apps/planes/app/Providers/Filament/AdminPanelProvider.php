<?php

namespace App\Providers\Filament;

use App\Filament\Pages\AdminDashboard;
use Illuminate\Contracts\View\View;
use App\Filament\AvatarProviders\CustomAvatarProvider;
use App\Http\Middleware\EnsureIdpSessionIsAlive;
use App\Filament\Pages\Auth\Login as FilamentLogin;
use App\Support\Institution\InstitutionTheme;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(FilamentLogin::class)
            ->profile(null)
            ->sidebarCollapsibleOnDesktop()
            ->colors(InstitutionTheme::filamentColors())
            ->font('Poppins')
            ->favicon(asset('images/favicon.png'))
            ->brandLogoHeight('2rem')
            ->renderHook(
                PanelsRenderHook::SIDEBAR_FOOTER,
                fn (): View => view('filament.hooks.sidebar-user-menu'),
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): View => view('filament.hooks.sidebar-user-menu-styles'),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                AdminDashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->navigationGroups([
                NavigationGroup::make()
                     ->label('Centros de interés')
                     ->icon('heroicon-o-user-group'),
                NavigationGroup::make()
                    ->label('Planes de área')
                    ->icon('heroicon-o-clipboard-document-list'),
                NavigationGroup::make()
                    ->label('Configuraciones')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsed(),
            ])
            ->defaultAvatarProvider(CustomAvatarProvider::class)
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
            ->plugins([
                FilamentShieldPlugin::make()
                    ->gridColumns([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 3,
                    ])
                    ->sectionColumnSpan(1)
                    ->checkboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 4,
                    ])
                    ->resourceCheckboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                    ]),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
