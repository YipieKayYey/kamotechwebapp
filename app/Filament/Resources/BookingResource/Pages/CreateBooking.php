<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use App\Models\Booking;
use App\Models\GuestCustomer;
use App\Models\User;
use App\Services\TechnicianAvailabilityService;
use App\Services\TechnicianRankingService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;

    protected static ?string $title = 'Create New Booking';

    public function form(Form $form): Form
    {
        return $form->schema([
            $this->getCustomerSection(),
            $this->getServiceSection(),
            $this->getScheduleSection(),
            $this->getBookingSettingsSection(),
        ]);
    }

    protected function getCustomerSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Customer Information')
            ->schema([
                // Customer Type Selection
                Forms\Components\ToggleButtons::make('customer_type')
                    ->label('Customer Type')
                    ->options([
                        'registered' => 'Registered User',
                        'guest' => 'Existing Guest',
                        'new_guest' => 'New Guest',
                    ])
                    ->default('registered')
                    ->reactive()
                    ->inline()
                    ->columnSpanFull(),

                // Registered Customer Selection
                Forms\Components\Select::make('customer_id')
                    ->label('Select Registered Customer')
                    ->relationship('customer', 'name', function ($query) {
                        return $query->where('role', 'customer');
                    })
                    ->searchable()
                    ->preload()
                    ->placeholder('Search by name or email...')
                    ->visible(fn (callable $get) => $get('customer_type') === 'registered')
                    ->required(fn (callable $get) => $get('customer_type') === 'registered')
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        if ($state) {
                            $customer = User::find($state);
                            if ($customer) {
                                $set('customer_mobile', $customer->phone ?? '');
                                if ($customer->house_no_street && ! $get('use_custom_address')) {
                                    // Find province ID by name
                                    if ($customer->province) {
                                        $province = \App\Models\Province::where('name', $customer->province)->first();
                                        if ($province) {
                                            $set('province', $province->id);

                                            // After setting province, find and set city
                                            if ($customer->city_municipality) {
                                                $city = \App\Models\City::where('province_id', $province->id)
                                                    ->where('name', $customer->city_municipality)
                                                    ->first();
                                                if ($city) {
                                                    $set('city_municipality', $city->id);

                                                    // After setting city, find and set barangay
                                                    if ($customer->barangay) {
                                                        $barangay = \App\Models\Barangay::where('city_id', $city->id)
                                                            ->where('name', $customer->barangay)
                                                            ->first();
                                                        $set('barangay', $barangay?->id);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    $set('house_no_street', $customer->house_no_street ?? '');
                                    $set('nearest_landmark', $customer->nearest_landmark ?? '');
                                    $set('use_customer_address', true);
                                }
                            }
                        }
                    })
                    ->columnSpanFull(),

                // Existing Guest Customer Selection
                Forms\Components\Select::make('guest_customer_id')
                    ->label('Select Guest Customer')
                    ->searchable()
                    ->preload()
                    ->options(function () {
                        return GuestCustomer::orderBy('created_at', 'desc')
                            ->limit(10)
                            ->get()
                            ->mapWithKeys(fn (GuestCustomer $guest) => [
                                $guest->id => "{$guest->full_name} - {$guest->phone}",
                            ])
                            ->toArray();
                    })
                    ->getSearchResultsUsing(fn (string $search): array => GuestCustomer::where('phone', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn (GuestCustomer $guest) => [
                            $guest->id => "{$guest->full_name} - {$guest->phone}",
                        ])
                        ->toArray()
                    )
                    ->getOptionLabelUsing(fn ($value): ?string => $value ? GuestCustomer::find($value)?->full_name.' - '.GuestCustomer::find($value)?->phone : null
                    )
                    ->placeholder('Search by name or phone...')
                    ->visible(fn (callable $get) => $get('customer_type') === 'guest')
                    ->required(fn (callable $get) => $get('customer_type') === 'guest')
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        if ($state) {
                            $guest = GuestCustomer::find($state);
                            if ($guest) {
                                $set('guest_first_name', $guest->first_name);
                                $set('guest_middle_initial', $guest->middle_initial);
                                $set('guest_last_name', $guest->last_name);
                                $set('customer_mobile', $guest->phone);
                                $set('guest_email', $guest->email);
                                if (! $get('use_custom_address')) {
                                    // Find province ID by name
                                    if ($guest->province) {
                                        $province = \App\Models\Province::where('name', $guest->province)->first();
                                        $set('province', $province?->id);
                                    }
                                    // Find city ID by name
                                    if ($guest->city_municipality) {
                                        $city = \App\Models\City::where('name', $guest->city_municipality)->first();
                                        $set('city_municipality', $city?->id);
                                    }
                                    // Find barangay ID by name
                                    if ($guest->barangay) {
                                        $barangay = \App\Models\Barangay::where('name', $guest->barangay)->first();
                                        $set('barangay', $barangay?->id);
                                    }
                                    $set('house_no_street', $guest->house_no_street);
                                    $set('nearest_landmark', $guest->nearest_landmark);
                                }
                            }
                        }
                    })
                    ->columnSpanFull(),

                // Guest/New Guest Name Fields
                Forms\Components\TextInput::make('guest_first_name')
                    ->label('First Name')
                    ->required(fn (callable $get) => in_array($get('customer_type'), ['guest', 'new_guest']))
                    ->disabled(fn (callable $get) => $get('customer_type') === 'guest' && $get('guest_customer_id'))
                    ->visible(fn (callable $get) => in_array($get('customer_type'), ['guest', 'new_guest']))
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('guest_middle_initial')
                    ->label('M.I.')
                    ->maxLength(5)
                    ->disabled(fn (callable $get) => $get('customer_type') === 'guest' && $get('guest_customer_id'))
                    ->visible(fn (callable $get) => in_array($get('customer_type'), ['guest', 'new_guest']))
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('guest_last_name')
                    ->label('Last Name')
                    ->required(fn (callable $get) => in_array($get('customer_type'), ['guest', 'new_guest']))
                    ->disabled(fn (callable $get) => $get('customer_type') === 'guest' && $get('guest_customer_id'))
                    ->visible(fn (callable $get) => in_array($get('customer_type'), ['guest', 'new_guest']))
                    ->columnSpanFull(),

                // Guest Email Field
                Forms\Components\TextInput::make('guest_email')
                    ->label('Email Address')
                    ->email()
                    ->placeholder('guest@example.com')
                    ->helperText('Optional - can be used to convert guest to registered user later')
                    ->disabled(fn (callable $get) => $get('customer_type') === 'guest' && $get('guest_customer_id'))
                    ->visible(fn (callable $get) => in_array($get('customer_type'), ['guest', 'new_guest']))
                    ->columnSpanFull(),

                // Address Toggle for Registered and Guest Users
                Forms\Components\Toggle::make('use_custom_address')
                    ->label('Use different address for this booking')
                    ->visible(fn (callable $get) => ($get('customer_type') === 'registered' && $get('customer_id')) ||
                        ($get('customer_type') === 'guest' && $get('guest_customer_id'))
                    )
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        if (! $state) {
                            if ($get('customer_type') === 'registered' && $get('customer_id')) {
                                // Reset to customer's address
                                $customer = User::find($get('customer_id'));
                                if ($customer && $customer->house_no_street) {
                                    // Find province ID by name
                                    if ($customer->province) {
                                        $province = \App\Models\Province::where('name', $customer->province)->first();
                                        $set('province', $province?->id);
                                    }
                                    // Find city ID by name
                                    if ($customer->city_municipality) {
                                        $city = \App\Models\City::where('name', $customer->city_municipality)->first();
                                        $set('city_municipality', $city?->id);
                                    }
                                    // Find barangay ID by name
                                    if ($customer->barangay) {
                                        $barangay = \App\Models\Barangay::where('name', $customer->barangay)->first();
                                        $set('barangay', $barangay?->id);
                                    }
                                    $set('house_no_street', $customer->house_no_street ?? '');
                                    $set('nearest_landmark', $customer->nearest_landmark ?? '');
                                }
                            } elseif ($get('customer_type') === 'guest' && $get('guest_customer_id')) {
                                // Reset to guest's address
                                $guest = GuestCustomer::find($get('guest_customer_id'));
                                if ($guest) {
                                    // Find province ID by name
                                    if ($guest->province) {
                                        $province = \App\Models\Province::where('name', $guest->province)->first();
                                        $set('province', $province?->id);
                                    }
                                    // Find city ID by name
                                    if ($guest->city_municipality) {
                                        $city = \App\Models\City::where('name', $guest->city_municipality)->first();
                                        $set('city_municipality', $city?->id);
                                    }
                                    // Find barangay ID by name
                                    if ($guest->barangay) {
                                        $barangay = \App\Models\Barangay::where('name', $guest->barangay)->first();
                                        $set('barangay', $barangay?->id);
                                    }
                                    $set('house_no_street', $guest->house_no_street ?? '');
                                    $set('nearest_landmark', $guest->nearest_landmark ?? '');
                                }
                            }
                        } else {
                            // Clear address fields for custom input
                            $set('province', '');
                            $set('city_municipality', '');
                            $set('barangay', '');
                            $set('house_no_street', '');
                            $set('nearest_landmark', '');
                        }
                    })
                    ->columnSpanFull(),

                Forms\Components\Hidden::make('use_customer_address')
                    ->default(false),

                Forms\Components\TextInput::make('customer_mobile')
                    ->label('Mobile Number')
                    ->tel()
                    ->placeholder('09123456789')
                    ->helperText('Contact number for service coordination')
                    ->required()
                    ->disabled(fn (callable $get) => ($get('customer_type') === 'registered' && $get('customer_id')) ||
                        ($get('customer_type') === 'guest' && $get('guest_customer_id'))
                    )
                    ->dehydrated(true)
                    ->columnSpanFull(),

                Forms\Components\Select::make('province')
                    ->label('Province')
                    ->searchable()
                    ->preload()
                    ->options(fn (): array => \App\Models\Province::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->getOptionLabelUsing(fn ($value): ?string => \App\Models\Province::find($value)?->name)
                    ->dehydrateStateUsing(fn ($state): ?string => \App\Models\Province::find($state)?->name)
                    ->reactive()
                    ->afterStateUpdated(function (Forms\Set $set): void {
                        $set('city_municipality', null);
                        $set('barangay', null);
                    })
                    ->disabled(fn (callable $get) => ($get('customer_type') === 'registered' && $get('customer_id') && ! $get('use_custom_address')) ||
                        ($get('customer_type') === 'guest' && $get('guest_customer_id') && ! $get('use_custom_address'))
                    )
                    ->required()
                    ->columnSpanFull(),

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
                    ->disabled(fn (Forms\Get $get): bool => ! (bool) $get('province') ||
                        ($get('customer_type') === 'registered' && $get('customer_id') && ! $get('use_custom_address')) ||
                        ($get('customer_type') === 'guest' && $get('guest_customer_id') && ! $get('use_custom_address'))
                    )
                    ->afterStateUpdated(function (Forms\Set $set): void {
                        $set('barangay', null);
                    })
                    ->required()
                    ->columnSpanFull(),

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
                    ->disabled(fn (Forms\Get $get): bool => ! (bool) $get('city_municipality') ||
                        ($get('customer_type') === 'registered' && $get('customer_id') && ! $get('use_custom_address')) ||
                        ($get('customer_type') === 'guest' && $get('guest_customer_id') && ! $get('use_custom_address'))
                    )
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('house_no_street')
                    ->label('House No. & Street')
                    ->required()
                    ->placeholder('e.g., 123 Rizal Street, Block 5 Lot 10, Unit 2A')
                    ->disabled(fn (callable $get) => ($get('customer_type') === 'registered' && $get('customer_id') && ! $get('use_custom_address')) ||
                        ($get('customer_type') === 'guest' && $get('guest_customer_id') && ! $get('use_custom_address'))
                    )
                    ->dehydrated(true)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('nearest_landmark')
                    ->label('Nearest Landmark (Optional)')
                    ->placeholder('e.g., Near SM Mall, Opposite Jollibee, Behind Gas Station')
                    ->disabled(fn (callable $get) => ($get('customer_type') === 'registered' && $get('customer_id') && ! $get('use_custom_address')) ||
                        ($get('customer_type') === 'guest' && $get('guest_customer_id') && ! $get('use_custom_address'))
                    )
                    ->dehydrated(true)
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('customer_address')
                    ->label('Service Location (Auto-generated)')
                    ->placeholder('Will be generated when booking is saved')
                    ->disabled()
                    ->rows(2)
                    ->columnSpanFull(),

            ])
            ->collapsible();
    }

    protected function getServiceSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Service Details')
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

                            // Dynamic scheduling: ranking stub
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
                                // Trigger recompute of start/end when duration changes
                                if (method_exists(static::class, 'recalculateSchedule')) {
                                    try {
                                        static::recalculateSchedule($set, $get);
                                    } catch (\Exception $e) {
                                    }
                                }
                            } else {
                                $set('total_amount', 0);
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

                Forms\Components\TextInput::make('estimated_duration_minutes')
                    ->label('Estimated Duration (minutes)')
                    ->numeric()
                    ->disabled()
                    ->dehydrated()
                    ->helperText('Auto-calculated based on service type and number of units'),
            ]);
    }

    protected function getScheduleSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Schedule')
            ->schema([
                Forms\Components\Group::make([
                    Forms\Components\DatePicker::make('start_date')
                        ->label('Service Date')
                        ->required()
                        ->default(now()->addDay())
                        ->minDate(now())
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if (method_exists(static::class, 'recalculateSchedule')) {
                                static::recalculateSchedule($set, $get);
                            }
                        }),

                    Forms\Components\Select::make('start_time_hour')
                        ->label('Preferred Time')
                        ->options([
                            '08:00' => '08:00 AM',
                            '09:00' => '09:00 AM',
                            '10:00' => '10:00 AM',
                            '11:00' => '11:00 AM',
                            '13:00' => '01:00 PM',
                            '14:00' => '02:00 PM',
                            '15:00' => '03:00 PM',
                            '16:00' => '04:00 PM',
                        ])
                        ->required()
                        ->default('08:00')
                        ->reactive()
                        ->helperText('Service hours: 8AM-12PM, 1PM-5PM')
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if (method_exists(static::class, 'recalculateSchedule')) {
                                static::recalculateSchedule($set, $get);
                            }
                        }),

                    Forms\Components\DateTimePicker::make('scheduled_start_at')
                        ->label('Computed Start')
                        ->disabled()
                        ->dehydrated()
                        ->format('Y-m-d H:i:s')
                        ->seconds(false),

                    Forms\Components\DateTimePicker::make('scheduled_end_at')
                        ->label('Computed End')
                        ->helperText('Auto-calculated for multi-day jobs')
                        ->disabled()
                        ->dehydrated()
                        ->format('Y-m-d H:i:s')
                        ->seconds(false),

                    // Removed technician buffer from planning; service prep covers travel/prep time

                    // Timeslot removed – dynamic scheduling

                    Forms\Components\Select::make('technician_id')
                        ->label('Assign Technician')
                        ->options(function (callable $get) {
                            $serviceId = $get('service_id');
                            $startAt = $get('scheduled_start_at');
                            $endAt = $get('scheduled_end_at');
                            if (! $serviceId || ! $startAt || ! $endAt) {
                                return [];
                            }
                            try {
                                /** @var TechnicianRankingService $ranking */
                                $ranking = app(TechnicianRankingService::class);
                                $ranked = $ranking->getRankedTechniciansForWindow($serviceId, $startAt, $endAt);
                                $options = [];
                                foreach ($ranked as $idx => $tech) {
                                    $rank = $idx + 1;
                                    $rating = number_format($tech->service_specific_rating, 1);
                                    $reviews = (int) $tech->service_review_count;
                                    $options[$tech->id] = "#{$rank} {$tech->user->name} (⭐ {$rating}/5, {$reviews} reviews)";
                                }

                                return $options;
                            } catch (\Throwable $e) {
                                return [];
                            }
                        })
                        ->searchable()
                        ->preload()
                        ->reactive()
                        ->placeholder('Select a technician')
                        ->helperText(function (\Filament\Forms\Get $get) {
                            $startAt = $get('scheduled_start_at');
                            $endAt = $get('scheduled_end_at');
                            if (! $startAt || ! $endAt) {
                                return 'Set schedule first to see available technicians';
                            }
                            try {
                                $avail = app(TechnicianAvailabilityService::class)
                                    ->getAvailableTechniciansCountForWindow($startAt, $endAt);

                                return "Available technicians: {$avail} | Ranked by service expertise";
                            } catch (\Throwable $e) {
                                return 'Set schedule first to see available technicians';
                            }
                        })
                        ->hintAction(
                            Forms\Components\Actions\Action::make('refresh_ranking')
                                ->label('Refresh')
                                ->icon('heroicon-o-arrow-path')
                                ->action(function (callable $set, callable $get) {
                                    // simply re-run options by touching state
                                    $current = $get('technician_id');
                                    $set('technician_id', null);
                                    $set('technician_id', $current);
                                })
                        )
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if ($state) {
                                // No technician buffer; schedule does not depend on technician
                                if (method_exists(static::class, 'recalculateSchedule')) {
                                    static::recalculateSchedule($set, $get);
                                }
                            }
                        })
                        ->columnSpanFull(),
                ])->columns(2),

                Forms\Components\Textarea::make('special_instructions')
                    ->label('Special Instructions')
                    ->placeholder('Any specific requirements or notes for the technician...')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    protected function getBookingSettingsSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Booking Settings')
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
                        ->default('confirmed')
                        ->helperText('Admin-created bookings are automatically confirmed')
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
            ->collapsed();
    }

    protected static function calculateServiceDuration($service, int $numberOfUnits): array
    {
        $baseMinutes = (int) ($service->duration_minutes ?? 60);
        $prepMinutes = (int) ($service->prep_minutes ?? 60);
        $units = max(1, $numberOfUnits);
        // Use simple linear scaling and round up to the nearest hour to keep on-the-hour scheduling
        $rawTotal = $baseMinutes * $units;
        $totalMinutes = (int) (ceil($rawTotal / 60) * 60) + $prepMinutes;

        return [
            'total_minutes' => (int) $totalMinutes,
            'estimated_hours' => round($totalMinutes / 60, 1),
        ];
    }

    protected static function runGreedyAlgorithm($unused, callable $set, callable $get): void
    {
        // Dynamic ranking stub
    }

    protected static function recalculateSchedule(callable $set, callable $get): void
    {
        $date = $get('start_date');
        $time = $get('start_time_hour');
        $duration = (int) ($get('estimated_duration_minutes') ?? 0);
        if (! $date || ! $time || $duration <= 0) {
            return;
        }
        // Parse date safely regardless of display format
        $startAt = null;
        foreach (['Y-m-d', 'd/m/Y', 'm/d/Y'] as $fmt) {
            try {
                $startAt = \Carbon\Carbon::createFromFormat($fmt.' H:i:s', $date.' '.$time.':00');
                break;
            } catch (\Exception $e) {
                // try next format
            }
        }
        if (! $startAt) {
            // Fallback
            $startAt = \Carbon\Carbon::parse($date.' '.$time.':00');
        }
        $padding = (int) ($get('booking_padding_minutes') ?? 0);
        [$start, $end] = static::planSchedule($startAt, $duration, $padding);
        $set('scheduled_start_at', $start);
        $set('scheduled_end_at', $end);
    }

    protected static function planSchedule(\Carbon\Carbon $startAt, int $workMinutes, int $paddingMinutes = 0): array
    {
        $startAt = static::normalizeStart($startAt);
        $current = $startAt->copy();
        $remaining = $workMinutes;
        $hops = 0;
        while ($remaining > 0 && $hops < 1000) {
            [$winStart, $winEnd] = static::currentWindow($current);
            if ($current->lt($winStart)) {
                $current = $winStart->copy();
            }
            // Compute minutes available until the end of the current window
            $available = max(0, $current->diffInMinutes($winEnd, false));
            if ($available <= 0) {
                $current = static::nextWindowStart($current);
                $hops++;

                continue;
            }
            $consume = min($remaining, $available);
            $current->addMinutes($consume);
            $remaining -= $consume;
            if ($remaining > 0) {
                $current = static::nextWindowStart($current);
                $hops++;
            }
        }
        $endWithPadding = static::addBusinessMinutes($current->copy(), $paddingMinutes);

        return [$startAt->format('Y-m-d H:i:s'), $endWithPadding->format('Y-m-d H:i:s')];
    }

    protected static function normalizeStart(\Carbon\Carbon $dt): \Carbon\Carbon
    {
        if ($dt->lt($dt->copy()->setTime(8, 0))) {
            return $dt->copy()->setTime(8, 0);
        }
        if ($dt->gte($dt->copy()->setTime(12, 0)) && $dt->lt($dt->copy()->setTime(13, 0))) {
            return $dt->copy()->setTime(13, 0);
        }
        if ($dt->gte($dt->copy()->setTime(17, 0))) {
            return $dt->copy()->addDay()->setTime(8, 0);
        }

        return $dt;
    }

    protected static function currentWindow(\Carbon\Carbon $dt): array
    {
        $mStart = $dt->copy()->setTime(8, 0);
        $mEnd = $dt->copy()->setTime(12, 0);
        $aStart = $dt->copy()->setTime(13, 0);
        $aEnd = $dt->copy()->setTime(17, 0);

        return $dt->lt($mEnd) ? [$mStart, $mEnd] : [$aStart, $aEnd];
    }

    protected static function nextWindowStart(\Carbon\Carbon $dt): \Carbon\Carbon
    {
        // If exactly at 12:00, this is lunch start – continue at 13:00 same day
        return $dt->lte($dt->copy()->setTime(12, 0))
            ? $dt->copy()->setTime(13, 0)
            : $dt->copy()->addDay()->setTime(8, 0);
    }

    protected static function addBusinessMinutes(\Carbon\Carbon $start, int $minutes): \Carbon\Carbon
    {
        $current = $start->copy();
        $remaining = max(0, $minutes);
        $hops = 0;
        while ($remaining > 0 && $hops < 1000) {
            [$winStart, $winEnd] = static::currentWindow($current);
            if ($current->lt($winStart)) {
                $current = $winStart->copy();
            }
            $available = max(0, $current->diffInMinutes($winEnd, false));
            if ($available <= 0) {
                $current = static::nextWindowStart($current);
                $hops++;

                continue;
            }
            $consume = min($remaining, $available);
            $current->addMinutes($consume);
            $remaining -= $consume;
            if ($remaining > 0) {
                $current = static::nextWindowStart($current);
                $hops++;
            }
        }

        return $current;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        // Ensure admin-created bookings are always confirmed
        $data['status'] = 'confirmed';

        // Handle guest customer creation/linking based on customer type
        if ($data['customer_type'] === 'new_guest') {
            // Create new guest customer
            $guestCustomer = GuestCustomer::create([
                'first_name' => $data['guest_first_name'],
                'middle_initial' => $data['guest_middle_initial'] ?? null,
                'last_name' => $data['guest_last_name'],
                'phone' => $data['customer_mobile'],
                'email' => $data['guest_email'] ?? null,
                'house_no_street' => $data['house_no_street'] ?? null,
                'barangay' => $data['barangay'] ?? null,
                'city_municipality' => $data['city_municipality'] ?? null,
                'province' => $data['province'] ?? null,
                'nearest_landmark' => $data['nearest_landmark'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $data['guest_customer_id'] = $guestCustomer->id;
            $data['customer_id'] = null;
        } elseif ($data['customer_type'] === 'guest') {
            // Using existing guest customer
            $data['customer_id'] = null;
            // guest_customer_id is already set from the form

            // Increment booking count for existing guest
            if (! empty($data['guest_customer_id'])) {
                $guestCustomer = GuestCustomer::find($data['guest_customer_id']);
                if ($guestCustomer) {
                    $guestCustomer->incrementBookingCount();
                }
            }
        } else {
            // Registered user
            $data['guest_customer_id'] = null;
            // customer_id is already set from the form
        }

        // Remove temporary form fields
        unset($data['customer_type']);
        unset($data['guest_first_name']);
        unset($data['guest_middle_initial']);
        unset($data['guest_last_name']);
        unset($data['guest_email']);

        // Generate address
        if (! empty($data['province']) && ! empty($data['city_municipality'])) {
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

    protected function afterCreate(): void
    {
        $booking = $this->record;

        // Send SMS notification for new confirmed booking
        $smsService = new \App\Services\SemaphoreSmsService;
        $smsService->sendNewBookingCreated($booking);

        if ($booking->guest_customer_id) {
            $guestName = $booking->guestCustomer->full_name;
            Notification::make()
                ->title('Confirmed Booking Created')
                ->body("Confirmed booking created for guest customer: {$guestName}")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Confirmed Booking Created')
                ->body("Booking #{$booking->booking_number} has been created and confirmed")
                ->success()
                ->send();
        }
    }
}
