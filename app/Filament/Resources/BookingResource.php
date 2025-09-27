<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingResource\Pages;
use App\Models\Booking;
use App\Models\User;
use App\Services\TechnicianRankingService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'Manage Bookings';

    protected static ?string $navigationGroup = 'Booking Management';

    protected static ?int $navigationSort = 2;

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
                            ->label('Registered Customer')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn ($record) => $record?->customer_id !== null)
                            ->disabled(),

                        Forms\Components\Select::make('guest_customer_id')
                            ->label('Guest Customer')
                            ->relationship('guestCustomer', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name.' - '.$record->phone)
                            ->searchable()
                            ->preload()
                            ->visible(fn ($record) => $record?->guest_customer_id !== null)
                            ->disabled(),

                        Forms\Components\TextInput::make('customer_name')
                            ->label('Guest Customer Name (Legacy)')
                            ->placeholder('For old walk-in/phone bookings')
                            ->visible(fn ($record) => $record?->customer_id === null && $record?->guest_customer_id === null && $record?->customer_name !== null)
                            ->disabled(),

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
                            ->relationship('technician', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->user->name)
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
                        Forms\Components\DateTimePicker::make('scheduled_start_at')
                            ->label('Start')
                            ->seconds(false)
                            ->required(),

                        Forms\Components\DateTimePicker::make('scheduled_end_at')
                            ->label('End')
                            ->seconds(false)
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

                // Show cancellation info to admins when applicable
                Forms\Components\Section::make('Cancellation')
                    ->schema([
                        Forms\Components\TextInput::make('cancellation_reason')
                            ->label('Reason Category')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\Textarea::make('cancellation_details')
                            ->label('Reason Details')
                            ->rows(3)
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('cancellation_requested_at')
                            ->label('Requested At')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($record) => $record?->cancellation_requested_at ? $record->cancellation_requested_at->format('M j, Y g:i A') : null),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => in_array($record?->status, ['cancel_requested', 'cancelled']))
                    ->collapsible(),

                Forms\Components\Section::make('Address & Contact')
                    ->schema([
                        Forms\Components\TextInput::make('customer_mobile')
                            ->label('Mobile')
                            ->tel(),

                        // Toggle for using customer's registered address
                        Forms\Components\Toggle::make('use_customer_address')
                            ->label('Use Customer\'s Registered Address')
                            ->helperText('Toggle to use the customer\'s saved address or enter a custom address')
                            ->reactive()
                            ->dehydrated(false)
                            ->default(false)
                            ->visible(fn (Forms\Get $get) => $get('customer_id') !== null)
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                if ($state && $get('customer_id')) {
                                    $customer = User::find($get('customer_id'));
                                    if ($customer) {
                                        $set('province', $customer->province ?? '');
                                        $set('city_municipality', $customer->city_municipality ?? '');
                                        $set('barangay', $customer->barangay ?? '');
                                        $set('house_no_street', $customer->house_no_street ?? '');
                                        $set('nearest_landmark', $customer->nearest_landmark ?? '');
                                    }
                                }
                            })
                            ->columnSpanFull(),

                        // Province select (stores name into `province` on save)
                        Forms\Components\Select::make('province')
                            ->label('Province')
                            ->searchable()
                            ->options(fn (): array => \App\Models\Province::query()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->getOptionLabelUsing(fn ($value): ?string => \App\Models\Province::find($value)?->name)
                            ->dehydrateStateUsing(fn ($state): ?string => \App\Models\Province::find($state)?->name)
                            ->reactive()
                            ->afterStateUpdated(function (Forms\Set $set): void {
                                $set('city_municipality', null);
                                $set('barangay', null);
                            })
                            ->required()
                            ->disabled(fn (Forms\Get $get) => $get('use_customer_address')),

                        // City/Municipality select (stores name into `city_municipality` on save)
                        Forms\Components\Select::make('city_municipality')
                            ->label('City/Municipality')
                            ->searchable()
                            ->options(function (Forms\Get $get): array {
                                $provinceId = $get('province');
                                if (! $provinceId) {
                                    return [];
                                }

                                return \App\Models\City::query()
                                    ->where('province_id', $provinceId)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all();
                            })
                            ->getOptionLabelUsing(fn ($value): ?string => \App\Models\City::find($value)?->name)
                            ->dehydrateStateUsing(fn ($state): ?string => \App\Models\City::find($state)?->name)
                            ->reactive()
                            ->disabled(fn (Forms\Get $get): bool => ! (bool) $get('province'))
                            ->afterStateUpdated(function (Forms\Set $set): void {
                                $set('barangay', null);
                            })
                            ->required()
                            ->disabled(fn (Forms\Get $get) => $get('use_customer_address')),

                        // Barangay select (stores name into `barangay` on save)
                        Forms\Components\Select::make('barangay')
                            ->label('Barangay')
                            ->searchable()
                            ->options(function (Forms\Get $get): array {
                                $cityId = $get('city_municipality');
                                if (! $cityId) {
                                    return [];
                                }

                                return \App\Models\Barangay::query()
                                    ->where('city_id', $cityId)
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all();
                            })
                            ->getOptionLabelUsing(fn ($value): ?string => \App\Models\Barangay::find($value)?->name)
                            ->dehydrateStateUsing(fn ($state): ?string => \App\Models\Barangay::find($state)?->name)
                            ->reactive()
                            ->disabled(fn (Forms\Get $get): bool => $get('use_customer_address') || ! (bool) $get('city_municipality'))
                            ->required(),

                        Forms\Components\TextInput::make('house_no_street')
                            ->label('House No. & Street')
                            ->disabled(fn (Forms\Get $get) => $get('use_customer_address'))
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('nearest_landmark')
                            ->label('Nearest Landmark')
                            ->disabled(fn (Forms\Get $get) => $get('use_customer_address'))
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
            ->searchable()
            ->searchPlaceholder('Search by booking #, customer, service, technician...')
            ->modifyQueryUsing(function (Builder $query) {
                return $query->with(['customer', 'guestCustomer', 'service', 'technician.user', 'airconType']);
            })
            ->filters(static::getTableFilters())
            ->headerActions(static::getHeaderActions())
            ->actions(static::getTableActions())
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Delete actions disabled as per panelist requirement
                ]),
            ]);
    }

    protected static function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('booking_number')->label('Booking #')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('display_name')
                ->label('Customer')
                ->getStateUsing(fn ($record) => $record->display_name)
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query->where(function ($query) use ($search) {
                        $query->whereHas('customer', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                    });
                })
                ->sortable(),
            Tables\Columns\TextColumn::make('service.name')
                ->label('Service')
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query->whereHas('service', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
                })
                ->sortable(),
            Tables\Columns\TextColumn::make('number_of_units')->label('Units')->suffix(' unit(s)')->sortable()->alignCenter(),
            Tables\Columns\TextColumn::make('technician.user.name')
                ->label('Technician')
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query->whereHas('technician.user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
                })
                ->sortable(),
            Tables\Columns\TextColumn::make('scheduled_start_at')->label('Start')->dateTime('M j, Y g:i A')->sortable(),
            Tables\Columns\TextColumn::make('scheduled_end_at')->label('End')->dateTime('M j, Y g:i A')->sortable(),
            Tables\Columns\BadgeColumn::make('status')
                ->label('Status')
                ->colors([
                    'warning' => 'pending',
                    'info' => ['confirmed', 'cancel_requested'],
                    'primary' => 'in_progress',
                    'success' => 'completed',
                    'danger' => 'cancelled',
                ])
                ->sortable(),
            Tables\Columns\TextColumn::make('created_at')->label('Booked On')->dateTime('M j, Y g:i A')->sortable(),
        ];
    }

    protected static function getTableFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('status')
                ->options([
                    'pending' => 'Pending',
                    'confirmed' => 'Confirmed',
                    'in_progress' => 'In Progress',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                    'cancel_requested' => 'Cancel Requested',
                ]),

            Tables\Filters\SelectFilter::make('service')
                ->label('Service')
                ->relationship('service', 'name')
                ->preload()
                ->searchable(),

            Tables\Filters\SelectFilter::make('technician')
                ->label('Technician')
                ->relationship('technician.user', 'name')
                ->preload()
                ->searchable(),

            Tables\Filters\Filter::make('scheduled_date')
                ->form([
                    Forms\Components\DatePicker::make('date')
                        ->label('Specific Date'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['date'],
                            fn (Builder $query, $date): Builder => $query->whereDate('scheduled_start_at', $date),
                        );
                })
                ->indicateUsing(function (array $data): ?string {
                    if ($data['date'] ?? null) {
                        return 'Date: '.\Carbon\Carbon::parse($data['date'])->format('M j, Y');
                    }

                    return null;
                }),

            Tables\Filters\Filter::make('date_range')
                ->form([
                    Forms\Components\DatePicker::make('from')
                        ->label('From Date'),
                    Forms\Components\DatePicker::make('until')
                        ->label('To Date'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['from'],
                            fn (Builder $query, $date): Builder => $query->whereDate('scheduled_start_at', '>=', $date),
                        )
                        ->when(
                            $data['until'],
                            fn (Builder $query, $date): Builder => $query->whereDate('scheduled_start_at', '<=', $date),
                        );
                })
                ->indicateUsing(function (array $data): array {
                    $indicators = [];
                    if ($data['from'] ?? null) {
                        $indicators['from'] = 'From '.\Carbon\Carbon::parse($data['from'])->format('M j, Y');
                    }
                    if ($data['until'] ?? null) {
                        $indicators['until'] = 'Until '.\Carbon\Carbon::parse($data['until'])->format('M j, Y');
                    }

                    return $indicators;
                }),
        ];
    }

    protected static function getHeaderActions(): array
    {
        // Remove inline header button from table; handled by page header in ListBookings
        return [];
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

                    // Send SMS notification
                    $smsService = new \App\Services\SemaphoreSmsService;
                    $smsSent = $smsService->sendBookingCancellation($record);

                    $notification = \Filament\Notifications\Notification::make()
                        ->title('Cancellation Accepted')
                        ->body("Booking {$record->booking_number} has been cancelled.");

                    if ($smsSent) {
                        $notification->body("Booking {$record->booking_number} has been cancelled. Customer notified via SMS.");
                    }

                    $notification->success()->send();
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
            Tables\Actions\Action::make('confirm')
                ->label('Confirm')
                ->icon('heroicon-m-check-circle')
                ->color('success')
                ->visible(fn (Booking $record): bool => $record->status === 'pending')
                ->requiresConfirmation()
                ->action(function (Booking $record): void {
                    $record->update([
                        'status' => 'confirmed',
                        'confirmed_at' => now(),
                        'confirmed_by' => auth()->id(),
                    ]);

                    // Send SMS notification
                    $smsService = new \App\Services\SemaphoreSmsService;
                    $smsService->sendBookingConfirmation($record);
                }),

            Tables\Actions\Action::make('cancel')
                ->label('Cancel')
                ->icon('heroicon-m-x-circle')
                ->color('danger')
                ->visible(fn (Booking $record): bool => in_array($record->status, ['pending', 'confirmed']))
                ->requiresConfirmation()
                ->modalHeading('Cancel Booking')
                ->modalDescription('This will mark the booking as cancelled and set payment status to unpaid. This action cannot be undone.')
                ->action(function (Booking $record): void {
                    $record->update([
                        'status' => 'cancelled',
                        'payment_status' => 'unpaid',
                        'cancellation_processed_at' => now(),
                        'cancellation_processed_by' => auth()->id(),
                    ]);

                    // Send SMS notification
                    $smsService = new \App\Services\SemaphoreSmsService;
                    $smsSent = $smsService->sendBookingCancellation($record);

                    $notification = \Filament\Notifications\Notification::make()
                        ->title('Booking Cancelled')
                        ->body("Booking {$record->booking_number} has been cancelled.");

                    if ($smsSent) {
                        $notification->body("Booking {$record->booking_number} has been cancelled. Customer notified via SMS.");
                    }

                    $notification->danger()->send();
                }),
            Tables\Actions\Action::make('complete')->label('Complete')->icon('heroicon-m-check-badge')->color('success')->visible(fn (Booking $record): bool => $record->status === 'in_progress')->requiresConfirmation()->action(function (Booking $record): void {
                $record->update(['status' => 'completed', 'completed_at' => now()]);
            }),
            ViewAction::make(),
            EditAction::make()->visible(fn (Booking $record): bool => ! in_array($record->status, ['cancelled', 'completed'])),
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
    protected static function runUpdatedGreedyAlgorithm($unused, $unusedDate, callable $set, callable $get): void
    {
        $serviceId = $get('service_id');

        if (! $serviceId) {
            return;
        }

        try {
            // Use the TechnicianRankingService for pure service-rating algorithm
            // Dynamic ranking would go here once implemented
            return;

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

    protected static function runGreedyAlgorithm($unused, callable $set, callable $get): void
    {
        // Dynamic ranking stub – no-op to avoid referencing legacy vars

    }
}
