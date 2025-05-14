<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenantResource\Pages;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    
    protected static ?string $navigationGroup = 'System';
    
    protected static ?int $navigationSort = 100;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('super_admin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Club Management')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Basic Information')
                            ->icon('heroicon-o-building-office')
                            ->schema([
                                Forms\Components\Section::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Club Name')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (string $state, $set) {
                                                $set('slug', Str::slug($state));
                                            }),
                                        Forms\Components\TextInput::make('slug')
                                            ->label('Club URL')
                                            ->prefix(fn () => 'https://')
                                            ->suffix(fn () => '.' . config('app.domain', 'manage.green'))
                                            ->required()
                                            ->maxLength(255)
                                            ->unique(ignoreRecord: true)
                                            ->rules(['alpha_dash'])
                                            ->helperText('This will be used for the club URL'),
                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\Select::make('country')
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
                                                Forms\Components\Select::make('currency')
                                                    ->options([
                                                        'EUR' => 'Euro (€)',
                                                        'GBP' => 'British Pound (£)',
                                                        'CHF' => 'Swiss Franc (CHF)',
                                                        'TRY' => 'Turkish Lira (₺)',
                                                    ]),
                                                Forms\Components\Select::make('timezone')
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
                        Forms\Components\Tabs\Tab::make('Club Details')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\Section::make('Contact Information')
                                    ->schema([
                                        Forms\Components\TextInput::make('address')
                                            ->label('Street Address'),
                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('city')
                                                    ->label('City'),
                                                Forms\Components\TextInput::make('postal_code')
                                                    ->label('Postal Code'),
                                            ]),
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('phone')
                                                    ->tel()
                                                    ->label('Phone Number'),
                                                Forms\Components\TextInput::make('email')
                                                    ->email()
                                                    ->label('Email Address'),
                                            ]),
                                    ]),
                                Forms\Components\Section::make('Legal Information')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('registration_number')
                                                    ->label('Business Registration Number'),
                                                Forms\Components\TextInput::make('vat_number')
                                                    ->label('VAT Number'),
                                            ]),
                                    ]),
                            ]),
                        Forms\Components\Tabs\Tab::make('Branding')
                            ->icon('heroicon-o-paint-brush')
                            ->schema([
                                Forms\Components\Section::make()
                                    ->schema([
                                        FileUpload::make('logo_path')
                                            ->label('Logo')
                                            ->image()
                                            ->disk('public')
                                            ->directory(fn ($record) => $record ? 'tenants/' . $record->id . '/branding' : 'tenants/temp/branding')
                                            ->visibility('public')
                                            ->nullable(),
                                        ColorPicker::make('primary_color')
                                            ->label('Primary Color')
                                            ->nullable(),
                                    ]),
                            ]),
                        Forms\Components\Tabs\Tab::make('Domain Setup')
                            ->icon('heroicon-o-globe-alt')
                            ->schema([
                                Forms\Components\Section::make()
                                    ->schema([
                                        Forms\Components\Checkbox::make('use_custom_domain')
                                            ->label('Use Custom Domain')
                                            ->helperText('Use own domain name instead of a subdomain')
                                            ->live(),
                                        Forms\Components\TextInput::make('domain')
                                            ->label('Custom Domain')
                                            ->placeholder('yourclub.com')
                                            ->hidden(fn (Forms\Get $get) => !$get('use_custom_domain'))
                                            ->unique(ignoreRecord: true),
                                    ]),
                            ]),
                        Forms\Components\Tabs\Tab::make('Features')
                            ->icon('heroicon-o-cog')
                            ->schema([
                                Forms\Components\Section::make()
                                    ->schema([
                                        Forms\Components\Checkbox::make('enable_wallet')
                                            ->label('Enable Member Wallets')
                                            ->helperText('Allow members to preload funds for purchases'),
                                        Forms\Components\Checkbox::make('enable_inventory')
                                            ->label('Enable Inventory Management')
                                            ->helperText('Track product inventory and handle stock management'),
                                        Forms\Components\Checkbox::make('enable_pos')
                                            ->label('Enable Point of Sale')
                                            ->helperText('Process sales and transactions'),
                                        Forms\Components\Checkbox::make('enable_member_portal')
                                            ->label('Enable Member Portal')
                                            ->helperText('Allow members to access their account information online'),
                                    ]),
                            ]),
                    ])
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('country')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\ImageColumn::make('logo_path')
                    ->label('Logo')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Members')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('enable_wallet')
                    ->boolean()
                    ->label('Wallet')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('enable_inventory')
                    ->boolean()
                    ->label('Inventory')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('enable_pos')
                    ->boolean()
                    ->label('POS')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
} 