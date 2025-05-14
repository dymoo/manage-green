<?php

namespace App\Filament\Resources\WalletResource\Pages;

use App\Filament\Resources\WalletResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWallets extends ListRecords
{
    protected static string $resource = WalletResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Create Wallet')
                ->using(function (array $data): \App\Models\Wallet {
                    // Create a new wallet or update if one already exists
                    return \App\Models\Wallet::updateOrCreate(
                        [
                            'tenant_id' => \Filament\Facades\Filament::getTenant()->id,
                            'user_id' => $data['user_id'],
                        ],
                        [
                            'balance' => 0,
                        ]
                    );
                }),
        ];
    }
} 