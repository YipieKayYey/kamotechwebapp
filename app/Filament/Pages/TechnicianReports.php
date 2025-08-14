<?php

namespace App\Filament\Pages;

use App\Models\Technician;
use App\Models\Booking;
use App\Models\Earning;
use App\Models\RatingReview;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class TechnicianReports extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.pages.technician-reports';

    protected static ?string $title = 'Technician Performance Reports';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?int $navigationSort = 8;

    public $reportType = 'weekly';
    public $selectedTechnician = null;
    public $startDate = null;
    public $endDate = null;
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
                            'yearly' => 'Yearly Report',
                            'custom' => 'Custom Date Range',
                        ])
                        ->default('weekly')
                        ->reactive()
                        ->required(),
                    
                    Forms\Components\Select::make('selectedTechnician')
                        ->label('Technician')
                        ->placeholder('All Technicians')
                        ->options(
                            Technician::with('user')
                                ->get()
                                ->pluck('user.name', 'id')
                                ->toArray()
                        ),
                    
                    Forms\Components\DatePicker::make('startDate')
                        ->label('Start Date')
                        ->default(now()->startOfWeek())
                        ->visible(fn ($get) => $get('reportType') === 'custom')
                        ->required(fn ($get) => $get('reportType') === 'custom'),
                    
                    Forms\Components\DatePicker::make('endDate')
                        ->label('End Date')
                        ->default(now()->endOfWeek())
                        ->visible(fn ($get) => $get('reportType') === 'custom')
                        ->required(fn ($get) => $get('reportType') === 'custom'),
                ])
                ->action(function (array $data) {
                    $this->reportType = $data['reportType'];
                    $this->selectedTechnician = $data['selectedTechnician'];
                    
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
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Technician Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                    
                Tables\Columns\TextColumn::make('employee_id')
                    ->label('Employee ID')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('total_jobs')
                    ->label('Total Jobs')
                    ->getStateUsing(fn ($record) => $this->getTechnicianJobCount($record))
                    ->sortable()
                    ->alignCenter(),
                    
                Tables\Columns\TextColumn::make('completed_jobs')
                    ->label('Completed Jobs')
                    ->getStateUsing(fn ($record) => $this->getTechnicianCompletedJobs($record))
                    ->sortable()
                    ->alignCenter(),
                    
                Tables\Columns\TextColumn::make('total_earnings')
                    ->label('Total Earnings')
                    ->getStateUsing(fn ($record) => '₱' . number_format($this->getTechnicianEarnings($record), 2))
                    ->sortable()
                    ->alignEnd()
                    ->color('success'),
                    
                Tables\Columns\TextColumn::make('average_rating')
                    ->label('Avg. Rating')
                    ->getStateUsing(fn ($record) => number_format($this->getTechnicianAverageRating($record), 2) . '/5.0')
                    ->sortable()
                    ->alignCenter()
                    ->color('warning'),
                    
                Tables\Columns\TextColumn::make('completion_rate')
                    ->label('Completion Rate')
                    ->getStateUsing(fn ($record) => $this->getCompletionRate($record) . '%')
                    ->sortable()
                    ->alignCenter(),
                    
                Tables\Columns\BadgeColumn::make('performance')
                    ->label('Performance')
                    ->getStateUsing(fn ($record) => $this->getPerformanceRating($record))
                    ->colors([
                        'success' => 'Excellent',
                        'primary' => 'Good',
                        'warning' => 'Average',
                        'danger' => 'Needs Improvement',
                    ])
                    ->alignCenter(),
            ])
            ->defaultSort('total_jobs', 'desc')
            ->emptyStateHeading('No report generated yet')
            ->emptyStateDescription('Click "Generate Report" above to create a technician performance report.')
            ->emptyStateIcon('heroicon-o-chart-bar');
    }

    protected function getTableQuery(): Builder    
    {
        if (!$this->showReport) {
            return Technician::query()->whereRaw('1 = 0'); // Return empty query
        }

        $query = Technician::with(['user', 'bookings', 'earnings']);
        
        if ($this->selectedTechnician) {
            $query->where('id', $this->selectedTechnician);
        }
        
        return $query;
    }

    protected function getTechnicianJobCount($technician): int
    {
        if (!$this->startDate || !$this->endDate) {
            return $technician->total_jobs;
        }
        
        return $technician->bookings()
            ->whereBetween('scheduled_date', [$this->startDate, $this->endDate])
            ->count();
    }

    protected function getTechnicianCompletedJobs($technician): int
    {
        if (!$this->startDate || !$this->endDate) {
            return $technician->bookings()->where('status', 'completed')->count();
        }
        
        return $technician->bookings()
            ->where('status', 'completed')
            ->whereBetween('scheduled_date', [$this->startDate, $this->endDate])
            ->count();
    }

    protected function getTechnicianEarnings($technician): float
    {
        if (!$this->startDate || !$this->endDate) {
            return $technician->earnings()->sum('total_amount');
        }
        
        return $technician->earnings()
            ->whereHas('booking', function ($query) {
                $query->whereBetween('scheduled_date', [$this->startDate, $this->endDate]);
            })
            ->sum('total_amount');
    }

    protected function getTechnicianAverageRating($technician): float
    {
        if (!$this->startDate || !$this->endDate) {
            return $technician->reviews()->avg('overall_rating') ?: 0;
        }
        
        return $technician->reviews()
            ->whereHas('booking', function ($query) {
                $query->whereBetween('scheduled_date', [$this->startDate, $this->endDate]);
            })
            ->avg('overall_rating') ?: 0;
    }

    protected function getCompletionRate($technician): string
    {
        $totalJobs = $this->getTechnicianJobCount($technician);
        $completedJobs = $this->getTechnicianCompletedJobs($technician);
        
        if ($totalJobs === 0) {
            return '0';
        }
        
        return number_format(($completedJobs / $totalJobs) * 100, 1);
    }

    protected function getPerformanceRating($technician): string
    {
        $completionRate = (float) $this->getCompletionRate($technician);
        $avgRating = $this->getTechnicianAverageRating($technician);
        
        if ($completionRate >= 95 && $avgRating >= 4.5) {
            return 'Excellent';
        } elseif ($completionRate >= 85 && $avgRating >= 4.0) {
            return 'Good';
        } elseif ($completionRate >= 70 && $avgRating >= 3.5) {
            return 'Average';
        } else {
            return 'Needs Improvement';
        }
    }

    public function getTitle(): string
    {
        if ($this->reportType && $this->startDate && $this->endDate) {
            $reportTypeLabel = ucfirst($this->reportType);
            if ($this->selectedTechnician) {
                $technicianName = Technician::find($this->selectedTechnician)?->user?->name ?? 'Selected Technician';
                return "{$reportTypeLabel} Report for {$technicianName} ({$this->startDate} to {$this->endDate})";
            }
            return "{$reportTypeLabel} Report ({$this->startDate} to {$this->endDate})";
        }
        
        return 'Technician Performance Reports';
    }

    // Helper methods for the view
    public function getTotalBookings(): int
    {
        if (!$this->startDate || !$this->endDate || !$this->showReport) {
            return 0;
        }
        
        return Booking::whereBetween('scheduled_date', [$this->startDate, $this->endDate])->count();
    }

    public function getTotalRevenue(): float
    {
        if (!$this->startDate || !$this->endDate || !$this->showReport) {
            return 0;
        }
        
        return Booking::whereBetween('scheduled_date', [$this->startDate, $this->endDate])
            ->where('payment_status', 'paid')
            ->sum('total_amount');
    }

    public function getAverageRating(): float
    {
        if (!$this->startDate || !$this->endDate || !$this->showReport) {
            return 0;
        }
        
        return RatingReview::whereHas('booking', function($q) { 
            $q->whereBetween('scheduled_date', [$this->startDate, $this->endDate]); 
        })->avg('overall_rating') ?: 0;
    }
}