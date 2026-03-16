<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login as FilamentLogin;
use App\Filament\Pages\DocenteDashboard;
use App\Filament\Pages\ReadingSession;
use App\Filament\Resources\CourseResource;
use App\Filament\Resources\ReadingAttemptResource;
use App\Filament\Resources\ReadingPassageResource;
use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\StudentResource;
use App\Filament\Resources\UserResource;
use App\Filament\Widgets\ReadingStatsWidget;
use App\Filament\Widgets\RecentAttemptsWidget;
use App\Http\Middleware\EnsureIdpSessionIsAlive;
use App\Support\Institution\InstitutionTheme;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Enums\ThemeMode;
use Filament\Enums\UserMenuPosition;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
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
            ->id('app')
            ->path('app')
            ->login(FilamentLogin::class)
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->profile(null)
            ->defaultThemeMode(ThemeMode::System)
            ->sidebarCollapsibleOnDesktop()
            ->userMenu(position: UserMenuPosition::Sidebar)
            ->colors(InstitutionTheme::filamentColors())
            ->favicon(asset('images/favicon.png'))
            ->renderHook(
                'panels::body.end',
                fn (): \Illuminate\Contracts\View\View => view('filament.hooks.session-watchdog'),
            )
            ->resources([
                StudentResource::class,
                ReadingPassageResource::class,
                ReadingAttemptResource::class,
                CourseResource::class,
                UserResource::class,
                RoleResource::class,
            ])
            ->pages([
                DocenteDashboard::class,
                ReadingSession::class,
            ])
            ->widgets([
                ReadingStatsWidget::class,
                RecentAttemptsWidget::class,
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
            ])
            ->plugins([
                FilamentShieldPlugin::make()
                    ->navigationGroup('Configuración')
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
            ]);
    }
}
