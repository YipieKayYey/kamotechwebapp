<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use App\Models\Booking;
use App\Models\User;
use App\Services\TechnicianAvailabilityService;
use App\Services\TechnicianRankingService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;

class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;

    protected static ?string $title = 'Create New Booking';

    public function form(Form $form): Form
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
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state && ! $get('use_custom_address')) {
                                    $customer = User::find($state);
                                    if ($customer && $customer->hasStructuredAddress()) {
                                        $set('province', $customer->province);
                                        $set('city_municipality', $customer->city_municipality);
                                        $set('barangay', $customer->barangay);
                                        $set('house_no_street', $customer->house_no_street);
                                        $set('customer_address', $customer->formatted_address);
                                    } elseif ($customer) {
                                        $set('province', null);
                                        $set('city_municipality', null);
                                        $set('barangay', null);
                                        $set('house_no_street', null);
                                        $set('customer_address', $customer->address ?? null);
                                    }
                                    $set('customer_name', '');
                                } elseif ($state) {
                                    $set('customer_name', '');
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

                        Forms\Components\Toggle::make('use_custom_address')
                            ->label('📍 Use Different Service Address')
                            ->helperText('Toggle OFF = Use customer\'s registered address | Toggle ON = Enter custom address')
                            ->default(false)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state) {
                                    $set('province', null);
                                    $set('city_municipality', null);
                                    $set('barangay', null);
                                    $set('house_no_street', null);
                                    $set('customer_address', null);
                                } else {
                                    $customerId = $get('customer_id');
                                    if ($customerId) {
                                        $customer = User::find($customerId);
                                        if ($customer && $customer->hasStructuredAddress()) {
                                            $set('province', $customer->province);
                                            $set('city_municipality', $customer->city_municipality);
                                            $set('barangay', $customer->barangay);
                                            $set('house_no_street', $customer->house_no_street);
                                            $set('customer_address', $customer->formatted_address);
                                        } elseif ($customer) {
                                            $set('province', null);
                                            $set('city_municipality', null);
                                            $set('barangay', null);
                                            $set('house_no_street', null);
                                            $set('customer_address', $customer->address ?? null);
                                        }
                                    }
                                }
                            })
                            ->columnSpanFull()
                            ->visible(fn (callable $get) => filled($get('customer_id'))),

                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('customer_mobile')
                                ->label('Mobile Number')
                                ->tel()
                                ->placeholder('09123456789')
                                ->helperText('Customer contact number for technician coordination'),

                            Forms\Components\TextInput::make('province')
                                ->label('Province')
                                ->required()
                                ->placeholder('e.g., Bataan, Bulacan, Pampanga')
                                ->helperText(fn (callable $get) => $get('use_custom_address')
                                        ? 'Enter province name'
                                        : ($get('customer_id') ? 'Auto-filled from customer address' : 'Enter province name')
                                )
                                ->disabled(fn (callable $get) => $get('customer_id') && ! $get('use_custom_address')),
                        ])->columns(2),

                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('city_municipality')
                                ->label('City/Municipality')
                                ->required()
                                ->placeholder('e.g., Balanga City, Orani, Hermosa')
                                ->disabled(fn (callable $get) => $get('customer_id') && ! $get('use_custom_address')),

                            Forms\Components\TextInput::make('barangay')
                                ->label('Barangay')
                                ->required()
                                ->placeholder('e.g., Poblacion, San Jose')
                                ->disabled(fn (callable $get) => $get('customer_id') && ! $get('use_custom_address')),
                        ])->columns(2),

                        Forms\Components\TextInput::make('house_no_street')
                            ->label('House No. & Street')
                            ->required()
                            ->placeholder('e.g., 123 Rizal Street, Block 5 Lot 10, Unit 2A')
                            ->disabled(fn (callable $get) => $get('customer_id') && ! $get('use_custom_address'))
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('nearest_landmark')
                            ->label('Nearest Landmark (Optional)')
                            ->placeholder('e.g., Near SM Mall, Opposite Jollibee, Behind Gas Station')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('customer_address')
                            ->label('Service Location (Auto-generated)')
                            ->placeholder('Will be generated when booking is saved')
                            ->disabled()
                            ->rows(2)
                            ->columnSpanFull(),

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
                                        $booking = new Booking([
                                            'service_id' => $state,
                                            'aircon_type_id' => $airconTypeId,
                                            'number_of_units' => $numberOfUnits,
                                        ]);
                                        $total = $booking->calculateTotalAmount();
                                        $set('total_amount', $total);
                                    }

                                    try {
                                        if (method_exists(static::class, 'runGreedyAlgorithm')) {
                                            static::runGreedyAlgorithm($get('timeslot_id'), $set, $get);
                                        }
                                    } catch (\Exception $e) {
                                        // Silently handle algorithm errors
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
                                        $booking = new Booking([
                                            'service_id' => $serviceId,
                                            'aircon_type_id' => $state,
                                            'number_of_units' => $numberOfUnits,
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
                                    $serviceId = $get('service_id');
                                    $airconTypeId = $get('aircon_type_id');

                                    if ($serviceId && $airconTypeId && $state && $state > 0) {
                                        $booking = new Booking([
                                            'service_id' => $serviceId,
                                            'aircon_type_id' => $airconTypeId,
                                            'number_of_units' => $state,
                                        ]);
                                        $total = $booking->calculateTotalAmount();
                                        $set('total_amount', $total);

                                        $service = \App\Models\Service::find($serviceId);
                                        $duration = static::calculateServiceDuration($service, $state);
                                        $set('estimated_duration_minutes', $duration['total_minutes']);
                                        $set('estimated_days', $duration['estimated_days']);

                                        $scheduledDate = $get('scheduled_date');
                                        if ($scheduledDate && $duration['estimated_days'] > 1) {
                                            $endDate = \Carbon\Carbon::parse($scheduledDate)->addDays($duration['estimated_days'] - 1);
                                            $set('scheduled_end_date', $endDate->format('Y-m-d'));
                                        } else {
                                            $set('scheduled_end_date', $scheduledDate);
                                        }
                                    } else {
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
                                    'Unknown', 'Not Sure', 'Multiple Brands',
                                ]),
                        ])->columns(2),

                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total Amount (₱)')
                            ->required()
                            ->numeric()
                            ->prefix('₱')
                            ->step(0.01)
                            ->helperText('Auto-calculated: Base price × units')
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
                                    $estimatedDays = $get('estimated_days') ?? 1;
                                    if ($state && $estimatedDays > 1) {
                                        $endDate = \Carbon\Carbon::parse($state)->addDays($estimatedDays - 1);
                                        $set('scheduled_end_date', $endDate->format('Y-m-d'));
                                    } else {
                                        $set('scheduled_end_date', $state);
                                    }

                                    try {
                                        if (method_exists(static::class, 'runGreedyAlgorithm')) {
                                            static::runGreedyAlgorithm($get('timeslot_id'), $set, $get);
                                        }
                                    } catch (\Exception $e) {
                                        // Silently handle algorithm errors
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
                                    $timeslots = \App\Models\Timeslot::all();
                                    $options = [];

                                    if ($scheduledDate) {
                                        try {
                                            $availabilityService = new TechnicianAvailabilityService;

                                            foreach ($timeslots as $timeslot) {
                                                $availableCount = $availabilityService->getAvailableTechniciansCount($scheduledDate, $timeslot->id);

                                                if ($availableCount > 0) {
                                                    $options[$timeslot->id] = "{$timeslot->display_time} ({$availableCount} technicians available)";
                                                } else {
                                                    $options[$timeslot->id] = "{$timeslot->display_time} (⚠️ No technicians available)";
                                                }
                                            }
                                        } catch (\Exception $e) {
                                            foreach ($timeslots as $timeslot) {
                                                $options[$timeslot->id] = $timeslot->display_time;
                                            }
                                        }
                                    } else {
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
                                    try {
                                        if (method_exists(static::class, 'runGreedyAlgorithm')) {
                                            static::runGreedyAlgorithm($state, $set, $get);
                                        }
                                    } catch (\Exception $e) {
                                        // Silently handle algorithm errors
                                    }
                                })
                                ->helperText('Shows real-time technician availability for each time slot'),

                            Forms\Components\Select::make('technician_id')
                                ->label('🏆 Technician Ranking (100% Service Rating)')
                                ->options(function (callable $get) {
                                    $serviceId = $get('service_id');
                                    $scheduledDate = $get('scheduled_date');
                                    $timeslotId = $get('timeslot_id');

                                    if ($serviceId && $scheduledDate && $timeslotId) {
                                        try {
                                            $availabilityService = new TechnicianAvailabilityService;
                                            $rankingService = new TechnicianRankingService($availabilityService);

                                            $rankedTechnicians = $rankingService->getRankedTechniciansForService(
                                                $serviceId,
                                                $scheduledDate,
                                                $timeslotId,
                                                null,
                                                null
                                            );

                                            $options = [];
                                            foreach ($rankedTechnicians as $index => $technician) {
                                                $rank = $index + 1;
                                                $serviceRating = $technician->service_specific_rating;
                                                $reviewCount = $technician->service_review_count;

                                                $options[$technician->id] = "#{$rank} {$technician->user->name} (Rating: {$serviceRating}/5, Reviews: {$reviewCount})";
                                            }

                                            return $options;
                                        } catch (\Exception $e) {
                                            return User::where('role', 'technician')
                                                ->where('is_active', true)
                                                ->pluck('name', 'id');
                                        }
                                    }

                                    return User::where('role', 'technician')
                                        ->where('is_active', true)
                                        ->pluck('name', 'id');
                                })
                                ->searchable()
                                ->preload()
                                ->placeholder('📊 Select from ranked technicians')
                                ->helperText('Technicians ranked by: 100% Service Rating (Pure Service Expertise)')
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
                                    'cancel_requested' => 'Cancel Requested',
                                ])
                                ->default('pending')
                                ->required(),

                            Forms\Components\Select::make('payment_status')
                                ->label('Payment Status')
                                ->options([
                                    'pending' => 'Pending',
                                    'paid' => 'Paid',
                                    'unpaid' => 'Unpaid',
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

    protected static function calculateServiceDuration($service, int $numberOfUnits): array
    {
        $baseMinutes = $service->duration_minutes ?? 90;

        $unit1 = $baseMinutes;
        $units2to5 = min(4, max(0, $numberOfUnits - 1)) * ($baseMinutes * 0.8);
        $units6plus = max(0, $numberOfUnits - 5) * ($baseMinutes * 0.6);

        $totalMinutes = $unit1 + $units2to5 + $units6plus;
        $estimatedDays = max(1, ceil($totalMinutes / 480));

        return [
            'total_minutes' => (int) $totalMinutes,
            'estimated_hours' => round($totalMinutes / 60, 1),
            'estimated_days' => $estimatedDays,
        ];
    }

    protected static function runGreedyAlgorithm($timeslotId, callable $set, callable $get): void
    {
        try {
            $serviceId = $get('service_id');
            $scheduledDate = $get('scheduled_date');

            if (! $serviceId || ! $scheduledDate || ! $timeslotId) {
                return;
            }

            $availabilityService = new TechnicianAvailabilityService;
            $rankingService = new TechnicianRankingService($availabilityService);

            $availableCount = $availabilityService->getAvailableTechniciansCount($scheduledDate, $timeslotId);

            $timeslotObj = \App\Models\Timeslot::find($timeslotId);
            $timeslotName = $timeslotObj ? $timeslotObj->display_time : 'Selected timeslot';

            if ($availableCount === 0) {
                $set('technician_id', null);

                \Illuminate\Support\Facades\Log::info('No technicians available', [
                    'date' => $scheduledDate,
                    'timeslot' => $timeslotName,
                    'service_id' => $serviceId,
                ]);

            } else {
                $rankedTechnicians = $rankingService->getRankedTechniciansForService(
                    $serviceId,
                    $scheduledDate,
                    $timeslotId,
                    null,
                    null
                );

                if ($rankedTechnicians->isNotEmpty()) {
                    $topTechnician = $rankedTechnicians->first();
                    $score = round($topTechnician->greedy_score, 3);
                    $serviceRating = $topTechnician->service_specific_rating;
                    $reviewCount = $topTechnician->service_review_count;

                    \Illuminate\Support\Facades\Log::info('Technician ranking generated (100% Service Rating)', [
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
                } else {
                    \Illuminate\Support\Facades\Log::warning('Greedy algorithm returned no ranked technicians', [
                        'date' => $scheduledDate,
                        'timeslot' => $timeslotName,
                        'service_id' => $serviceId,
                        'available_count' => $availableCount,
                    ]);
                }
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Greedy Algorithm Error in CreateBooking', [
                'error' => $e->getMessage(),
                'service_id' => $serviceId ?? null,
                'scheduled_date' => $scheduledDate ?? null,
                'timeslot_id' => $timeslotId ?? null,
            ]);
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        // Remove manual booking_number generation - let the Booking model handle it
        
        if (!empty($data['province']) && !empty($data['city_municipality'])) {
            $addressParts = array_filter([
                $data['house_no_street'] ?? '',
                $data['barangay'] ?? '',
                $data['city_municipality'] ?? '',
                $data['province'] ?? '',
            ]);
            $data['customer_address'] = implode(', ', $addressParts);
        }

        return $data;
    }
}
