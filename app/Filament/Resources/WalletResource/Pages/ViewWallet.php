<?php

namespace App\Filament\Resources\WalletResource\Pages;

use App\Filament\Resources\WalletResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms;
use Filament\Facades\Filament;

class ViewWallet extends ViewRecord
{
    protected static string $resource = WalletResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
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
                ->action(function (array $data) {
                    $this->record->deposit(
                        amount: $data['amount'],
                        attributes: [
                            'payment_method' => $data['payment_method'],
                            'staff_id' => auth()->id(),
                            'reference' => $data['reference'] ?? null,
                            'notes' => $data['notes'] ?? null,
                        ]
                    );
                    
                    $this->refreshFormData(['balance']);
                    
                    $this->notify('success', 'Funds added successfully');
                }),
        ];
    }
} 