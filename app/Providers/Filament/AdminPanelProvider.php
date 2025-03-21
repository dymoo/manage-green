<?php

namespace App\Providers\Filament;

use App\Filament\Pages\TenantsOverview;
use App\Filament\Pages\Tenancy\EditTenantProfile;
use App\Filament\Pages\Tenancy\RegisterTenant;
use App\Models\Tenant;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
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
            ->login()
            ->colors([
                'primary' => Color::Green,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
                TenantsOverview::class,
            ])
            ->navigationItems([
                NavigationItem::make('Tenants Management')
                    ->url(fn (): string => TenantsOverview::getUrl())
                    ->icon('heroicon-o-building-library')
                    ->activeIcon('heroicon-s-building-library')
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.pages.tenants-overview'))
                    ->sort(2)
                    // Only show this navigation item to super_admin users
                    ->visible(fn (): bool => auth()->user()?->hasRole('super_admin') ?? false),
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->tenant(
                Tenant::class,
                ownershipRelationship: 'users',
                slugAttribute: 'slug'
            )
            ->tenantRegistration(
                RegisterTenant::class
            )
            ->tenantProfile(
                EditTenantProfile::class
            )
            ->tenantMenuItems([
                MenuItem::make()
                    ->label('Dashboard')
                    ->icon('heroicon-o-home')
                    ->url(fn (): string => Pages\Dashboard::getUrl()),
            ])
            ->tenantMenu(function () {
                return true;
            });
    }
}
