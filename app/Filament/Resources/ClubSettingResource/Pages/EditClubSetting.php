<?php

namespace App\Filament\Resources\ClubSettingResource\Pages;

use App\Filament\Resources\ClubSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClubSetting extends EditRecord
{
    protected static string $resource = ClubSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
