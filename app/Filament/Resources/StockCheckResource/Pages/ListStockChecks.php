<?php

namespace App\Filament\Resources\StockCheckResource\Pages;

use App\Filament\Resources\StockCheckResource;
use App\Models\StockCheck;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListStockChecks extends ListRecords
{
    protected static string $resource = StockCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Start New Stock Check')
                ->modalHeading('Start New Stock Check'),
        ];
    }
    
    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()->latest();
    }
} 