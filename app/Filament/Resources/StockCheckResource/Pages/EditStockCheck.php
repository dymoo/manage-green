<?php

namespace App\Filament\Resources\StockCheckResource\Pages;

use App\Filament\Resources\StockCheckResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditStockCheck extends EditRecord
{
    protected static string $resource = StockCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('continue_check')
                ->label('Continue Stock Check')
                ->url(fn () => StockCheckResource\Pages\StockCheckItems::getUrl(['record' => $this->record]))
                ->icon('heroicon-o-clipboard-document-list')
                ->button()
                ->color('primary')
                ->visible(fn () => $this->record->status !== 'completed'),
            Actions\Action::make('view_check')
                ->label('View Stock Check')
                ->url(fn () => StockCheckResource\Pages\StockCheckItems::getUrl(['record' => $this->record]))
                ->icon('heroicon-o-eye')
                ->button()
                ->visible(fn () => $this->record->status === 'completed'),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return StockCheckResource::getUrl('index');
    }
    
    protected function afterSave(): void
    {
        Notification::make()
            ->title('Stock check updated')
            ->success()
            ->send();
    }
    
    protected function beforeDelete(): void
    {
        // Check if the stock check has any items
        if ($this->record->stockCheckItems()->count() > 0) {
            Notification::make()
                ->title('Cannot delete stock check')
                ->body('This stock check has items and cannot be deleted.')
                ->danger()
                ->send();
                
            $this->halt();
        }
    }
} 