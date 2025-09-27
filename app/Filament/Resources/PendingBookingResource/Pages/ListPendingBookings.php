<?php

namespace App\Filament\Resources\PendingBookingResource\Pages;

use App\Filament\Resources\PendingBookingResource;
use Filament\Resources\Pages\ListRecords;

class ListPendingBookings extends ListRecords
{
    protected static string $resource = PendingBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action for pending bookings - they come from customers
        ];
    }

    public function getTitle(): string
    {
        return 'Pending Bookings';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Could add widgets here for pending booking stats
        ];
    }
}
