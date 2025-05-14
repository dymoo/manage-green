<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WalletResource\Pages;
use App\Filament\Resources\WalletResource\RelationManagers;
use App\Models\Wallet;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Facades\Filament;

class WalletResource extends Resource
{
    protected static ?string $model = Wallet::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    
    protected static ?string $navigationGroup = 'Member Management';
    
    protected static ?string $navigationLabel = 'Member Wallets';
    
    protected static ?int $navigationSort = 3;
    
    // Define the relationship used to determine which wallets belong to a tenant
    protected static ?string $tenantOwnershipRelationshipName = 'tenant';
    
    public static function getNavigationBadge(): ?string
    {
        $tenant = Filament::getTenant();
        
        if (!$tenant || !$tenant->enable_wallet) {
            return null;
        }
        
        return static::getEloquentQuery()->count();
    }
    
    public static function shouldRegisterNavigation(): bool
    {
        $tenant = Filament::getTenant();
        return $tenant && $tenant->enable_wallet;
    }
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Member')
                    ->relationship(
                        'user',
                        'name',
                        fn (Builder $query) => $query->whereHas('roles', fn ($q) => $q->where('name', 'user'))
                    )
                    ->searchable()
                    ->required(),
                Forms\Components\TextInput::make('balance')
                    ->label('Current Balance')
                    ->prefix(fn () => Filament::getTenant()->currency ?? '€')
                    ->disabled()
                    ->numeric()
                    ->step(0.01),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Member')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.fob_id')
                    ->label('FOB ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Current Balance')
                    ->money(fn () => Filament::getTenant()->currency ?? 'EUR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('add_funds')
                    ->label('Add Funds')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount')
                            ->prefix(fn () => Filament::getTenant()->currency ?? '€')
                            ->numeric()
                            ->minValue(0.01)
                            ->step(0.01)
                            ->required(),
                        Forms\Components\Select::make('payment_method')
                            ->label('Payment Method')
                            ->options([
                                'cash' => 'Cash',
                                'card' => 'Credit/Debit Card',
                                'transfer' => 'Bank Transfer',
                                'other' => 'Other',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('reference')
                            ->label('Reference/Receipt #')
                            ->placeholder('Optional reference number'),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->placeholder('Any additional notes')
                            ->rows(2),
                    ])
                    ->action(function (Wallet $wallet, array $data) {
                        $wallet->deposit(
                            amount: $data['amount'],
                            attributes: [
                                'payment_method' => $data['payment_method'],
                                'staff_id' => auth()->id(),
                                'reference' => $data['reference'] ?? null,
                                'notes' => $data['notes'] ?? null,
                            ]
                        );
                        
                        return redirect()->back();
                    }),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }
    
    public static function getRelations(): array
    {
        return [
            RelationManagers\TransactionsRelationManager::class,
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWallets::route('/'),
            'view' => Pages\ViewWallet::route('/{record}'),
        ];
    }
} 