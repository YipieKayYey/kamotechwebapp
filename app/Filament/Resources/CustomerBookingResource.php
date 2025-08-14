<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerBookingResource\Pages;
use App\Filament\Resources\CustomerBookingResource\RelationManagers;
use App\Models\Booking;
use App\Models\User;
use App\Services\TechnicianAvailabilityService;
use App\Services\TechnicianRankingService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomerBookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-phone';
    protected static ?string $navigationGroup = 'Booking Management';
    protected static ?string $navigationLabel = 'Customer Bookings';
    protected static ?string $modelLabel = 'Customer Booking';
    protected static ?string $pluralModelLabel = 'Customer Bookings';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Customer Information')
                    ->description('Select an existing customer or enter details for a new/guest customer')
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label('Existing Customer (Optional)')
                            ->relationship('customer', 'name', function ($query) {
                                return $query->where('role', 'customer');
                            })
                            ->searchable()
                            ->preload()
                            ->placeholder('Search for existing customer...')
                            ->helperText('Leave empty to create booking for new customer')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $user = User::find($state);
                                    if ($user) {
                                        $set('customer_name', '');
                                        $set('customer_address', $user->address ?? '');
                                    }
                                }
                            })
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('customer_name')
                            ->label('Customer Name')
                            ->placeholder('Enter customer name for new booking')
                            ->helperText('Use this for walk-in customers or phone bookings')
                            ->hidden(fn (callable $get) => filled($get('customer_id')))
                            ->required(fn (callable $get) => empty($get('customer_id')))
                            ->columnSpanFull(),

                        // Contact & Address Components (Ready for Autocomplete API)
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('customer_mobile')
                                ->label('Mobile Number')
                                ->tel()
                                ->placeholder('09123456789')
                                ->helperText('Customer contact number for technician coordination'),

                            Forms\Components\TextInput::make('province')
                                ->label('Province')
                                ->required()
                                ->placeholder('e.g., Bataan, Bulacan, Pampanga'),
                        ])->columns(2),

                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('city_municipality')
                                ->label('City/Municipality')
                                ->required()
                                ->placeholder('e.g., Balanga City, Orani, Hermosa'),

                            Forms\Components\TextInput::make('barangay')
                                ->label('Barangay')
                                ->required()
                                ->placeholder('e.g., Poblacion, San Jose'),
                        ])->columns(2),

                        Forms\Components\TextInput::make('house_no_street')
                            ->label('House No. & Street')
                            ->required()
                            ->placeholder('e.g., 123 Rizal Street, Block 5 Lot 10, Unit 2A')
                            ->helperText('✍️ Customer manually enters their specific house number and street')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('nearest_landmark')
                            ->label('Nearest Landmark (Optional)')
                            ->placeholder('e.g., Near SM Mall, Opposite Jollibee, Behind Gas Station')
                            ->helperText('✍️ Customer manually enters landmark to help technicians locate the address')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('customer_address')
                            ->label('Complete Address')
                            ->placeholder('Will be generated when booking is saved')
                            ->disabled()
                            ->columnSpanFull()
                            ->helperText('📍 Full address will be compiled from the components above'),


                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Service Details')
                    ->schema([
                        Forms\Components\Group::make([
                            Forms\Components\Select::make('service_id')
                                ->label('Service')
                                ->relationship('service', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $airconTypeId = $get('aircon_type_id');
                                    $numberOfUnits = $get('number_of_units') ?? 0;
                                    
                                    if ($state && $airconTypeId && $numberOfUnits > 0) {
                                        // Use Booking model's calculation method
                                        $booking = new Booking([
                                            'service_id' => $state,
                                            'aircon_type_id' => $airconTypeId,
                                            'number_of_units' => $numberOfUnits
                                        ]);
                                        $total = $booking->calculateTotalAmount();
                                        $set('total_amount', $total);
                                    }
                                    
                                    // Run Greedy Algorithm when service changes (safe call)
                                    try {
                                        if (method_exists(static::class, 'runGreedyAlgorithm')) {
                                            static::runGreedyAlgorithm($get('timeslot_id'), $set, $get);
                                        }
                                    } catch (\Exception $e) {
                                        // Silently handle algorithm errors during form processing
                                    }
                                }),

                            Forms\Components\Select::make('aircon_type_id')
                                ->label('Aircon Type')
                                ->relationship('airconType', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $serviceId = $get('service_id');
                                    $numberOfUnits = $get('number_of_units') ?? 0;
                                    
                                    if ($serviceId && $state && $numberOfUnits > 0) {
                                        // Use Booking model's calculation method
                                        $booking = new Booking([
                                            'service_id' => $serviceId,
                                            'aircon_type_id' => $state,
                                            'number_of_units' => $numberOfUnits
                                        ]);
                                        $total = $booking->calculateTotalAmount();
                                        $set('total_amount', $total);
                                    }
                                }),


                        ])->columns(3),
                        
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('number_of_units')
                                ->label('Number of AC Units')
                                ->required()
                                ->numeric()
                                ->default(0)
                                ->minValue(1)
                                ->maxValue(50)
                                ->placeholder('Enter number of units')
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    // Recalculate pricing when number of units changes
                                    $serviceId = $get('service_id');
                                    $airconTypeId = $get('aircon_type_id');
                                    
                                    if ($serviceId && $airconTypeId && $state && $state > 0) {
                                        // Use Booking model's calculation method
                                        $booking = new Booking([
                                            'service_id' => $serviceId,
                                            'aircon_type_id' => $airconTypeId,
                                            'number_of_units' => $state
                                        ]);
                                        $total = $booking->calculateTotalAmount();
                                        $set('total_amount', $total);
                                        
                                        // Auto-calculate estimated days and duration
                                        $service = \App\Models\Service::find($serviceId);
                                        $duration = static::calculateServiceDuration($service, $state);
                                        $set('estimated_duration_minutes', $duration['total_minutes']);
                                        $set('estimated_days', $duration['estimated_days']);
                                        
                                        // Auto-set end date for multi-day jobs
                                        $scheduledDate = $get('scheduled_date');
                                        if ($scheduledDate && $duration['estimated_days'] > 1) {
                                            $endDate = \Carbon\Carbon::parse($scheduledDate)->addDays($duration['estimated_days'] - 1);
                                            $set('scheduled_end_date', $endDate->format('Y-m-d'));
                                        } else {
                                            $set('scheduled_end_date', $scheduledDate);
                                        }
                                    } else {
                                        // Clear calculated fields when units is 0 or invalid
                                        $set('total_amount', 0);
                                        $set('estimated_days', 0);
                                        $set('estimated_duration_minutes', 0);
                                    }
                                })
                                ->helperText('⚡ Enter the total number of AC units to activate pricing calculations'),
                                
                            Forms\Components\TextInput::make('ac_brand')
                                ->label('AC Brand')
                                ->placeholder('e.g., Samsung, LG, Daikin, or "Unknown"')
                                ->helperText('Customer\'s air conditioner brand (optional)')
                                ->datalist([
                                    'Samsung', 'LG', 'Carrier', 'Daikin', 'Panasonic', 'Sharp',
                                    'Kolin', 'Koppel', 'Condura', 'Hitachi', 'TCL', 'Haier',
                                    'Unknown', 'Not Sure', 'Multiple Brands'
                                ]),
                        ])->columns(2),

                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total Amount (₱)')
                            ->required()
                            ->numeric()
                            ->prefix('₱')
                            ->step(0.01)
                            ->helperText('Auto-calculated: Base price × units with progressive discounts (10-20%)')
                            ->disabled()
                            ->dehydrated(),
                            
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('estimated_duration_minutes')
                                ->label('Estimated Duration (minutes)')
                                ->numeric()
                                ->disabled()
                                ->dehydrated()
                                ->helperText('Auto-calculated based on service type and number of units'),
                                
                            Forms\Components\TextInput::make('estimated_days')
                                ->label('Estimated Days')
                                ->numeric()
                                ->disabled()
                                ->dehydrated()
                                ->helperText('Auto-calculated - large jobs may require multiple days'),
                        ])->columns(2),
                    ]),

                Forms\Components\Section::make('Schedule')
                    ->schema([
                        Forms\Components\Group::make([
                            Forms\Components\DatePicker::make('scheduled_date')
                                ->label('Service Start Date')
                                ->required()
                                ->default(now()->addDay())
                                ->minDate(now())
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    // Auto-update end date when start date changes
                                    $estimatedDays = $get('estimated_days') ?? 1;
                                    if ($state && $estimatedDays > 1) {
                                        $endDate = \Carbon\Carbon::parse($state)->addDays($estimatedDays - 1);
                                        $set('scheduled_end_date', $endDate->format('Y-m-d'));
                                    } else {
                                        $set('scheduled_end_date', $state);
                                    }
                                    
                                    // Run Greedy Algorithm when date changes (safe call)
                                    try {
                                        if (method_exists(static::class, 'runGreedyAlgorithm')) {
                                            static::runGreedyAlgorithm($get('timeslot_id'), $set, $get);
                                        }
                                    } catch (\Exception $e) {
                                        // Silently handle algorithm errors during form processing
                                    }
                                }),
                                
                            Forms\Components\DatePicker::make('scheduled_end_date')
                                ->label('Service End Date')
                                ->helperText('Auto-calculated for multi-day jobs')
                                ->reactive(),

                            Forms\Components\Select::make('timeslot_id')
                                ->label('⏰ Time Slot (with Availability)')
                                ->options(function (callable $get) {
                                    $scheduledDate = $get('scheduled_date');
                                    
                                    // Get all timeslots
                                    $timeslots = \App\Models\Timeslot::all();
                                    $options = [];
                                    
                                    if ($scheduledDate) {
                                        try {
                                            $availabilityService = new TechnicianAvailabilityService();
                                            
                                            foreach ($timeslots as $timeslot) {
                                                $availableCount = $availabilityService->getAvailableTechniciansCount($scheduledDate, $timeslot->id);
                                                
                                                if ($availableCount > 0) {
                                                    $options[$timeslot->id] = "{$timeslot->display_time} ({$availableCount} technicians available)";
                                                } else {
                                                    $options[$timeslot->id] = "{$timeslot->display_time} (⚠️ No technicians available)";
                                                }
                                            }
                                        } catch (\Exception $e) {
                                            // Fallback to regular timeslot list if availability check fails
                                            foreach ($timeslots as $timeslot) {
                                                $options[$timeslot->id] = $timeslot->display_time;
                                            }
                                        }
                                    } else {
                                        // No date selected yet, show basic timeslots
                                        foreach ($timeslots as $timeslot) {
                                            $options[$timeslot->id] = "{$timeslot->display_time} (Select date first)";
                                        }
                                    }
                                    
                                    return $options;
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    // Run Greedy Algorithm when timeslot is selected (safe call)
                                    try {
                                        if (method_exists(static::class, 'runGreedyAlgorithm')) {
                                            static::runGreedyAlgorithm($state, $set, $get);
                                        }
                                    } catch (\Exception $e) {
                                        // Silently handle algorithm errors during form processing
                                    }
                                })
                                ->helperText('Shows real-time technician availability for each time slot'),

                            Forms\Components\Select::make('technician_id')
                                ->label('🏆 Technician Ranking (Greedy Algorithm)')
                                ->options(function (callable $get) {
                                    // Get current form data
                                    $serviceId = $get('service_id');
                                    $scheduledDate = $get('scheduled_date');
                                    $timeslotId = $get('timeslot_id');

                                    
                                    // If we have all required data, get ranked technicians
                                    if ($serviceId && $scheduledDate && $timeslotId) {
                                        try {
                                            $availabilityService = new TechnicianAvailabilityService();
                                            $rankingService = new TechnicianRankingService($availabilityService);
                                            
                                            $rankedTechnicians = $rankingService->getRankedTechniciansForService(
                                                $serviceId,
                                                $scheduledDate,
                                                $timeslotId,
                                                null,
                                                null
                                            );
                                            
                                            // Create options with service-specific info
                                            $options = [];
                                            foreach ($rankedTechnicians as $index => $technician) {
                                                $rank = $index + 1;
                                                $serviceRating = $technician->service_specific_rating;
                                                $reviewCount = $technician->service_review_count;
                                                $completedJobs = $technician->service_completed_jobs ?? 0;
                                                
                                                $options[$technician->id] = "#{$rank} {$technician->user->name} (Rating: {$serviceRating}/5, Reviews: {$reviewCount}, Completed: {$completedJobs})";
                                            }
                                            
                                            return $options;
                                        } catch (\Exception $e) {
                                            // Fallback to regular technician list if algorithm fails
                                            return User::where('role', 'technician')
                                                ->where('is_active', true)
                                                ->pluck('name', 'id');
                                        }
                                    }
                                    
                                    // Fallback to regular technician list
                                    return User::where('role', 'technician')
                                        ->where('is_active', true)
                                        ->pluck('name', 'id');
                                })
                                ->searchable()
                                ->preload()
                                ->placeholder('📊 Select from ranked technicians')
                                ->helperText('Technicians ranked by: Service Rating (70%) + Availability (30%)')
                                ->hintAction(
                                    Forms\Components\Actions\Action::make('refresh_algorithm')
                                        ->label('🔄 Refresh Ranking')
                                        ->action(function (callable $set, callable $get) {
                                            try {
                                                if (method_exists(static::class, 'runGreedyAlgorithm')) {
                                                    static::runGreedyAlgorithm($get('timeslot_id'), $set, $get);
                                                }
                                            } catch (\Exception $e) {
                                                // Silently handle algorithm errors
                                            }
                                        })
                                ),
                        ])->columns(2),

                        Forms\Components\Textarea::make('special_instructions')
                            ->label('Special Instructions')
                            ->placeholder('Any specific requirements or notes for the technician...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Booking Settings')
                    ->schema([
                        Forms\Components\Group::make([
                            Forms\Components\Select::make('status')
                                ->label('Status')
                                ->options([
                                    'pending' => 'Pending',
                                    'confirmed' => 'Confirmed',
                                    'in_progress' => 'In Progress',
                                    'completed' => 'Completed',
                                    'cancelled' => 'Cancelled',
                                ])
                                ->default('pending')
                                ->required(),

                            Forms\Components\Select::make('payment_status')
                                ->label('Payment Status')
                                ->options([
                                    'pending' => 'Pending',
                                    'paid' => 'Paid',
                                    'refunded' => 'Refunded',
                                ])
                                ->default('pending')
                                ->required(),

                            Forms\Components\TextInput::make('booking_number')
                                ->label('Booking Number')
                                ->disabled()
                                ->placeholder('Auto-generated')
                                ->helperText('Will be automatically generated'),
                        ])->columns(3),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('booking_number')
                    ->label('Booking #')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('display_name')
                    ->label('Customer')
                    ->getStateUsing(fn ($record) => $record->display_name)
                    ->searchable(['customer_name', 'customer.name'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('service.name')
                    ->label('Service')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('number_of_units')
                    ->label('Units')
                    ->suffix(' unit(s)')
                    ->sortable()
                    ->alignCenter(),
                    
                Tables\Columns\TextColumn::make('ac_brand')
                    ->label('AC Brand')
                    ->searchable()
                    ->sortable()
                    ->limit(15)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('customer_address')
                    ->label('Service Address')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(function ($record) {
                        return $record->customer_address . ($record->nearest_landmark ? ' (Near: ' . $record->nearest_landmark . ')' : '');
                    }),

                Tables\Columns\TextColumn::make('nearest_landmark')
                    ->label('Landmark')
                    ->limit(20)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('service_period')
                    ->label('Service Period')
                    ->getStateUsing(function ($record) {
                        $startDate = \Carbon\Carbon::parse($record->scheduled_date)->format('M j, Y');
                        $endDate = $record->scheduled_end_date ? \Carbon\Carbon::parse($record->scheduled_end_date)->format('M j, Y') : null;
                        
                        if ($endDate && $startDate !== $endDate) {
                            return $startDate . ' - ' . $endDate . ' (' . $record->estimated_days . ' days)';
                        }
                        
                        return $startDate . ' (1 day)';
                    })
                    ->searchable(['scheduled_date', 'scheduled_end_date'])
                    ->sortable(['scheduled_date']),
                    
                Tables\Columns\TextColumn::make('estimated_duration_minutes')
                    ->label('Duration')
                    ->getStateUsing(fn ($record) => $record->estimated_duration_minutes ? 
                        floor($record->estimated_duration_minutes / 60) . 'h ' . 
                        ($record->estimated_duration_minutes % 60) . 'm' : 
                        'Not set'
                    )
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('timeslot.display_time')
                    ->label('Time')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'confirmed',
                        'primary' => 'in_progress',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ]),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Amount')
                    ->money('PHP')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_guest_booking')
                    ->label('Guest')
                    ->getStateUsing(fn ($record) => $record->is_guest_booking)
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Booked On')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                
                Tables\Filters\SelectFilter::make('service_id')
                    ->relationship('service', 'name')
                    ->label('Service'),

                Tables\Filters\Filter::make('guest_bookings')
                    ->label('Guest Bookings Only')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('customer_name')),

                Tables\Filters\Filter::make('registered_customers')
                    ->label('Registered Customers Only')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('customer_id')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('call_customer')
                    ->label('Call')
                    ->icon('heroicon-o-phone')
                    ->url(function ($record) {
                        $phone = $record->customer ? $record->customer->phone : null;
                        return $phone ? "tel:$phone" : null;
                    })
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record->customer && $record->customer->phone),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListCustomerBookings::route('/'),
            'create' => Pages\CreateCustomerBooking::route('/create'),
            'edit' => Pages\EditCustomerBooking::route('/{record}/edit'),
        ];
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
    protected static function runGreedyAlgorithm($timeslotId, callable $set, callable $get): void
    {
        try {
            // Get required data
            $serviceId = $get('service_id');
            $scheduledDate = $get('scheduled_date');

            
            // Skip if missing required data
            if (!$serviceId || !$scheduledDate || !$timeslotId) {
                return;
            }
            
            // Initialize services
            $availabilityService = new TechnicianAvailabilityService();
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
                    null,
                    null
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
            \Illuminate\Support\Facades\Log::error('Greedy Algorithm Error in CustomerBookingResource', [
                'error' => $e->getMessage(),
                'service_id' => $serviceId ?? null,
                'scheduled_date' => $scheduledDate ?? null,
                'timeslot_id' => $timeslotId ?? null,
            ]);
        }
    }


}