<?php

namespace App\Filament\Technician\Resources\MyJobsResource\Pages;

use App\Filament\Technician\Resources\MyJobsResource;
use Filament\Resources\Pages\ListRecords;

class ListMyJobs extends ListRecords
{
    protected static string $resource = MyJobsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Technicians cannot create jobs
        ];
    }
}
