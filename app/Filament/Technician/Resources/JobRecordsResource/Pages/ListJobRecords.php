<?php

namespace App\Filament\Technician\Resources\JobRecordsResource\Pages;

use App\Filament\Technician\Resources\JobRecordsResource;
use Filament\Resources\Pages\ListRecords;

class ListJobRecords extends ListRecords
{
    protected static string $resource = JobRecordsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action for job records
        ];
    }
}
