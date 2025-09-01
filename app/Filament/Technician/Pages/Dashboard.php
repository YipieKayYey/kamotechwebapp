<?php

namespace App\Filament\Technician\Pages;

use App\Filament\Technician\Widgets\TechnicianStatsWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    public function getWidgets(): array
    {
        return [
            TechnicianStatsWidget::class,
        ];
    }
}
