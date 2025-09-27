<?php

namespace App\Filament\Resources\GuestCustomerResource\Pages;

use App\Filament\Resources\GuestCustomerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGuestCustomer extends CreateRecord
{
    protected static string $resource = GuestCustomerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
