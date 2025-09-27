<?php

namespace App\Filament\Technician\Widgets;

use App\Models\RatingReview;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class JobReportsStatsWidget extends BaseWidget
{
    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $user = Auth::user();
        $technician = $user?->technician;

        if (! $technician) {
            return [];
        }

        // Total reviews received
        $totalReviews = RatingReview::where('technician_id', $technician->getKey())
            ->where('is_approved', true)
            ->count();

        // Average rating
        $avgRating = RatingReview::where('technician_id', $technician->getKey())
            ->where('is_approved', true)
            ->avg('overall_rating') ?? 0;

        // Customer satisfaction (4+ stars percentage)
        $highRatings = RatingReview::where('technician_id', $technician->getKey())
            ->where('is_approved', true)
            ->where('overall_rating', '>=', 4)
            ->count();

        $satisfaction = $totalReviews > 0 ? ($highRatings / $totalReviews) * 100 : 0;

        return [
            BaseWidget\Stat::make('Total Reviews', $totalReviews)
                ->description('Customer feedback received')
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->color('info'),

            BaseWidget\Stat::make('Average Rating', number_format($avgRating, 1).'/5')
                ->description('Overall customer satisfaction')
                ->descriptionIcon('heroicon-m-star')
                ->color($avgRating >= 4.5 ? 'success' : ($avgRating >= 4 ? 'warning' : 'danger')),

            BaseWidget\Stat::make('Customer Satisfaction', number_format($satisfaction, 1).'%')
                ->description('4+ star ratings')
                ->descriptionIcon('heroicon-m-face-smile')
                ->color($satisfaction >= 90 ? 'success' : ($satisfaction >= 75 ? 'warning' : 'danger')),
        ];
    }
}
