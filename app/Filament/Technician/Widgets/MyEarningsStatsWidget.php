<?php

namespace App\Filament\Technician\Widgets;

use App\Models\Earning;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class MyEarningsStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $user = Auth::user();
        $technician = $user->technician;

        if (! $technician) {
            return [];
        }

        // This Week's earnings (paid only)
        $weekEarnings = Earning::where('technician_id', $technician->id)
            ->where('payment_status', 'paid')
            ->whereHas('booking', function ($query) {
                $query->whereBetween('scheduled_start_at', [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek(),
                ]);
            })
            ->sum('total_amount');

        // This Month's earnings (paid only)
        $monthEarnings = Earning::where('technician_id', $technician->id)
            ->where('payment_status', 'paid')
            ->whereHas('booking', function ($query) {
                $query->whereMonth('scheduled_start_at', Carbon::now()->month)
                    ->whereYear('scheduled_start_at', Carbon::now()->year);
            })
            ->sum('total_amount');

        // This Year's earnings (paid only)
        $yearEarnings = Earning::where('technician_id', $technician->id)
            ->where('payment_status', 'paid')
            ->whereHas('booking', function ($query) {
                $query->whereYear('scheduled_start_at', Carbon::now()->year);
            })
            ->sum('total_amount');

        // Total earnings (all time, paid only)
        $totalEarnings = Earning::where('technician_id', $technician->id)
            ->where('payment_status', 'paid')
            ->sum('total_amount');

        // Pending payments
        $pendingPayments = Earning::where('technician_id', $technician->id)
            ->where('payment_status', 'pending')
            ->sum('total_amount');

        // Calculate trend for the week (compare to last week)
        $lastWeekEarnings = Earning::where('technician_id', $technician->id)
            ->where('payment_status', 'paid')
            ->whereHas('booking', function ($query) {
                $query->whereBetween('scheduled_start_at', [
                    Carbon::now()->subWeek()->startOfWeek(),
                    Carbon::now()->subWeek()->endOfWeek(),
                ]);
            })
            ->sum('total_amount');

        $weekTrend = $lastWeekEarnings > 0
            ? round((($weekEarnings - $lastWeekEarnings) / $lastWeekEarnings) * 100, 1)
            : 0;

        // Calculate trend for the month (compare to last month)
        $lastMonthEarnings = Earning::where('technician_id', $technician->id)
            ->where('payment_status', 'paid')
            ->whereHas('booking', function ($query) {
                $query->whereMonth('scheduled_start_at', Carbon::now()->subMonth()->month)
                    ->whereYear('scheduled_start_at', Carbon::now()->subMonth()->year);
            })
            ->sum('total_amount');

        $monthTrend = $lastMonthEarnings > 0
            ? round((($monthEarnings - $lastMonthEarnings) / $lastMonthEarnings) * 100, 1)
            : 0;

        return [
            BaseWidget\Stat::make('This Week', '₱'.number_format($weekEarnings, 2))
                ->description($weekTrend >= 0 ? '↑ '.$weekTrend.'% from last week' : '↓ '.abs($weekTrend).'% from last week')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($weekTrend >= 0 ? 'success' : 'danger')
                ->chart($this->getWeeklyChart()),

            BaseWidget\Stat::make('This Month', '₱'.number_format($monthEarnings, 2))
                ->description($monthTrend >= 0 ? '↑ '.$monthTrend.'% from last month' : '↓ '.abs($monthTrend).'% from last month')
                ->descriptionIcon('heroicon-m-calendar')
                ->color($monthTrend >= 0 ? 'success' : 'danger'),

            BaseWidget\Stat::make('Total Earnings', '₱'.number_format($totalEarnings, 2))
                ->description('All-time earnings')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            BaseWidget\Stat::make('Commission Rate', number_format($technician->commission_rate ?? 30, 1).'%')
                ->description('Your commission rate')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color('primary'),
        ];
    }

    protected function getWeeklyChart(): array
    {
        $user = Auth::user();
        $technician = $user->technician;

        if (! $technician) {
            return [];
        }

        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $earning = Earning::where('technician_id', $technician->id)
                ->where('payment_status', 'paid')
                ->whereHas('booking', function ($query) use ($date) {
                    $query->whereDate('scheduled_start_at', $date);
                })
                ->sum('total_amount');
            $data[] = $earning;
        }

        return $data;
    }
}
