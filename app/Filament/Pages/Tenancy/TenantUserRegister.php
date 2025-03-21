<?php

namespace App\Filament\Pages\Tenancy;

use App\Models\Tenant;
use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class TenantUserRegister extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    protected static string $view = 'filament.pages.tenancy.tenant-user-register';
    protected static ?string $title = 'Register';
    protected static bool $shouldRegisterNavigation = false;
    
    public ?array $data = [];
    public Tenant $tenant;
    
    public function mount(string $tenant): void
    {
        $this->tenant = Tenant::where('slug', $tenant)->firstOrFail();
        
        if (auth()->check()) {
            // If the user is logged in and has access to this tenant, redirect them
            $user = auth()->user();
            
            if ($user->tenants()->where('tenants.id', $this->tenant->id)->exists()) {
                redirect()->to("/admin/tenants/{$this->tenant->slug}")->send();
            }
        }
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email address')
                    ->required()
                    ->email()
                    ->unique(User::class),
                TextInput::make('password')
                    ->label('Password')
                    ->required()
                    ->password()
                    ->rule(Password::default())
                    ->same('passwordConfirmation'),
                TextInput::make('passwordConfirmation')
                    ->label('Confirm password')
                    ->required()
                    ->password()
                    ->dehydrated(false),
            ])
            ->statePath('data');
    }
    
    public function register(): void
    {
        $data = $this->form->getState();
        
        try {
            $user = User::registerUser([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ], $this->tenant);
            
            auth()->login($user);
            
            // Redirect to tenant dashboard or another appropriate page
            redirect()->to("/admin/tenants/{$this->tenant->slug}")->send();
        } catch (Halt $exception) {
            return;
        }
    }
    
    public static function getSlug(): string
    {
        return 'register';
    }
} 