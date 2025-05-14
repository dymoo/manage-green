<?php

namespace App\Filament\Resources\StockCheckResource\Pages;

use App\Filament\Resources\StockCheckResource;
use App\Models\StockCheck;
use App\Enums\StockCheckType;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateStockCheck extends CreateRecord
{
    protected static string $resource = StockCheckResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // These are no longer in the form to be unset
        // unset($data['check_in_at']);
        // unset($data['check_out_at']);
        // unset($data['checked_out_by']);

        // If creating a CHECK_IN, ensure no active CHECK_IN exists for this staff today.
        if ($data['type'] === StockCheckType::CHECK_IN->value) {
            $existingCheckIn = StockCheck::where('tenant_id', tenant()->id)
                ->where('staff_id', auth()->id()) // Check for current staff
                ->where('type', StockCheckType::CHECK_IN)
                ->whereNull('check_out_at') // Not yet checked out (i.e., active)
                ->whereDate('check_in_at', today()) // Created today
                ->first();
            
            if ($existingCheckIn) {
                Notification::make()
                    ->title('Active Check-In Exists')
                    ->body('You already have an active stock check-in for today. Please complete or void it before starting a new one.')
                    ->danger()
                    ->send();
                $this->halt();
            }
        }
        // Add logic for CHECK_OUT if it were creatable directly (it shouldn't be)
        
        return $data;
    }
    
    protected function getRedirectUrl(): string
    {
        // Redirect to the items page for the newly created stock check record
        return StockCheckResource\Pages\StockCheckItems::getUrl(['record' => $this->record->id]);
    }
    
    protected function afterCreate(): void
    {
        // Status column removed, so this update is no longer needed/valid
        // $this->record->update(['status' => 'in_progress']);
        
        Notification::make()
            ->title('Stock Check Started') // Title changed to reflect start
            ->body('You can now start adding items to your stock check.')
            ->success()
            ->send();
    }
} 