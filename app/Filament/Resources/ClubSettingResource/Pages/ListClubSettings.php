<?php

namespace App\Filament\Resources\ClubSettingResource\Pages;

use App\Filament\Resources\ClubSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClubSettings extends ListRecords
{
    protected static string $resource = ClubSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
