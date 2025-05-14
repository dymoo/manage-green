<?php

namespace App\Filament\Pages;

use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\HtmlString;
use Filament\Actions\Action;

class ClubWelcome extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-rocket-launch';
    
    protected static string $view = 'filament.pages.club-welcome';
    
    protected static ?string $title = 'Welcome to your club';
    
    protected static ?int $navigationSort = 1;
    
    protected ?string $heading = 'Welcome to your club!';
    
    protected ?string $subheading = 'Let\'s get you set up with everything you need.';
    
    protected static ?string $slug = 'welcome';
    
    protected static bool $shouldRegisterNavigation = false;

    public function mount(): void
    {
        $this->checkFirstLogin();
    }
    
    protected function checkFirstLogin(): void
    {
        // Only show this page for first-time tenant access
        // Subsequent visits should redirect to dashboard
        if (!session()->has('first_tenant_login')) {
            return;
        }
        
        // Remove the flag so it only shows once
        session()->forget('first_tenant_login');
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('go_to_dashboard')
                ->label('Go to Dashboard')
                ->url(fn () => route('filament.admin.pages.dashboard', ['tenant' => Filament::getTenant()->slug]))
                ->color('gray')
                ->outlined(),
            Action::make('setup_member_profile')
                ->label('Invite your first member')
                ->url(fn () => route('filament.admin.resources.users.create', ['tenant' => Filament::getTenant()->slug]))
                ->color('primary'),
        ];
    }
} 