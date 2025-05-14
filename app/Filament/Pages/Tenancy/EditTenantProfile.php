<?php

namespace App\Filament\Pages\Tenancy;

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
use Filament\Pages\Tenancy\EditTenantProfile as BaseEditTenantProfile;
use Illuminate\Support\Str;

class EditTenantProfile extends BaseEditTenantProfile
{
    public static function getLabel(): string
    {
        return 'Club settings';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Club Settings')
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
                                            ->unique(ignoreRecord: true)
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
                                                    ->preload(),
                                                Select::make('currency')
                                                    ->options([
                                                        'EUR' => 'Euro (€)',
                                                        'GBP' => 'British Pound (£)',
                                                        'CHF' => 'Swiss Franc (CHF)',
                                                        'TRY' => 'Turkish Lira (₺)',
                                                    ]),
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
                                                    ]),
                                            ]),
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
                                                    ->label('Primary Color'),
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
                                            ->unique(ignoreRecord: true)
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
                                            ->helperText('Allow members to preload funds for purchases'),
                                        Checkbox::make('enable_inventory')
                                            ->label('Enable Inventory Management')
                                            ->helperText('Track product inventory and handle stock management'),
                                        Checkbox::make('enable_pos')
                                            ->label('Enable Point of Sale')
                                            ->helperText('Process sales and transactions'),
                                        Checkbox::make('enable_member_portal')
                                            ->label('Enable Member Portal')
                                            ->helperText('Allow members to access their account information online'),
                                    ]),
                            ]),
                    ])
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }
    
    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Club settings updated')
            ->body('Your club settings have been updated successfully.');
    }
} 