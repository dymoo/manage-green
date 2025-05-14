<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use App\Filament\Resources\UserResource\RelationManagers\InventoryAdjustmentsRelationManager;
use Filament\Forms\Components\Component;
use Illuminate\Validation\Rule;
use Filament\Facades\Filament;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Spatie\Permission\Models\Role;
use App\Models\Tenant;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'User Management';

    // Define the relationship used to determine which users belong to a tenant
    protected static ?string $tenantOwnershipRelationshipName = 'tenant';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\DateTimePicker::make('email_verified_at'),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create'),
                Forms\Components\TextInput::make('fob_id')
                    ->label('FOB ID')
                    ->maxLength(255)
                    ->rule(function (Component $component) {
                        return Rule::unique('users', 'fob_id')
                            ->where('tenant_id', Filament::getTenant()->getKey())
                            ->ignore($component->getRecord()?->getKey());
                    }),
                Forms\Components\Select::make('member_status')
                    ->options([
                        'pending_approval' => 'Pending Approval',
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'rejected' => 'Rejected',
                        'banned' => 'Banned',
                    ])
                    ->visible(fn (string $context): bool => $context === 'edit'),
                Forms\Components\TextInput::make('wallet_balance')
                    ->label('Wallet Balance')
                    ->prefix(fn () => \Filament\Facades\Filament::getTenant()->currency ?? '€')
                    ->disabled()
                    ->dehydrated(false)
                    ->formatStateUsing(function ($record) {
                        $wallet = $record?->wallet;
                        return $wallet ? $wallet->balance : '0.00';
                    })
                    ->visible(fn () => \Filament\Facades\Filament::getTenant()?->enable_wallet ?? false)
                    ->helperText('Manage wallet through the Wallet tab below'),
                Forms\Components\Select::make('roles')
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->preload(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'admin' => 'warning',
                        'staff' => 'success',
                        'member' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\TextEntry::make('name'),
                Infolists\Components\TextEntry::make('email'),
                Infolists\Components\TextEntry::make('wallet_balance_display')
                    ->label('Wallet Balance')
                    ->state(function ($record) {
                        return $record?->wallet ? number_format($record->wallet->balance, 2) : 'N/A';
                    })
                    ->visible(fn ($record) => Filament::getTenant()?->enable_wallet && $record?->wallet),
                Infolists\Components\TextEntry::make('roles.name')
                    ->label('Roles')
                    ->badge(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\WalletsRelationManager::class,
            RelationManagers\InventoryAdjustmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getStaffFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('email')
                ->email()
                ->required()
                ->maxLength(255)
                ->label('Email Address'),
        ];
    }
}
