<?php

namespace App\Filament\Pages\Tenancy;

use App\Models\Tenant;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Tenancy\RegisterTenant as BaseRegisterTenant;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RegisterTenant extends BaseRegisterTenant
{
    public static function getLabel(): string
    {
        return 'Create a new club';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Club Setup')
                    ->tabs([
                        Tabs\Tab::make('Basic Information')
                            ->icon('heroicon-o-building-office')
                            ->schema([
                                Section::make()
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Club Name')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, $set) {
                                                if (is_string($state)) {
                                                    $set('slug', Str::slug($state));
                                                }
                                            }),
                                        TextInput::make('slug')
                                            ->label('Club URL')
                                            ->prefix(fn () => 'https://')
                                            ->suffix(fn () => '.' . config('app.domain', 'manage.green'))
                                            ->required()
                                            ->maxLength(255)
                                            ->unique(Tenant::class)
                                            ->rules(['alpha_dash'])
                                            ->helperText('This will be used for your club URL'),
                                        Grid::make(3)
                                            ->schema([
                                                Select::make('country')
                                                    ->options([
                                                        'de' => 'Germany',
                                                        'es' => 'Spain',
                                                        'nl' => 'Netherlands',
                                                        'pt' => 'Portugal',
                                                        'ch' => 'Switzerland',
                                                        'mt' => 'Malta',
                                                        'cz' => 'Czech Republic',
                                                        'fr' => 'France',
                                                        'it' => 'Italy',
                                                        'tr' => 'Turkey',
                                                        'uk' => 'United Kingdom',
                                                    ])
                                                    ->preload()
                                                    ->afterStateUpdated(function (string $state, $set) {
                                                        if ($state == 'uk') {
                                                            $set('currency', 'GBP');
                                                        } elseif ($state == 'ch') {
                                                            $set('currency', 'CHF');
                                                        } elseif ($state == 'tr') {
                                                            $set('currency', 'TRY');
                                                        } else {
                                                            $set('currency', 'EUR');
                                                        }
                                                    })
                                                    ->required(),
                                                Select::make('currency')
                                                    ->options([
                                                        'EUR' => 'Euro (€)',
                                                        'GBP' => 'British Pound (£)',
                                                        'CHF' => 'Swiss Franc (CHF)',
                                                        'TRY' => 'Turkish Lira (₺)',
                                                    ])
                                                    ->required()
                                                    ->default('EUR'),
                                                Select::make('timezone')
                                                    ->options([
                                                        'Europe/Amsterdam' => 'Amsterdam',
                                                        'Europe/Berlin' => 'Berlin',
                                                        'Europe/Madrid' => 'Madrid',
                                                        'Europe/Lisbon' => 'Lisbon',
                                                        'Europe/Zurich' => 'Zurich',
                                                        'Europe/Malta' => 'Malta',
                                                        'Europe/Prague' => 'Prague',
                                                        'Europe/Paris' => 'Paris',
                                                        'Europe/Rome' => 'Rome',
                                                        'Europe/Istanbul' => 'Istanbul',
                                                        'Europe/London' => 'London',
                                                    ])
                                                    ->required()
                                                    ->default('Europe/Amsterdam'),
                                            ]),
                                    ]),
                            ]),
                        Tabs\Tab::make('Owner Information')
                            ->icon('heroicon-o-user-circle')
                            ->schema([
                                Section::make('Owner Details')
                                    ->description('Create the primary admin user for this club.')
                                    ->schema([
                                        TextInput::make('owner_name')
                                            ->label('Full Name')
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('owner_email')
                                            ->label('Email Address')
                                            ->email()
                                            ->required()
                                            ->maxLength(255)
                                            ->unique(table: 'users', column: 'email'),
                                        TextInput::make('owner_password')
                                            ->label('Password')
                                            ->password()
                                            ->required()
                                            ->minLength(8)
                                            ->confirmed(),
                                        TextInput::make('owner_password_confirmation')
                                            ->label('Confirm Password')
                                            ->password()
                                            ->required(),
                                    ]),
                            ]),
                        Tabs\Tab::make('Club Details')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Section::make('Contact Information')
                                    ->schema([
                                        Group::make()
                                            ->schema([
                                                TextInput::make('address')
                                                    ->label('Street Address'),
                                                Grid::make(3)
                                                    ->schema([
                                                        TextInput::make('city')
                                                            ->label('City'),
                                                        TextInput::make('postal_code')
                                                            ->label('Postal Code'),
                                                    ]),
                                            ]),
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('phone')
                                                    ->tel()
                                                    ->label('Phone Number'),
                                                TextInput::make('email')
                                                    ->email()
                                                    ->label('Email Address'),
                                            ]),
                                    ]),
                                Section::make('Legal Information')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('registration_number')
                                                    ->label('Business Registration Number'),
                                                TextInput::make('vat_number')
                                                    ->label('VAT Number'),
                                            ]),
                                    ]),
                            ]),
                        Tabs\Tab::make('Branding')
                            ->icon('heroicon-o-paint-brush')
                            ->schema([
                                Section::make()
                                    ->schema([
                                        FileUpload::make('logo_path')
                                            ->label('Club Logo')
                                            ->image()
                                            ->directory('tenant-logos')
                                            ->maxSize(2048)
                                            ->helperText('Max file size: 2MB. Recommended dimensions: 400x400px.'),
                                        Grid::make(2)
                                            ->schema([
                                                ColorPicker::make('primary_color')
                                                    ->label('Primary Color')
                                                    ->default('#059669'), // emerald-600
                                                ColorPicker::make('secondary_color')
                                                    ->label('Secondary Color'),
                                            ]),
                                    ]),
                            ]),
                        Tabs\Tab::make('Domain Setup')
                            ->icon('heroicon-o-globe-alt')
                            ->schema([
                                Section::make()
                                    ->schema([
                                        Checkbox::make('use_custom_domain')
                                            ->label('Use Custom Domain')
                                            ->helperText('Use your own domain name instead of a subdomain')
                                            ->live(),
                                        TextInput::make('domain')
                                            ->label('Custom Domain')
                                            ->placeholder('yourclub.com')
                                            ->hidden(fn (Get $get) => !$get('use_custom_domain'))
                                            ->unique(Tenant::class, 'domain')
                                            ->helperText('You\'ll need to set up DNS records with your domain registrar'),
                                    ]),
                            ]),
                        Tabs\Tab::make('Features')
                            ->icon('heroicon-o-cog')
                            ->schema([
                                Section::make()
                                    ->schema([
                                        Checkbox::make('enable_wallet')
                                            ->label('Enable Member Wallets')
                                            ->default(true)
                                            ->helperText('Allow members to preload funds for purchases'),
                                        Checkbox::make('enable_inventory')
                                            ->label('Enable Inventory Management')
                                            ->default(true)
                                            ->helperText('Track product inventory and handle stock management'),
                                        Checkbox::make('enable_pos')
                                            ->label('Enable Point of Sale')
                                            ->default(true)
                                            ->helperText('Process sales and transactions'),
                                        Checkbox::make('enable_member_portal')
                                            ->label('Enable Member Portal')
                                            ->default(false)
                                            ->helperText('Allow members to access their account information online'),
                                    ]),
                            ]),
                    ])
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }

    protected function handleRegistration(array $data): Tenant
    {
        $tenantData = collect($data)->except(['owner_name', 'owner_email', 'owner_password', 'owner_password_confirmation'])->toArray();
        $ownerData = collect($data)->only(['owner_name', 'owner_email', 'owner_password'])->toArray();

        $tenant = Tenant::create($tenantData);

        // Create the owner user
        $owner = User::create([
            'name' => $ownerData['owner_name'],
            'email' => $ownerData['owner_email'],
            'password' => Hash::make($ownerData['owner_password']),
            // 'tenant_id' => $tenant->id, // Set the user's primary tenant context if your User model uses it directly
        ]);

        // Attach user to the tenant (many-to-many relationship)
        $tenant->users()->attach($owner);
        
        // Assign 'admin' role to the new owner within this tenant
        $owner->assignRole('admin', $tenant);
        
        // If the person registering is different from the owner being created,
        // you might want to attach them too, or handle that differently.
        // For now, assuming the form owner IS the main admin.
        // $tenant->users()->attach(auth()->user()); 
        // auth()->user()->assignRole('admin', $tenant); 

        // Set session flag for first-time tenant login
        session()->put('first_tenant_login', true);
        
        Notification::make()
            ->title('Club created successfully')
            ->body('Your club has been set up and is ready to use.')
            ->success()
            ->send();

        return $tenant;
    }

    protected function getRedirectUrl(): ?string
    {
        // Redirect to welcome page
        return route('filament.admin.pages.welcome', [
            'tenant' => $this->tenant->slug
        ]);
    }
} 