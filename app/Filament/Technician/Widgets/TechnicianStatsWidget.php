<?php

namespace App\Filament\Technician\Widgets;

use App\Models\Booking;
use App\Models\Earning;
use App\Models\RatingReview;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class TechnicianStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $user = Auth::user();
        $technician = $user->technician;

        if (!$technician) {
            return [
                BaseWidget\Stat::make('Error', 'No technician profile found')
                    ->color('danger'),
            ];
        }

        // Today's jobs
        $todayJobs = Booking::where('technician_id', $technician->id)
            ->whereDate('scheduled_date', today())
            ->whereIn('status', ['confirmed', 'in_progress'])
            ->count();

        // This week's earnings (include all statuses for better visibility)
        $weeklyEarnings = Earning::where('technician_id', $technician->id)
            ->whereBetween('created_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ])
            ->sum('total_amount');

        // Pending tasks (jobs requiring action)
        $pendingTasks = Booking::where('technician_id', $technician->id)
            ->where('status', 'confirmed')
            ->count();

        // Average rating
        $avgRating = RatingReview::where('technician_id', $technician->id)
            ->where('is_approved', true)
            ->avg('overall_rating') ?? 0;

        return [
            BaseWidget\Stat::make('Today\'s Jobs', $todayJobs)
                ->description('Scheduled for today')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($todayJobs > 0 ? 'success' : 'gray'),

            BaseWidget\Stat::make('This Week\'s Earnings', '₱' . number_format($weeklyEarnings, 2))
                ->description('Total commission earned')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            BaseWidget\Stat::make('Pending Tasks', $pendingTasks)
                ->description('Jobs ready to start')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingTasks > 0 ? 'warning' : 'success'),

            BaseWidget\Stat::make('Average Rating', number_format($avgRating, 1) . '/5')
                ->description('Customer satisfaction')
                ->descriptionIcon('heroicon-m-star')
                ->color($avgRating >= 4.5 ? 'success' : ($avgRating >= 4 ? 'warning' : 'danger')),
        ];
    }
}
