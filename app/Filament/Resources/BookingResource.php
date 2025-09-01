<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingResource\Pages;
use App\Models\Booking;
use App\Models\User;
use App\Services\TechnicianAvailabilityService;
use App\Services\TechnicianRankingService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\EditAction;
use Illuminate\Database\Eloquent\Builder;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Booking Management';

    protected static ?string $navigationLabel = 'Booking Management';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $pendingCount = static::getModel()::where('status', 'pending')->count();
        $cancelRequestsCount = static::getModel()::where('status', 'cancel_requested')->count();
        
        // Priority: Show cancellations first (more urgent)
        if ($cancelRequestsCount > 0) {
            return "❗{$cancelRequestsCount}"; // e.g., "❗1" (red - urgent cancellations)
        } elseif ($pendingCount > 0) {
            return (string) $pendingCount; // e.g., "7" (yellow - normal pending)
        }
        
        return null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        // Red (danger) - Customer cancellation requests (highest priority)
        $hasCancellationRequests = static::getModel()::where('status', 'cancel_requested')->exists();
        if ($hasCancellationRequests) {
            return 'danger';
        }
        
        // Yellow (warning) - Pending bookings
        $hasPendingBookings = static::getModel()::where('status', 'pending')->exists();
        if ($hasPendingBookings) {
            return 'warning';
        }
        
        return null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        $pendingCount = static::getModel()::where('status', 'pending')->count();
        $cancelRequestsCount = static::getModel()::where('status', 'cancel_requested')->count();
        
        if ($cancelRequestsCount > 0) {
            $base = "❗ {$cancelRequestsCount} cancellation request(s) - URGENT";
            if ($pendingCount > 0) {
                $base .= " • ({$pendingCount} pending also waiting)";
            }
            return $base;
        } elseif ($pendingCount > 0) {
            return "{$pendingCount} pending booking(s) awaiting confirmation";
        }
        
        return null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->description('Core booking details')
                    ->schema([
                        Forms\Components\TextInput::make('booking_number')
                            ->label('Booking Number')
                            ->disabled()
                            ->placeholder('Auto-generated'),

                        Forms\Components\Select::make('customer_id')
                            ->label('Customer')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('customer_name')
                            ->label('Guest Customer Name')
                            ->placeholder('For walk-in/phone bookings'),

                        Forms\Components\Select::make('service_id')
                            ->label('Service')
                            ->relationship('service', 'name')
                            ->required(),

                        Forms\Components\Select::make('aircon_type_id')
                            ->label('AC Type')
                            ->relationship('airconType', 'name'),

                        Forms\Components\TextInput::make('number_of_units')
                            ->label('Units')
                            ->numeric()
                            ->required()
                            ->minValue(1),

                        Forms\Components\Select::make('technician_id')
                            ->label('Technician')
                            ->relationship('technician.user', 'name')
                            ->searchable(),

                        Forms\Components\TextInput::make('total_amount')
                            ->label('Amount (₱)')
                            ->numeric()
                            ->prefix('₱')
                            ->required(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Schedule')
                    ->schema([
                        Forms\Components\DatePicker::make('scheduled_date')
                            ->label('Service Date')
                            ->required(),

                        Forms\Components\Select::make('timeslot_id')
                            ->label('Time Slot')
                            ->relationship('timeslot', 'display_time')
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                                'in_progress' => 'In Progress',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                                'cancel_requested' => 'Cancel Requested',
                            ])
                            ->required(),

                        Forms\Components\Select::make('payment_status')
                            ->label('Payment')
                            ->options([
                                'pending' => 'Pending',
                                'paid' => 'Paid',
                                'unpaid' => 'Unpaid',
                            ])
                            ->required(),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Address & Contact')
                            ->schema([
                                Forms\Components\TextInput::make('customer_mobile')
                            ->label('Mobile')
                            ->tel(),

                                Forms\Components\TextInput::make('province')
                            ->label('Province'),

                                Forms\Components\TextInput::make('city_municipality')
                            ->label('City/Municipality'),

                                Forms\Components\TextInput::make('barangay')
                            ->label('Barangay'),

                                Forms\Components\TextInput::make('house_no_street')
                                    ->label('House No. & Street')
                                    ->columnSpanFull(),

                        Forms\Components\Textarea::make('special_instructions')
                            ->label('Special Instructions')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(static::getTableColumns())
            ->defaultSort('created_at', 'desc')
            ->filters(static::getTableFilters())
            ->headerActions(static::getHeaderActions())
            ->actions(static::getTableActions())
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('booking_number')->label('Booking #')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('display_name')->label('Customer')->getStateUsing(fn ($record) => $record->display_name)->searchable(['customer_name', 'customer.name'])->sortable(),
            Tables\Columns\TextColumn::make('service.name')->label('Service')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('number_of_units')->label('Units')->suffix(' unit(s)')->sortable()->alignCenter(),
            Tables\Columns\TextColumn::make('technician.user.name')->label('Technician')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('scheduled_date')->label('Date')->date('M j, Y')->sortable(),
            Tables\Columns\TextColumn::make('timeslot.display_time')->label('Time')->searchable()->sortable(),
            Tables\Columns\BadgeColumn::make('status')->label('Status')->colors(['secondary' => 'pending', 'warning' => 'confirmed', 'primary' => 'in_progress', 'success' => 'completed', 'danger' => 'cancelled', 'info' => 'cancel_requested'])->sortable(),
            Tables\Columns\TextColumn::make('created_at')->label('Booked On')->dateTime('M j, Y g:i A')->sortable(),
        ];
    }

    protected static function getTableFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('status')->options(['pending' => 'Pending', 'confirmed' => 'Confirmed', 'in_progress' => 'In Progress', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'cancel_requested' => 'Cancel Requested']),
            Tables\Filters\SelectFilter::make('payment_status')->options(['pending' => 'Pending', 'paid' => 'Paid', 'unpaid' => 'Unpaid']),
        ];
    }

    protected static function getHeaderActions(): array
    {
        return [
            Tables\Actions\CreateAction::make()->label('Create New Booking')->icon('heroicon-m-plus'),
        ];
    }

    protected static function getTableActions(): array
    {
        return [
            // Cancellation Request Actions (show only for cancel_requested status)
            Tables\Actions\Action::make('accept_cancellation')
                ->label('Accept Cancellation')
                ->icon('heroicon-m-x-circle')
                ->color('danger')
                ->visible(fn (Booking $record): bool => $record->status === 'cancel_requested')
                ->requiresConfirmation()
                ->modalHeading('Accept Cancellation Request')
                ->modalDescription('This will mark the booking as cancelled and set payment status to unpaid. This action cannot be undone.')
                ->action(function (Booking $record): void {
                    $record->update([
                        'status' => 'cancelled',
                        'payment_status' => 'unpaid',
                        'cancellation_processed_at' => now(),
                        'cancellation_processed_by' => auth()->id(),
                    ]);
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Cancellation Accepted')
                        ->body("Booking {$record->booking_number} has been cancelled.")
                        ->success()
                        ->send();
                }),

            Tables\Actions\Action::make('reject_cancellation')
                ->label('Reject Cancellation')
                ->icon('heroicon-m-arrow-uturn-left')
                ->color('warning')
                ->visible(fn (Booking $record): bool => $record->status === 'cancel_requested')
                ->requiresConfirmation()
                ->modalHeading('Reject Cancellation Request')
                ->modalDescription('This will restore the booking to pending status.')
                ->action(function (Booking $record): void {
                    $record->update([
                        'status' => 'pending',
                        'cancellation_requested_at' => null,
                        'cancellation_reason' => null,
                        'cancellation_details' => null,
                    ]);
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Cancellation Rejected')
                        ->body("Booking {$record->booking_number} has been restored to pending.")
                        ->warning()
                        ->send();
                }),

            // Regular booking actions
            Tables\Actions\Action::make('confirm')->label('Confirm')->icon('heroicon-m-check-circle')->color('success')->visible(fn (Booking $record): bool => $record->status === 'pending')->requiresConfirmation()->action(function (Booking $record): void { $record->update(['status' => 'confirmed', 'confirmed_at' => now(), 'confirmed_by' => auth()->id()]); }),
            Tables\Actions\Action::make('complete')->label('Complete')->icon('heroicon-m-check-badge')->color('success')->visible(fn (Booking $record): bool => $record->status === 'in_progress')->requiresConfirmation()->action(function (Booking $record): void { $record->update(['status' => 'completed', 'completed_at' => now()]); }),
            ViewAction::make(),
            EditAction::make()->visible(fn (Booking $record): bool => !in_array($record->status, ['cancelled', 'completed'])),
        ];
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'view' => Pages\ViewBooking::route('/{record}'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
        ];
    }

    /**
     * Calculate simple pricing without discounts
     */
    protected static function calculateSimplePricing(float $basePrice, int $numberOfUnits): float
    {
        return $basePrice * $numberOfUnits;
    }

    /**
     * Calculate service duration and estimated days based on units
     */
    protected static function calculateServiceDuration($service, int $numberOfUnits): array
    {
        $baseMinutes = $service->duration_minutes ?? 90;

        // Progressive time calculation (efficiency improves with more units)
        $unit1 = $baseMinutes;
        $units2to5 = min(4, max(0, $numberOfUnits - 1)) * ($baseMinutes * 0.8);
        $units6plus = max(0, $numberOfUnits - 5) * ($baseMinutes * 0.6);

        $totalMinutes = $unit1 + $units2to5 + $units6plus;
        $estimatedDays = max(1, ceil($totalMinutes / 480)); // 8 hours per working day

        return [
            'total_minutes' => (int) $totalMinutes,
            'estimated_hours' => round($totalMinutes / 60, 1),
            'estimated_days' => $estimatedDays,
        ];
    }

    /**
     * 🎯 KAMOTECH GREEDY ALGORITHM INTEGRATION
     *
     * This method runs the greedy algorithm to rank available technicians
     * and displays availability information in the admin panel.
     */
    /**
     * Calculate estimated days based on service complexity and unit count
     */
    protected static function calculateEstimatedDays(int $serviceId, int $numberOfUnits): int
    {
        // Get service complexity
        $service = \App\Models\Service::find($serviceId);
        if (! $service) {
            return 1;
        }

        // Base days calculation based on service type
        $baseDays = match ($service->category) {
            'installation' => 2, // Installation takes longer
            'repair' => 1,
            'maintenance' => 1,
            'cleaning' => 1,
            default => 1
        };

        // Additional days for multiple units
        if ($numberOfUnits > 3) {
            $baseDays += ceil(($numberOfUnits - 3) / 3); // +1 day per 3 additional units
        }

        return max(1, $baseDays);
    }

    /**
     * Run pure service-rating algorithm for technician assignment
     */
    protected static function runUpdatedGreedyAlgorithm($timeslotId, $scheduledDate, callable $set, callable $get): void
    {
        $serviceId = $get('service_id');

        if (! $serviceId || ! $timeslotId || ! $scheduledDate) {
            return;
        }

        try {
            // Use the TechnicianRankingService for pure service-rating algorithm
            $rankingService = app(TechnicianRankingService::class);
            $rankedTechnicians = $rankingService->getRankedTechniciansForService(
                $serviceId, 
                $scheduledDate, 
                $timeslotId
            );

            if ($rankedTechnicians->isNotEmpty()) {
                $bestTechnician = $rankedTechnicians->first();
            $set('technician_id', $bestTechnician->id);
                
                // Show algorithm result notification
                \Filament\Notifications\Notification::make()
                    ->title('Best Technician Found')
                    ->body("Selected: {$bestTechnician->user->name} (Rating: {$bestTechnician->service_specific_rating}/5 for this service)")
                    ->success()
                    ->duration(3000)
                    ->send();
            }
        } catch (\Exception $e) {
            // Silently handle algorithm errors during form processing
        }
    }

    protected static function runGreedyAlgorithm($timeslotId, callable $set, callable $get): void
    {
        try {
            // Get required data
            $serviceId = $get('service_id');
            $scheduledDate = $get('scheduled_date');

            // Skip if missing required data
            if (! $serviceId || ! $scheduledDate || ! $timeslotId) {
                return;
            }

            // Initialize services
            $availabilityService = new TechnicianAvailabilityService;
            $rankingService = new TechnicianRankingService($availabilityService);

            // Get availability count
            $availableCount = $availabilityService->getAvailableTechniciansCount($scheduledDate, $timeslotId);

            // Get timeslot info
            $timeslotObj = \App\Models\Timeslot::find($timeslotId);
            $timeslotName = $timeslotObj ? $timeslotObj->display_time : 'Selected timeslot';

            if ($availableCount === 0) {
                // No technicians available - clear selection
                $set('technician_id', null);

                // Log the unavailability for admin reference
                \Illuminate\Support\Facades\Log::info('No technicians available', [
                    'date' => $scheduledDate,
                    'timeslot' => $timeslotName,
                    'service_id' => $serviceId,
                ]);

            } else {
                // Get ranked technicians for this service and timeslot
                $rankedTechnicians = $rankingService->getRankedTechniciansForService(
                    $serviceId,
                    $scheduledDate,
                    $timeslotId,
                    null, // No GPS coordinates needed anymore
                    null  // No GPS coordinates needed anymore
                );

                if ($rankedTechnicians->isNotEmpty()) {
                    // Get the top-ranked technician info for display
                    $topTechnician = $rankedTechnicians->first();
                    $score = round($topTechnician->greedy_score, 3);
                    $serviceRating = $topTechnician->service_specific_rating;
                    $reviewCount = $topTechnician->service_review_count;

                    // Log ranking results for admin reference
                    \Illuminate\Support\Facades\Log::info('Technician ranking generated', [
                        'date' => $scheduledDate,
                        'timeslot' => $timeslotName,
                        'service_id' => $serviceId,
                        'available_count' => $availableCount,
                        'top_technician' => $topTechnician->user->name,
                        'top_score' => $score,
                        'service_rating' => $serviceRating,
                        'review_count' => $reviewCount,
                        'total_ranked' => $rankedTechnicians->count(),
                    ]);

                    // Don't auto-assign - let user choose from ranked options
                    // The dropdown will show technicians in ranking order

                } else {
                    // Log error but don't break the form
                    \Illuminate\Support\Facades\Log::warning('Greedy algorithm returned no ranked technicians', [
                        'date' => $scheduledDate,
                        'timeslot' => $timeslotName,
                        'service_id' => $serviceId,
                        'available_count' => $availableCount,
                    ]);
                }
            }

        } catch (\Exception $e) {
            // Log error but don't break the form
            \Illuminate\Support\Facades\Log::error('Greedy Algorithm Error in BookingResource', [
                'error' => $e->getMessage(),
                'service_id' => $serviceId ?? null,
                'scheduled_date' => $scheduledDate ?? null,
                'timeslot_id' => $timeslotId ?? null,
            ]);
        }
    }
}
