<?php

namespace App\Filament\Pages\Reports;

use App\Models\CategoryScore;
use App\Models\RatingReview;
use App\Models\ReviewCategory;
use App\Models\Service;
use App\Models\Technician;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class CustomerSatisfactionReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static string $view = 'filament.pages.reports.customer-satisfaction';

    protected static ?string $title = 'Customer Satisfaction';

    protected static ?string $navigationLabel = 'Customer Satisfaction';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 4;

    public $reportType = 'monthly';

    public $startDate = null;

    public $endDate = null;

    public $selectedService = null;

    public $selectedTechnician = null;

    public $showReport = false;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateReport')
                ->label('Generate Report')
                ->icon('heroicon-o-document-chart-bar')
                ->color('primary')
                ->form([
                    Forms\Components\Select::make('reportType')
                        ->label('Report Type')
                        ->options([
                            'weekly' => 'Weekly Report',
                            'monthly' => 'Monthly Report',
                            'quarterly' => 'Quarterly Report',
                            'yearly' => 'Yearly Report',
                            'custom' => 'Custom Date Range',
                        ])
                        ->default('monthly')
                        ->reactive()
                        ->required(),

                    Forms\Components\DatePicker::make('startDate')
                        ->label('Start Date')
                        ->visible(fn ($get) => $get('reportType') === 'custom')
                        ->required(fn ($get) => $get('reportType') === 'custom'),

                    Forms\Components\DatePicker::make('endDate')
                        ->label('End Date')
                        ->visible(fn ($get) => $get('reportType') === 'custom')
                        ->required(fn ($get) => $get('reportType') === 'custom'),

                    Forms\Components\Select::make('selectedService')
                        ->label('Filter by Service')
                        ->placeholder('All Services')
                        ->options(Service::pluck('name', 'id')),

                    Forms\Components\Select::make('selectedTechnician')
                        ->label('Filter by Technician')
                        ->placeholder('All Technicians')
                        ->options(
                            Technician::with('user')
                                ->get()
                                ->pluck('user.name', 'id')
                        ),
                ])
                ->action(function (array $data) {
                    $this->reportType = $data['reportType'];
                    $this->selectedService = $data['selectedService'] ?? null;
                    $this->selectedTechnician = $data['selectedTechnician'] ?? null;

                    // Set date ranges based on report type
                    switch ($this->reportType) {
                        case 'weekly':
                            $this->startDate = now()->startOfWeek()->format('Y-m-d');
                            $this->endDate = now()->endOfWeek()->format('Y-m-d');
                            break;
                        case 'monthly':
                            $this->startDate = now()->startOfMonth()->format('Y-m-d');
                            $this->endDate = now()->endOfMonth()->format('Y-m-d');
                            break;
                        case 'quarterly':
                            $this->startDate = now()->firstOfQuarter()->format('Y-m-d');
                            $this->endDate = now()->lastOfQuarter()->format('Y-m-d');
                            break;
                        case 'yearly':
                            $this->startDate = now()->startOfYear()->format('Y-m-d');
                            $this->endDate = now()->endOfYear()->format('Y-m-d');
                            break;
                        case 'custom':
                            $this->startDate = $data['startDate'];
                            $this->endDate = $data['endDate'];
                            break;
                    }

                    $this->showReport = true;
                }),

            Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->visible(fn () => $this->showReport)
                ->action(fn () => $this->exportToPdf()),
        ];
    }

    // Overall Satisfaction Score
    public function getOverallSatisfaction(): float
    {
        if (! $this->showReport) {
            return 0;
        }

        $query = RatingReview::whereHas('booking', function ($q) {
            $q->whereDate('scheduled_start_at', '>=', $this->startDate)
                ->whereDate('scheduled_start_at', '<=', $this->endDate);

            if ($this->selectedService) {
                $q->where('service_id', $this->selectedService);
            }

            if ($this->selectedTechnician) {
                $q->where('technician_id', $this->selectedTechnician);
            }
        })->where('is_approved', true);

        return round($query->avg('overall_rating') ?: 0, 2);
    }

    // Total Reviews Count
    public function getTotalReviews(): int
    {
        if (! $this->showReport) {
            return 0;
        }

        $query = RatingReview::whereHas('booking', function ($q) {
            $q->whereDate('scheduled_start_at', '>=', $this->startDate)
                ->whereDate('scheduled_start_at', '<=', $this->endDate);

            if ($this->selectedService) {
                $q->where('service_id', $this->selectedService);
            }

            if ($this->selectedTechnician) {
                $q->where('technician_id', $this->selectedTechnician);
            }
        });

        return $query->count();
    }

    // Response Rate
    public function getResponseRate(): string
    {
        if (! $this->showReport) {
            return '0%';
        }

        $totalCompleted = \App\Models\Booking::whereDate('scheduled_start_at', '>=', $this->startDate)
            ->whereDate('scheduled_start_at', '<=', $this->endDate)
            ->where('status', 'completed')
            ->when($this->selectedService, fn ($q) => $q->where('service_id', $this->selectedService))
            ->when($this->selectedTechnician, fn ($q) => $q->where('technician_id', $this->selectedTechnician))
            ->count();

        $totalReviews = $this->getTotalReviews();

        if ($totalCompleted == 0) {
            return '0%';
        }

        return number_format(($totalReviews / $totalCompleted) * 100, 1).'%';
    }

    // Rating Distribution (5-star breakdown)
    public function getRatingDistribution(): array
    {
        if (! $this->showReport) {
            return [];
        }

        $distribution = [];
        for ($i = 5; $i >= 1; $i--) {
            $count = RatingReview::whereHas('booking', function ($q) {
                $q->whereDate('scheduled_start_at', '>=', $this->startDate)
                    ->whereDate('scheduled_start_at', '<=', $this->endDate);

                if ($this->selectedService) {
                    $q->where('service_id', $this->selectedService);
                }

                if ($this->selectedTechnician) {
                    $q->where('technician_id', $this->selectedTechnician);
                }
            })
                ->where('is_approved', true)
                ->whereBetween('overall_rating', [$i - 0.5, $i + 0.49])
                ->count();

            $distribution[] = [
                'rating' => $i,
                'count' => $count,
                'percentage' => 0, // Will be calculated in view
            ];
        }

        return $distribution;
    }

    // Category-wise Average Ratings
    public function getCategoryRatings(): array
    {
        if (! $this->showReport) {
            return [];
        }

        $categories = ReviewCategory::all();
        $result = [];

        foreach ($categories as $category) {
            $avgScore = CategoryScore::whereHas('review.booking', function ($q) {
                $q->whereDate('scheduled_start_at', '>=', $this->startDate)
                    ->whereDate('scheduled_start_at', '<=', $this->endDate);

                if ($this->selectedService) {
                    $q->where('service_id', $this->selectedService);
                }

                if ($this->selectedTechnician) {
                    $q->where('technician_id', $this->selectedTechnician);
                }
            })
                ->where('category_scores.category_id', $category->id)
                ->whereHas('review', fn ($q) => $q->where('is_approved', true))
                ->avg('category_scores.score') ?: 0;

            $result[] = [
                'category' => $category->name,
                'icon' => $category->icon,
                'rating' => round($avgScore, 2),
            ];
        }

        return $result;
    }

    // Satisfaction Trends (Line Chart Data)
    public function getSatisfactionTrends(): array
    {
        if (! $this->showReport) {
            return [];
        }

        $groupBy = 'DATE(bookings.scheduled_start_at)';
        $format = 'M d';

        // Adjust grouping based on date range
        $daysDiff = Carbon::parse($this->startDate)->diffInDays(Carbon::parse($this->endDate));
        if ($daysDiff > 90) {
            $groupBy = 'YEAR(bookings.scheduled_start_at), MONTH(bookings.scheduled_start_at)';
            $format = 'M Y';
        } elseif ($daysDiff > 30) {
            $groupBy = 'YEAR(bookings.scheduled_start_at), WEEK(bookings.scheduled_start_at)';
            $format = 'W';
        }

        return RatingReview::join('bookings', 'ratings_reviews.booking_id', '=', 'bookings.id')
            ->whereDate('bookings.scheduled_start_at', '>=', $this->startDate)
            ->whereDate('bookings.scheduled_start_at', '<=', $this->endDate)
            ->when($this->selectedService, fn ($q) => $q->where('bookings.service_id', $this->selectedService))
            ->when($this->selectedTechnician, fn ($q) => $q->where('bookings.technician_id', $this->selectedTechnician))
            ->where('ratings_reviews.is_approved', true)
            ->select(DB::raw($groupBy.' as period'), DB::raw('AVG(overall_rating) as avg_rating'))
            ->groupBy(DB::raw($groupBy))
            ->orderBy(DB::raw($groupBy))
            ->get()
            ->map(function ($item) use ($format) {
                $date = is_string($item->period) ? Carbon::parse($item->period) : Carbon::now();

                return [
                    'period' => $date->format($format),
                    'rating' => round($item->avg_rating, 2),
                ];
            })
            ->toArray();
    }

    // Service-wise Satisfaction
    public function getServiceSatisfaction(): array
    {
        if (! $this->showReport) {
            return [];
        }

        return Service::all()->map(function ($service) {
            $query = RatingReview::whereHas('booking', function ($q) use ($service) {
                $q->whereDate('scheduled_start_at', '>=', $this->startDate)
                    ->whereDate('scheduled_start_at', '<=', $this->endDate)
                    ->where('service_id', $service->id);

                if ($this->selectedTechnician) {
                    $q->where('technician_id', $this->selectedTechnician);
                }
            })->where('is_approved', true);

            $avgRating = $query->avg('overall_rating') ?: 0;
            $reviewCount = $query->count();

            return [
                'service' => $service->name,
                'rating' => round($avgRating, 2),
                'reviews' => $reviewCount,
            ];
        })
            ->filter(fn ($item) => $item['reviews'] > 0)
            ->values()
            ->toArray();
    }

    // Top Rated Technicians
    public function getTopRatedTechnicians(): array
    {
        if (! $this->showReport) {
            return [];
        }

        return Technician::with('user')->get()->map(function ($technician) {
            $query = RatingReview::whereHas('booking', function ($q) use ($technician) {
                $q->whereDate('scheduled_start_at', '>=', $this->startDate)
                    ->whereDate('scheduled_start_at', '<=', $this->endDate)
                    ->where('technician_id', $technician->id);

                if ($this->selectedService) {
                    $q->where('service_id', $this->selectedService);
                }
            })->where('is_approved', true);

            $avgRating = $query->avg('overall_rating') ?: 0;
            $reviewCount = $query->count();

            return [
                'technician' => $technician->user->name,
                'rating' => round($avgRating, 2),
                'reviews' => $reviewCount,
            ];
        })
            ->filter(fn ($item) => $item['reviews'] > 0)
            ->sortByDesc('rating')
            ->take(10)
            ->values()
            ->toArray();
    }

    // Recent Reviews Sample
    public function getRecentReviews(): array
    {
        if (! $this->showReport) {
            return [];
        }

        return RatingReview::with(['booking.service', 'booking.technician.user', 'customer'])
            ->whereHas('booking', function ($q) {
                $q->whereDate('scheduled_start_at', '>=', $this->startDate)
                    ->whereDate('scheduled_start_at', '<=', $this->endDate);

                if ($this->selectedService) {
                    $q->where('service_id', $this->selectedService);
                }

                if ($this->selectedTechnician) {
                    $q->where('technician_id', $this->selectedTechnician);
                }
            })
            ->where('is_approved', true)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function ($review) {
                return [
                    'customer' => $review->customer->name,
                    'service' => $review->booking->service->name,
                    'technician' => $review->booking->technician->user->name,
                    'rating' => $review->overall_rating,
                    'review' => $review->review,
                    'date' => $review->created_at->format('M d, Y'),
                ];
            })
            ->toArray();
    }

    protected function exportToPdf()
    {
        $data = [
            'reportType' => ucfirst($this->reportType),
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'overallSatisfaction' => $this->getOverallSatisfaction(),
            'totalReviews' => $this->getTotalReviews(),
            'responseRate' => $this->getResponseRate(),
            'ratingDistribution' => $this->getRatingDistribution(),
            'categoryRatings' => $this->getCategoryRatings(),
            'serviceSatisfaction' => $this->getServiceSatisfaction(),
            'topRatedTechnicians' => $this->getTopRatedTechnicians(),
            'recentReviews' => $this->getRecentReviews(),
            'generatedAt' => now()->format('F j, Y g:i A'),
            'selectedService' => $this->selectedService ? Service::find($this->selectedService)?->name : 'All Services',
            'selectedTechnician' => $this->selectedTechnician ? Technician::find($this->selectedTechnician)?->user?->name : 'All Technicians',
        ];

        // Calculate total for rating distribution
        $totalRatings = collect($data['ratingDistribution'])->sum('count');
        foreach ($data['ratingDistribution'] as &$rating) {
            $rating['percentage'] = $totalRatings > 0 ? round(($rating['count'] / $totalRatings) * 100, 1) : 0;
        }

        $pdf = Pdf::loadView('reports.customer-satisfaction-pdf', $data);
        $pdf->setPaper('A4', 'portrait');

        $filename = 'customer-satisfaction-report-'.Carbon::now()->format('Y-m-d').'.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }
}
