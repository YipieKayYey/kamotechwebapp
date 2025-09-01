<?php

namespace App\Filament\Technician\Resources\MyEarningsResource\Pages;

use App\Filament\Technician\Resources\MyEarningsResource;
use App\Filament\Technician\Widgets\MyEarningsStatsWidget;
use Filament\Resources\Pages\ListRecords;

class ListMyEarnings extends ListRecords
{
    protected static string $resource = MyEarningsResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            MyEarningsStatsWidget::class,
        ];
    }
}
