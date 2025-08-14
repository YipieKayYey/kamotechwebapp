<?php

namespace App\Filament\Resources\ServicePricingResource\Pages;

use App\Filament\Resources\ServicePricingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServicePricing extends EditRecord
{
    protected static string $resource = ServicePricingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
