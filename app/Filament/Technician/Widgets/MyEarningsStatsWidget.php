<?php

namespace App\Filament\Technician\Widgets;

use App\Models\Earning;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class MyEarningsStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        $technician = $user->technician;

        if (!$technician) {
            return [];
        }

        // Total earnings (paid only)
        $totalEarnings = Earning::where('technician_id', $technician->id)
            ->where('payment_status', 'paid')
            ->sum('total_amount');

        // This month earnings (paid only)
        $monthlyEarnings = Earning::where('technician_id', $technician->id)
            ->where('payment_status', 'paid')
            ->whereMonth('created_at', now()->month)
            ->sum('total_amount');

        // Pending earnings
        $pendingEarnings = Earning::where('technician_id', $technician->id)
            ->where('payment_status', 'pending')
            ->sum('total_amount');

        // Average commission rate
        $avgCommission = Earning::where('technician_id', $technician->id)
            ->avg('commission_rate') ?? 0;

        // Total earnings (all statuses)
        $allEarnings = Earning::where('technician_id', $technician->id)->sum('total_amount');

        return [
            BaseWidget\Stat::make('Total Earned', '₱' . number_format($allEarnings, 2))
                ->description('All earnings (paid + pending)')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            BaseWidget\Stat::make('Paid Earnings', '₱' . number_format($totalEarnings, 2))
                ->description('Received payments')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info'),

            BaseWidget\Stat::make('Pending Payment', '₱' . number_format($pendingEarnings, 2))
                ->description('Awaiting payment')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            BaseWidget\Stat::make('Avg Commission', number_format($avgCommission, 1) . '%')
                ->description('Average commission rate')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('gray'),
        ];
    }
}
