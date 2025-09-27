<?php

namespace App\Filament\Technician\Widgets;

use App\Models\Booking;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class TechnicianStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getColumns(): int
    {
        return 2;
    }

    protected function getStats(): array
    {
        $user = Auth::user();
        $technician = $user->technician;

        if (! $technician) {
            return [
                BaseWidget\Stat::make('Error', 'No technician profile found')
                    ->color('danger'),
            ];
        }

        // Today's jobs
        $todayJobs = Booking::where('technician_id', $technician->id)
            ->whereDate('scheduled_start_at', today())
            ->whereIn('status', ['confirmed', 'in_progress'])
            ->count();

        // Pending tasks (jobs requiring action)
        $pendingTasks = Booking::where('technician_id', $technician->id)
            ->where('status', 'confirmed')
            ->count();

        return [
            BaseWidget\Stat::make('Today\'s Jobs', $todayJobs)
                ->description('Scheduled for today')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($todayJobs > 0 ? 'success' : 'gray'),

            BaseWidget\Stat::make('Pending Tasks', $pendingTasks)
                ->description('Jobs ready to start')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingTasks > 0 ? 'warning' : 'success'),
        ];
    }
}
