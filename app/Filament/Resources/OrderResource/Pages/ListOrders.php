<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Create Order'),
                
            Actions\Action::make('quickSale')
                ->label('Quick Sale')
                ->icon('heroicon-o-shopping-cart')
                ->color('success')
                ->url(fn (): string => '/admin/point-of-sale'),
        ];
    }
}
