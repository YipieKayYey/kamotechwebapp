<?php

namespace App\Filament\Resources\CustomerBookingResource\Pages;

use App\Filament\Resources\CustomerBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateCustomerBooking extends CreateRecord
{
    protected static string $resource = CustomerBookingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set created_by to current admin
        $data['created_by'] = auth()->id();
        
        // If no customer_id is selected (guest booking), set customer_id to admin
        if (empty($data['customer_id'])) {
            $data['customer_id'] = auth()->id();
        }
        
        return $data;
    }

    protected function afterCreate(): void
    {
        // Simple success notification
        Notification::make()
            ->title('Booking Created Successfully!')
            ->body('Customer booking has been created and saved.')
            ->success()
            ->send();
    }
}
