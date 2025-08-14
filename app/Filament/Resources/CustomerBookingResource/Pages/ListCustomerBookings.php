<?php

namespace App\Filament\Resources\CustomerBookingResource\Pages;

use App\Filament\Resources\CustomerBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomerBookings extends ListRecords
{
    protected static string $resource = CustomerBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
