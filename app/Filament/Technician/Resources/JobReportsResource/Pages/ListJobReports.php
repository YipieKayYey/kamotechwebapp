<?php

namespace App\Filament\Technician\Resources\JobReportsResource\Pages;

use App\Filament\Technician\Resources\JobReportsResource;
use App\Filament\Technician\Widgets\JobReportsStatsWidget;
use Filament\Resources\Pages\ListRecords;

class ListJobReports extends ListRecords
{
    protected static string $resource = JobReportsResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            JobReportsStatsWidget::class,
        ];
    }
}
