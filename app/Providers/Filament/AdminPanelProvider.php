<?php

namespace App\Providers\Filament;

use App\Filament\Pages\TenantsOverview;
use App\Filament\Pages\Tenancy\EditTenantProfile;
use App\Filament\Pages\Tenancy\RegisterTenant;
use App\Filament\Pages\Tenancy\ClubSettings;
use App\Filament\Pages\ClubWelcome;
use App\Filament\Widgets\InventoryOverview;
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
use Illuminate\Support\Facades\Storage;
use Filament\Facades\Filament;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors(function () {
                /** @var Tenant|null $tenant */
                $tenant = Filament::getTenant();
                $primaryColor = $tenant?->primary_color ?? '#10b981'; // Default to emerald-600

                try {
                    // Attempt to parse the color. Handle potential errors.
                    $parsedColor = Color::hex($primaryColor);
                } catch (\Exception $e) {
                    // Log the error or handle it gracefully
                    // Fallback to default if parsing fails
                    $parsedColor = Color::Emerald;
                }
                
                return [
                    'primary' => $parsedColor,
                    // You could potentially add secondary colors here too
                    // 'secondary' => Color::hex($tenant?->secondary_color ?? '#default_secondary')
                ];
            })
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverPages(in: app_path('Filament/Tenant/Pages'), for: 'App\\Filament\\Tenant\\Pages')
            ->pages([
                Pages\Dashboard::class,
                ClubWelcome::class,
                ClubSettings::class,
            ])
            ->navigationItems([
                NavigationItem::make('Tenants')
                    ->url(fn (): string => TenantsOverview::getUrl())
                    ->icon('heroicon-o-building-library')
                    ->activeIcon('heroicon-s-building-library')
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.pages.tenants-overview'))
                    ->sort(2)
                    ->visible(fn (): bool => auth()->user()?->hasRole('super_admin') ?? false),
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                InventoryOverview::class,
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
            ->tenant(Tenant::class, ownershipRelationship: 'users')
            ->tenantRegistration(RegisterTenant::class)
            ->tenantProfile(EditTenantProfile::class)
            ->tenantMenuItems([
                MenuItem::make()
                    ->label('Dashboard')
                    ->icon('heroicon-o-home')
                    ->url(fn (): string => Pages\Dashboard::getUrl()),
                MenuItem::make()
                    ->label('Club Settings')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->url(fn (): string => ClubSettings::getUrl())
                    ->visible(fn (): bool => auth()->user()?->hasAnyRole(['admin', 'super_admin']) ?? false),
            ])
            ->tenantMenu(true)
            ->login()
            ->registration(false)
            ->passwordReset()
            ->brandLogo(function () {
                /** @var Tenant|null $tenant */
                $tenant = Filament::getTenant();
                if ($tenant && $tenant->logo_path && Storage::disk('public')->exists($tenant->logo_path)) {
                    return Storage::disk('public')->url($tenant->logo_path);
                }
                // Return default manage.green logo URL or path
                return asset('img/logo.svg'); // Example default logo
            });
    }
}
