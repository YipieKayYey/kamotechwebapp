<?php

namespace App\Filament\Technician\Resources\MyJobsResource\Pages;

use App\Filament\Technician\Resources\MyJobsResource;
use Filament\Resources\Pages\ViewRecord;

class ViewMyJob extends ViewRecord
{
    protected static string $resource = MyJobsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Add actions like call customer, get directions, etc.
        ];
    }
}
