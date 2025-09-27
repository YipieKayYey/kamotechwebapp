<?php

namespace App\Filament\Pages\Reports;

use App\Models\AirconType;
use App\Models\Booking;
use App\Models\Service;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class ServiceAnalyticsReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static string $view = 'filament.pages.reports.service-analytics';

    protected static ?string $title = 'Service Analytics';

    protected static ?string $navigationLabel = 'Service Analytics';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 2;

    public $reportType = 'monthly';

    public $startDate = null;

    public $endDate = null;

    public $selectedService = null;

    public $selectedAirconType = null;

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

                    Forms\Components\Select::make('selectedAirconType')
                        ->label('Filter by AC Type')
                        ->placeholder('All AC Types')
                        ->options(AirconType::pluck('name', 'id')),
                ])
                ->action(function (array $data) {
                    $this->reportType = $data['reportType'];
                    $this->selectedService = $data['selectedService'] ?? null;
                    $this->selectedAirconType = $data['selectedAirconType'] ?? null;

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

    // Service Distribution Data
    public function getServiceDistribution(): array
    {
        if (! $this->showReport) {
            return [];
        }

        $query = Booking::whereDate('scheduled_start_at', '>=', $this->startDate)
            ->whereDate('scheduled_start_at', '<=', $this->endDate);

        if ($this->selectedService) {
            $query->where('service_id', $this->selectedService);
        }

        if ($this->selectedAirconType) {
            $query->where('aircon_type_id', $this->selectedAirconType);
        }

        return $query->select('service_id', DB::raw('COUNT(*) as total'))
            ->with('service')
            ->groupBy('service_id')
            ->get()
            ->map(function ($item) {
                return [
                    'service' => $item->service->name,
                    'count' => $item->total,
                    'percentage' => 0, // Will be calculated in view
                ];
            })
            ->toArray();
    }

    // Booking Trends Data (for line chart)
    public function getBookingTrends(): array
    {
        if (! $this->showReport) {
            return [];
        }

        $query = Booking::whereDate('scheduled_start_at', '>=', $this->startDate)
            ->whereDate('scheduled_start_at', '<=', $this->endDate);

        if ($this->selectedService) {
            $query->where('service_id', $this->selectedService);
        }

        if ($this->selectedAirconType) {
            $query->where('aircon_type_id', $this->selectedAirconType);
        }

        // Group by date for trends
        return $query->select(DB::raw('DATE(scheduled_start_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => Carbon::parse($item->date)->format('M d'),
                    'count' => $item->count,
                ];
            })
            ->toArray();
    }

    // Service by AC Type Data
    public function getServiceByAirconType(): array
    {
        if (! $this->showReport) {
            return [];
        }

        $query = Booking::whereDate('scheduled_start_at', '>=', $this->startDate)
            ->whereDate('scheduled_start_at', '<=', $this->endDate);

        if ($this->selectedService) {
            $query->where('service_id', $this->selectedService);
        }

        if ($this->selectedAirconType) {
            $query->where('aircon_type_id', $this->selectedAirconType);
        }

        return $query->select('service_id', 'aircon_type_id', DB::raw('COUNT(*) as total'))
            ->with(['service', 'airconType'])
            ->groupBy('service_id', 'aircon_type_id')
            ->get()
            ->groupBy('service.name')
            ->map(function ($services) {
                return $services->map(function ($item) {
                    return [
                        'aircon_type' => $item->airconType->name,
                        'count' => $item->total,
                    ];
                });
            })
            ->toArray();
    }

    // Summary Statistics
    public function getTotalBookings(): int
    {
        if (! $this->showReport) {
            return 0;
        }

        $query = Booking::whereDate('scheduled_start_at', '>=', $this->startDate)
            ->whereDate('scheduled_start_at', '<=', $this->endDate);

        if ($this->selectedService) {
            $query->where('service_id', $this->selectedService);
        }

        if ($this->selectedAirconType) {
            $query->where('aircon_type_id', $this->selectedAirconType);
        }

        return $query->count();
    }

    public function getCompletedBookings(): int
    {
        if (! $this->showReport) {
            return 0;
        }

        $query = Booking::whereDate('scheduled_start_at', '>=', $this->startDate)
            ->whereDate('scheduled_start_at', '<=', $this->endDate)
            ->where('status', 'completed');

        if ($this->selectedService) {
            $query->where('service_id', $this->selectedService);
        }

        if ($this->selectedAirconType) {
            $query->where('aircon_type_id', $this->selectedAirconType);
        }

        return $query->count();
    }

    public function getAverageUnitsPerBooking(): float
    {
        if (! $this->showReport) {
            return 0;
        }

        $query = Booking::whereDate('scheduled_start_at', '>=', $this->startDate)
            ->whereDate('scheduled_start_at', '<=', $this->endDate);

        if ($this->selectedService) {
            $query->where('service_id', $this->selectedService);
        }

        if ($this->selectedAirconType) {
            $query->where('aircon_type_id', $this->selectedAirconType);
        }

        return round($query->avg('number_of_units') ?: 0, 1);
    }

    public function getMostPopularService(): string
    {
        if (! $this->showReport) {
            return 'N/A';
        }

        $query = Booking::whereDate('scheduled_start_at', '>=', $this->startDate)
            ->whereDate('scheduled_start_at', '<=', $this->endDate);

        if ($this->selectedAirconType) {
            $query->where('aircon_type_id', $this->selectedAirconType);
        }

        $topService = $query->select('service_id', DB::raw('COUNT(*) as total'))
            ->groupBy('service_id')
            ->orderByDesc('total')
            ->with('service')
            ->first();

        return $topService ? $topService->service->name : 'N/A';
    }

    public function getCompletionRate(): string
    {
        $total = $this->getTotalBookings();
        $completed = $this->getCompletedBookings();

        if ($total === 0) {
            return '0%';
        }

        return number_format(($completed / $total) * 100, 1).'%';
    }

    // Service Performance Table Data
    public function getServicePerformanceData(): array
    {
        if (! $this->showReport) {
            return [];
        }

        $query = Booking::whereDate('scheduled_start_at', '>=', $this->startDate)
            ->whereDate('scheduled_start_at', '<=', $this->endDate);

        if ($this->selectedService) {
            $query->where('service_id', $this->selectedService);
        }

        if ($this->selectedAirconType) {
            $query->where('aircon_type_id', $this->selectedAirconType);
        }

        return Service::all()->map(function ($service) use ($query) {
            $serviceBookings = (clone $query)->where('service_id', $service->id);
            $total = $serviceBookings->count();
            $completed = (clone $serviceBookings)->where('status', 'completed')->count();
            $revenue = (clone $serviceBookings)->where('payment_status', 'paid')->sum('total_amount');
            $avgDuration = (clone $serviceBookings)->avg('estimated_duration_minutes') ?: 0;

            return [
                'service' => $service->name,
                'total_bookings' => $total,
                'completed' => $completed,
                'completion_rate' => $total > 0 ? number_format(($completed / $total) * 100, 1) : 0,
                'revenue' => $revenue,
                'avg_duration' => round($avgDuration / 60, 1), // Convert to hours
            ];
        })
            ->filter(fn ($item) => $item['total_bookings'] > 0) // Only show services with bookings
            ->values()
            ->toArray();
    }

    protected function exportToPdf()
    {
        $data = [
            'reportType' => ucfirst($this->reportType),
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'totalBookings' => $this->getTotalBookings(),
            'completedBookings' => $this->getCompletedBookings(),
            'completionRate' => $this->getCompletionRate(),
            'averageUnitsPerBooking' => $this->getAverageUnitsPerBooking(),
            'mostPopularService' => $this->getMostPopularService(),
            'serviceDistribution' => $this->getServiceDistribution(),
            'bookingTrends' => $this->getBookingTrends(),
            'servicePerformance' => $this->getServicePerformanceData(),
            'generatedAt' => now()->format('F j, Y g:i A'),
            'selectedService' => $this->selectedService ? Service::find($this->selectedService)?->name : 'All Services',
            'selectedAirconType' => $this->selectedAirconType ? AirconType::find($this->selectedAirconType)?->name : 'All AC Types',
        ];

        // Calculate percentages for service distribution
        $total = collect($data['serviceDistribution'])->sum('count');
        foreach ($data['serviceDistribution'] as &$service) {
            $service['percentage'] = $total > 0 ? round(($service['count'] / $total) * 100, 1) : 0;
        }

        $pdf = Pdf::loadView('reports.service-analytics-pdf', $data);
        $pdf->setPaper('A4', 'portrait');

        $filename = 'service-analytics-report-'.Carbon::now()->format('Y-m-d').'.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }
}
