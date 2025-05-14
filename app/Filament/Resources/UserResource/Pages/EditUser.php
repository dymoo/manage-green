<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\DB;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('addFunds')
                ->label('Add Funds')
                ->form([
                    TextInput::make('amount')
                        ->label('Amount to Add')
                        ->numeric()
                        ->required()
                        ->minValue(0.01) // Or your desired minimum
                        ->prefix(\Filament\Facades\Filament::getTenant()?->currency ?? '€'),
                ])
                ->action(function (array $data) {
                    $user = $this->record;
                    $amount = $data['amount'];
                    $actingUser = auth()->user(); // Get the currently authenticated user (admin/staff)

                    DB::transaction(function () use ($user, $amount, $actingUser) {
                        $user->ensureWalletExists();
                        $user->wallet->deposit($amount, ['staff_id' => $actingUser->id]);
                    });

                    // $user->refresh(); // We determined these didn't fix the snapshot issue
                    // $this->fillForm(); 

                    Notification::make()
                        ->title('Funds added successfully')
                        ->success()
                        ->send();
                })
                ->visible(fn (): bool => auth()->user()->hasAnyRole(['admin', 'staff'], \Filament\Facades\Filament::getTenant())),

            Actions\DeleteAction::make(),
        ];
    }
}
