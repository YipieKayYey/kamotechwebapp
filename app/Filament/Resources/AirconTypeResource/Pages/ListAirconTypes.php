<?php

namespace App\Filament\Resources\AirconTypeResource\Pages;

use App\Filament\Resources\AirconTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAirconTypes extends ListRecords
{
    protected static string $resource = AirconTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}