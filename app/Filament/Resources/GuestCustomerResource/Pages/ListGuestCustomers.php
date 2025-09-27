<?php

namespace App\Filament\Resources\GuestCustomerResource\Pages;

use App\Filament\Resources\GuestCustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGuestCustomers extends ListRecords
{
    protected static string $resource = GuestCustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
