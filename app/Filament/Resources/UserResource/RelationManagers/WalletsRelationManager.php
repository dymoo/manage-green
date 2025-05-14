<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\Action;
use Filament\Facades\Filament;

class WalletsRelationManager extends RelationManager
{
    protected static string $relationship = 'wallets';

    protected static ?string $recordTitleAttribute = 'id';
    
    protected static ?string $title = 'Wallet';
    
    public static function canViewForRecord($record, $context = null): bool
    {
        $tenant = Filament::getTenant();
        return $tenant && $tenant->enable_wallet;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('balance')
                    ->label('Current Balance')
                    ->prefix(fn () => Filament::getTenant()->currency ?? '€')
                    ->disabled()
                    ->numeric()
                    ->step(0.01),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Club')
                    ->sortable(),
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
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Create Wallet')
                    ->visible(fn(): bool => !$this->getOwnerRecord()->wallet)
                    ->mutateFormDataUsing(function (array $data) {
                        $data['tenant_id'] = Filament::getTenant()->id;
                        $data['user_id'] = $this->getOwnerRecord()->id;
                        $data['balance'] = 0;
                        
                        return $data;
                    }),
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
                    ->action(function ($record, array $data) {
                        $record->deposit(
                            amount: $data['amount'],
                            attributes: [
                                'payment_method' => $data['payment_method'],
                                'staff_id' => auth()->id(),
                                'reference' => $data['reference'] ?? null,
                                'notes' => $data['notes'] ?? null,
                            ]
                        );
                    }),
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => \Filament\Resources\Pages\Page::getResource('App\\Filament\\Resources\\WalletResource')::getUrl('view', ['record' => $record])),
            ])
            ->bulkActions([
                //
            ]);
    }
} 