<?php

namespace App\Filament\Resources\ServicePricingResource\Pages;

use App\Filament\Resources\ServicePricingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServicePricings extends ListRecords
{
    protected static string $resource = ServicePricingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
