<?php

namespace App\Filament\Technician\Resources\JobRecordsResource\Pages;

use App\Filament\Technician\Resources\JobRecordsResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewJobRecord extends ViewRecord
{
    protected static string $resource = JobRecordsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No actions for viewing job records
        ];
    }
}
