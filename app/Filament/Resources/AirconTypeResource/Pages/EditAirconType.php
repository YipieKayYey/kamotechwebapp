<?php

namespace App\Filament\Resources\AirconTypeResource\Pages;

use App\Filament\Resources\AirconTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAirconType extends EditRecord
{
    protected static string $resource = AirconTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            // Delete action disabled as per panelist requirement
        ];
    }
}