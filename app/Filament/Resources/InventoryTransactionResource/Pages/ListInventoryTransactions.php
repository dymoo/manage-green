<?php

namespace App\Filament\Resources\InventoryTransactionResource\Pages;

use App\Filament\Resources\InventoryTransactionResource;
use App\Filament\Resources\InventoryTransactionResource\Widgets\DailyDiscrepancyReport;
use Filament\Resources\Pages\ListRecords;

class ListInventoryTransactions extends ListRecords
{
    protected static string $resource = InventoryTransactionResource::class;
    
    protected function getHeaderWidgets(): array
    {
        return [
            DailyDiscrepancyReport::class,
        ];
    }
} 