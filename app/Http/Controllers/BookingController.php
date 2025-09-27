<?php

namespace App\Http\Controllers;

use App\Models\AirconType;
use App\Models\Booking;
use App\Models\Promotion;
use App\Models\Service;
use App\Services\TechnicianAvailabilityService;
use App\Services\TechnicianRankingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    protected TechnicianAvailabilityService $availabilityService;

    protected TechnicianRankingService $rankingService;

    public function __construct(
        TechnicianAvailabilityService $availabilityService,
        TechnicianRankingService $rankingService
    ) {
        $this->availabilityService = $availabilityService;
        $this->rankingService = $rankingService;
    }

    /**
     * Show the booking form with dynamic data
     */
    public function create(): Response
    {
        try {
            // Get all active services with base information
            $services = Service::active()
                ->get()
                ->map(function ($service) {
                    return [
                        'id' => $service->id,
                        'name' => $service->name,
                        'description' => $service->description,
                        'base_price' => $service->base_price,
                        'duration_minutes' => $service->duration_minutes,
                        'category' => $service->category,
                    ];
                });

            // Get all aircon types
            $airconTypes = AirconType::all()->map(function ($type) {
                return [
                    'id' => $type->id,
                    'name' => $type->name,
                    'description' => $type->description,
                ];
            });

            // Available AC brands (from your admin panel)
            $brands = [
                'Samsung', 'LG', 'Carrier', 'Daikin', 'Panasonic', 'Sharp',
                'Kolin', 'Koppel', 'Condura', 'Hitachi', 'TCL', 'Haier',
                'Fujidenzo', 'Unknown', 'Not Sure', 'Multiple Brands',
            ];

            // Philippine provinces (focused on your service areas)
            $provinces = [
                'Bataan', 'Pampanga', 'Bulacan', 'Nueva Ecija', 'Tarlac', 'Zambales',
            ];

            // Municipalities per province (from your existing data)
            $municipalities = [
                'Bataan' => [
                    'Balanga', 'Mariveles', 'Dinalupihan', 'Hermosa', 'Orani',
                    'Samal', 'Abucay', 'Pilar', 'Orion', 'Limay', 'Bagac', 'Morong',
                ],
                'Pampanga' => [
                    'Angeles', 'San Fernando', 'Mabalacat', 'Mexico', 'San Luis',
                    'Guagua', 'Apalit', 'Candaba', 'Floridablanca', 'Lubao',
                ],
                // Add other provinces as needed
            ];

            return Inertia::render('booking', [
                'services' => $services,
                'airconTypes' => $airconTypes,
                // Dynamic scheduling – no timeslots
                'brands' => $brands,
                'provinces' => $provinces,
                'municipalities' => $municipalities,
                'auth' => [
                    'user' => auth()->user() ? [
                        'id' => auth()->user()->id,
                        'name' => auth()->user()->name,
                        'first_name' => auth()->user()->first_name,
                        'middle_initial' => auth()->user()->middle_initial,
                        'last_name' => auth()->user()->last_name,
                        'email' => auth()->user()->email,
                        'phone' => auth()->user()->phone,
                        'house_no_street' => auth()->user()->house_no_street,
                        'barangay' => auth()->user()->barangay,
                        'city_municipality' => auth()->user()->city_municipality,
                        'province' => auth()->user()->province,
                        'nearest_landmark' => auth()->user()->nearest_landmark,
                        'full_address' => auth()->user()->full_address,
                    ] : null,
                ],
                'booking' => session('booking'),
                'booking_success' => session('booking_success'),
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading booking page', ['error' => $e->getMessage()]);

            return Inertia::render('booking', [
                'error' => 'Failed to load booking data. Please try again.',
                'services' => [],
                'airconTypes' => [],
                // Dynamic scheduling – no timeslots
                'brands' => [],
                'provinces' => [],
                'municipalities' => [],
                'auth' => ['user' => null],
            ]);
        }
    }

    /**
     * Check availability for dynamic time window
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        // Keep backward compatibility for old timeslot-based calls
        if ($request->has('date') && ! $request->has('start_datetime')) {
            return response()->json([
                'success' => false,
                'message' => 'Please use the new dynamic scheduling API',
            ], 410);
        }

        // New dynamic availability check
        $validator = Validator::make($request->all(), [
            'start_datetime' => 'required|date_format:Y-m-d H:i:s',
            'end_datetime' => 'required|date_format:Y-m-d H:i:s|after:start_datetime',
            'service_id' => 'nullable|exists:services,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $startAt = $request->input('start_datetime');
            $endAt = $request->input('end_datetime');

            // Use TechnicianAvailabilityService exactly like admin panel
            $availableTechnicians = $this->availabilityService->getAvailableTechniciansForWindow($startAt, $endAt);
            $availableCount = $availableTechnicians->count();

            Log::info('Availability check for window', [
                'start' => $startAt,
                'end' => $endAt,
                'available_count' => $availableCount,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'start_datetime' => $startAt,
                    'end_datetime' => $endAt,
                    'available_count' => $availableCount,
                    'is_available' => $availableCount > 0,
                    'technicians' => $availableTechnicians->pluck('user.name', 'id'), // Include technician names for debugging
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error checking availability', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to check availability: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get ranked technicians for dynamic time window
     */
    public function getTechnicianRanking(Request $request): JsonResponse
    {
        // Keep backward compatibility
        if ($request->has('date') && ! $request->has('start_datetime')) {
            return response()->json([
                'success' => false,
                'message' => 'Please use the new dynamic scheduling API',
            ], 410);
        }

        $validator = Validator::make($request->all(), [
            'start_datetime' => 'required|date_format:Y-m-d H:i:s',
            'end_datetime' => 'required|date_format:Y-m-d H:i:s|after:start_datetime',
            'service_id' => 'required|exists:services,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $startAt = $request->input('start_datetime');
            $endAt = $request->input('end_datetime');
            $serviceId = $request->input('service_id');

            // Use TechnicianRankingService exactly like admin panel does
            // This uses the Pure Service-Rating Algorithm
            $rankedTechnicians = $this->rankingService->getRankedTechniciansForWindow(
                $serviceId,
                $startAt,
                $endAt,
                null, // No customer lat (removed GPS)
                null  // No customer lng (removed GPS)
            );

            // Format response for frontend
            $technicians = $rankedTechnicians->map(function ($technician, $index) use ($serviceId) {
                return [
                    'id' => $technician->id,
                    'name' => $technician->user->name,
                    'rating' => round($technician->service_specific_rating ?? $technician->rating_average, 1),
                    'rank' => $index + 1,
                    'greedy_score' => round($technician->greedy_score, 3),
                    'service_review_count' => $technician->service_review_count ?? 0,
                    'service_completed_jobs' => $technician->service_completed_jobs ?? 0,
                    'experience' => $this->formatExperience($technician),
                    'specializations' => $this->getTechnicianSpecializations($technician, $serviceId),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'technicians' => $technicians,
                    'count' => $technicians->count(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting technician ranking', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get technician ranking',
            ], 500);
        }
    }

    /**
     * Calculate service end time based on duration
     */
    public function calculateEndTime(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_datetime' => 'required|date_format:Y-m-d H:i:s',
            'service_id' => 'required|exists:services,id',
            'number_of_units' => 'required|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $service = Service::find($request->input('service_id'));
            $startDateTime = \Carbon\Carbon::parse($request->input('start_datetime'));
            $units = $request->input('number_of_units');

            // Calculate total duration using admin panel logic
            $baseDuration = $service->duration_minutes ?? 60;
            $prepMinutes = $service->prep_minutes ?? 60;

            // Use simple linear scaling and round up to nearest hour like admin panel
            $rawTotal = $baseDuration * $units;
            $totalMinutes = (int) (ceil($rawTotal / 60) * 60) + $prepMinutes;

            // Use business hours calculation like admin panel
            [$scheduledStart, $scheduledEnd] = $this->planSchedule($startDateTime, $totalMinutes);

            // Parse the calculated end time
            $endDateTime = \Carbon\Carbon::parse($scheduledEnd);

            // Check if service spans multiple days
            $estimatedDays = $startDateTime->diffInDays($endDateTime) + 1;

            return response()->json([
                'success' => true,
                'data' => [
                    'start_datetime' => $scheduledStart,
                    'end_datetime' => $scheduledEnd,
                    'duration_minutes' => $totalMinutes,
                    'estimated_days' => $estimatedDays,
                    'service_name' => $service->name,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error calculating end time', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate end time',
            ], 500);
        }
    }

    /**
     * Plan schedule respecting business hours (8AM-12PM, 1PM-5PM)
     */
    protected function planSchedule(\Carbon\Carbon $startAt, int $workMinutes, int $paddingMinutes = 0): array
    {
        $startAt = $this->normalizeStart($startAt);
        $current = $startAt->copy();
        $remaining = $workMinutes;
        $hops = 0;

        while ($remaining > 0 && $hops < 1000) {
            [$winStart, $winEnd] = $this->currentWindow($current);
            if ($current->lt($winStart)) {
                $current = $winStart->copy();
            }

            // Compute minutes available until the end of the current window
            $available = max(0, $current->diffInMinutes($winEnd, false));
            if ($available <= 0) {
                $current = $this->nextWindowStart($current);
                $hops++;

                continue;
            }

            $consume = min($remaining, $available);
            $current->addMinutes($consume);
            $remaining -= $consume;

            if ($remaining > 0) {
                $current = $this->nextWindowStart($current);
                $hops++;
            }
        }

        $endWithPadding = $this->addBusinessMinutes($current->copy(), $paddingMinutes);

        return [$startAt->format('Y-m-d H:i:s'), $endWithPadding->format('Y-m-d H:i:s')];
    }

    /**
     * Normalize start time to business hours
     */
    protected function normalizeStart(\Carbon\Carbon $dt): \Carbon\Carbon
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

    /**
     * Get current business window (morning or afternoon)
     */
    protected function currentWindow(\Carbon\Carbon $dt): array
    {
        $mStart = $dt->copy()->setTime(8, 0);
        $mEnd = $dt->copy()->setTime(12, 0);
        $aStart = $dt->copy()->setTime(13, 0);
        $aEnd = $dt->copy()->setTime(17, 0);

        return $dt->lt($mEnd) ? [$mStart, $mEnd] : [$aStart, $aEnd];
    }

    /**
     * Get next business window start
     */
    protected function nextWindowStart(\Carbon\Carbon $dt): \Carbon\Carbon
    {
        // If exactly at 12:00, this is lunch start – continue at 13:00 same day
        return $dt->lte($dt->copy()->setTime(12, 0))
            ? $dt->copy()->setTime(13, 0)
            : $dt->copy()->addDay()->setTime(8, 0);
    }

    /**
     * Add business minutes to a datetime
     */
    protected function addBusinessMinutes(\Carbon\Carbon $start, int $minutes): \Carbon\Carbon
    {
        $current = $start->copy();
        $remaining = max(0, $minutes);
        $hops = 0;

        while ($remaining > 0 && $hops < 1000) {
            [$winStart, $winEnd] = $this->currentWindow($current);
            if ($current->lt($winStart)) {
                $current = $winStart->copy();
            }

            $available = max(0, $current->diffInMinutes($winEnd, false));
            if ($available <= 0) {
                $current = $this->nextWindowStart($current);
                $hops++;

                continue;
            }

            $consume = min($remaining, $available);
            $current->addMinutes($consume);
            $remaining -= $consume;

            if ($remaining > 0) {
                $current = $this->nextWindowStart($current);
                $hops++;
            }
        }

        return $current;
    }

    /**
     * Calculate dynamic pricing (AJAX endpoint)
     */
    public function calculatePricing(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service_id' => 'required|exists:services,id',
            'aircon_type_id' => 'required|exists:aircon_types,id',
            'number_of_units' => 'required|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request data',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Create a new Booking instance and set the attributes
            $booking = new Booking;
            $booking->service_id = $request->input('service_id');
            $booking->aircon_type_id = $request->input('aircon_type_id');
            $booking->number_of_units = $request->input('number_of_units');

            $totalAmount = $booking->calculateTotalAmount();

            // Get pricing breakdown for transparency
            $service = Service::find($request->input('service_id'));
            $airconType = AirconType::find($request->input('aircon_type_id'));
            $units = $request->input('number_of_units');

            // Calculate simple pricing (base price * units)
            $basePrice = \App\Models\ServicePricing::getPricing($request->input('service_id'), $request->input('aircon_type_id'));
            $totalAmount = $basePrice * $units;

            return response()->json([
                'success' => true,
                'data' => [
                    'service_name' => $service->name,
                    'aircon_type_name' => $airconType->name,
                    'number_of_units' => $units,
                    'base_price' => $basePrice,
                    'total_amount' => round($totalAmount, 2),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error calculating pricing', [
                'error' => $e->getMessage(),
                'service_id' => $request->input('service_id'),
                'aircon_type_id' => $request->input('aircon_type_id'),
                'number_of_units' => $request->input('number_of_units'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate pricing',
            ], 500);
        }
    }

    /**
     * Store the booking (same logic as admin panel)
     */
    public function store(Request $request)
    {
        // Log the incoming request for debugging
        Log::info('DEBUG: Booking submission received', [
            'request_data' => $request->all(),
            'user_authenticated' => auth()->check(),
            'user_id' => auth()->id(),
        ]);

        // Use the same validation as your admin panel CustomerBookingResource
        try {
            $validated = $request->validate([
                'service_id' => 'required|exists:services,id',
                'aircon_type_id' => 'required|exists:aircon_types,id',
                'number_of_units' => 'required|integer|min:1|max:50',
                'ac_brand' => 'nullable|string|max:255',
                'scheduled_start_at' => 'required|date_format:Y-m-d H:i:s|after:today',
                'scheduled_end_at' => 'required|date_format:Y-m-d H:i:s|after:scheduled_start_at',
                'technician_id' => 'required|exists:technicians,id',
                'customer_mobile' => 'nullable|string|max:20',
                'province' => 'required|string|max:255',
                'city_municipality' => 'required|string|max:255',
                'barangay' => 'required|string|max:255',
                'house_no_street' => 'required|string|max:255',
                'nearest_landmark' => 'nullable|string|max:255',
                'use_custom_address' => 'nullable|boolean',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Booking validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
            ]);
            throw $e; // Re-throw to let Laravel handle the response
        }

        try {
            // Set customer data
            if (auth()->check()) {
                $validated['customer_id'] = auth()->id();
                $useCustomAddress = $request->input('use_custom_address', false);

                // If not using custom address, use the customer's registered address
                if (! $useCustomAddress) {
                    $customer = auth()->user();
                    if ($customer->house_no_street) {
                        $validated['province'] = $customer->province;
                        $validated['city_municipality'] = $customer->city_municipality;
                        $validated['barangay'] = $customer->barangay;
                        $validated['house_no_street'] = $customer->house_no_street;
                        $validated['nearest_landmark'] = $customer->nearest_landmark;
                    }
                }
            } else {
                // Guest booking
                $validated['customer_name'] = $request->input('customer_name', 'Guest Customer');
            }

            $validated['created_by'] = auth()->id() ?? 1; // Fallback to admin for guests
            $validated['status'] = 'pending';
            $validated['payment_status'] = 'pending';

            // Create booking (will auto-calculate total_amount, duration, etc.)
            $booking = Booking::create($validated);

            // Flash the booking data and redirect back to booking page
            session()->flash('booking_success', [
                'booking_number' => $booking->booking_number,
                'id' => $booking->id,
                'total_amount' => $booking->total_amount,
                'service_name' => $booking->service->name,
                'scheduled_start_at' => $booking->scheduled_start_at,
                'scheduled_end_at' => $booking->scheduled_end_at,
                'message' => "Booking {$booking->booking_number} created successfully!",
            ]);

        try {
            // Prepare friendly summary for email
            $summary = [
                'service' => optional($booking->service)->name,
                'aircon' => optional($booking->airconType)->name,
                'units' => $booking->number_of_units,
                'start' => optional($booking->scheduled_start_at)->format('M j, Y g:i A'),
                'end' => optional($booking->scheduled_end_at)->format('M j, Y g:i A'),
                'address' => $booking->service_location,
                'total' => $booking->total_amount,
            ];

            // Get current active promotions from the DB formatted like the booking page
            $promotions = \App\Models\Promotion::query()
                ->where(function ($q) {
                    $today = now()->toDateString();
                    $q->whereNull('start_date')->orWhere('start_date', '<=', $today);
                })
                ->where(function ($q) {
                    $today = now()->toDateString();
                    $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
                })
                ->where('is_active', true)
                ->orderBy('display_order')
                ->limit(6)
                ->get()
                ->map(function ($p) {
                    return [
                        'title' => $p->title,
                        'formatted_discount' => $p->formatted_discount,
                    ];
                })
                ->values()
                ->toArray();

            // Notify customer using same email stack as OTP (Laravel Notifications)
            if ($booking->customer) {
                $booking->customer->notify(new \App\Notifications\BookingConfirmationNotification($summary, $promotions));
            } elseif ($booking->guest_customer_id && $booking->customer_email) {
                // Fallback when guest provided email only
                \Illuminate\Support\Facades\Mail::to($booking->customer_email)
                    ->send((new \App\Notifications\BookingConfirmationNotification($summary, $promotions))->toMail((object) ['name' => $booking->customer_name]));
            }
        } catch (\Throwable $e) {
            \Log::warning('Booking confirmation email failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }

            // Redirect back with success message
            return redirect()->route('booking')->with('booking', [
                'booking_number' => $booking->booking_number,
                'id' => $booking->id,
                'total_amount' => $booking->total_amount,
                'service_name' => $booking->service->name,
                'scheduled_start_at' => $booking->scheduled_start_at,
                'scheduled_end_at' => $booking->scheduled_end_at,
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating booking', [
                'error' => $e->getMessage(),
                'data' => $validated,
            ]);

            return back()->withErrors(['error' => 'Failed to create booking. Please try again.'])
                ->withInput();
        }
    }

    /**
     * Helper: Format technician experience
     */
    private function formatExperience($technician): string
    {
        $years = now()->diffInYears($technician->hire_date);

        return $years > 0 ? "{$years} years" : '< 1 year';
    }

    /**
     * Helper: Get technician specializations based on service
     */
    private function getTechnicianSpecializations($technician, $serviceId): array
    {
        // You can enhance this based on your service categorization
        $service = Service::find($serviceId);

        // For now, return the service category or default specializations
        return [$service->name, $service->category ?? 'AC Maintenance'];
    }

    /**
     * Helper methods for data retrieval
     */
    private function getServicesData()
    {
        return Service::active()->get()->map(function ($service) {
            return [
                'id' => $service->id,
                'name' => $service->name,
                'description' => $service->description,
                'base_price' => $service->base_price,
                'duration_minutes' => $service->duration_minutes,
                'category' => $service->category,
            ];
        });
    }

    private function getAirconTypesData()
    {
        return AirconType::all()->map(function ($type) {
            return [
                'id' => $type->id,
                'name' => $type->name,
                'description' => $type->description,
            ];
        });
    }

    // Removed timeslot data provider – dynamic scheduling in use

    private function getBrandsData()
    {
        return [
            'Samsung', 'LG', 'Carrier', 'Daikin', 'Panasonic', 'Sharp',
            'Kolin', 'Koppel', 'Condura', 'Hitachi', 'TCL', 'Haier',
            'Fujidenzo', 'Unknown', 'Not Sure', 'Multiple Brands',
        ];
    }

    private function getProvincesData()
    {
        return [
            'Bataan', 'Pampanga', 'Bulacan', 'Nueva Ecija', 'Tarlac', 'Zambales',
        ];
    }

    private function getMunicipalitiesData()
    {
        return [
            'Bataan' => [
                'Balanga', 'Mariveles', 'Dinalupihan', 'Hermosa', 'Orani',
                'Samal', 'Abucay', 'Pilar', 'Orion', 'Limay', 'Bagac', 'Morong',
            ],
            'Pampanga' => [
                'Angeles', 'San Fernando', 'Mabalacat', 'Mexico', 'San Luis',
                'Guagua', 'Apalit', 'Candaba', 'Floridablanca', 'Lubao',
            ],
            // Add other provinces as needed
        ];
    }

    /**
     * Get available promotions for a service/aircon type
     */
    public function getAvailablePromotions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service_id' => 'nullable|exists:services,id',
            'aircon_type_id' => 'nullable|exists:aircon_types,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $serviceId = $request->input('service_id');
            $airconTypeId = $request->input('aircon_type_id');

            // Get active promotions
            $promotions = Promotion::active()
                ->where('discount_type', '!=', null)
                ->where('discount_value', '>', 0)
                ->get()
                ->filter(function ($promotion) use ($serviceId, $airconTypeId) {
                    // Check if promotion applies to all services or specific service
                    $serviceMatch = empty($promotion->applicable_services) ||
                                  ($serviceId && in_array($serviceId, $promotion->applicable_services));

                    // Check if promotion applies to all aircon types or specific type
                    $airconMatch = empty($promotion->applicable_aircon_types) ||
                                 ($airconTypeId && in_array($airconTypeId, $promotion->applicable_aircon_types));

                    return $serviceMatch && $airconMatch;
                })
                ->map(function ($promotion) {
                    // Create simplified discount text
                    $simplifiedDiscount = match ($promotion->discount_type) {
                        'percentage' => $promotion->discount_value.'% OFF',
                        'fixed' => '₱'.number_format((float) $promotion->discount_value).' OFF',
                        'free_service' => 'FREE SERVICE',
                        default => $promotion->formatted_discount
                    };

                    return [
                        'id' => $promotion->id,
                        'title' => $promotion->title,
                        'subtitle' => $promotion->subtitle,
                        'discount_type' => $promotion->discount_type,
                        'discount_value' => $promotion->discount_value,
                        'formatted_discount' => $simplifiedDiscount,
                        'promo_code' => $promotion->promo_code,
                        'start_date' => $promotion->start_date?->format('Y-m-d'),
                        'end_date' => $promotion->end_date?->format('Y-m-d'),
                        'is_active' => $promotion->is_active,
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'promotions' => $promotions,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting promotions', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get promotions',
            ], 500);
        }
    }

    /**
     * Customer requests cancellation (no time gating; admin will accept/decline)
     */
    public function requestCancellation(Request $request, Booking $booking): JsonResponse
    {
        // No time gating; admin panel will review and accept/decline

        // Validate ownership (for logged in users)
        if (auth()->check() && $booking->customer_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Check if already requested
        if ($booking->status === 'cancel_requested') {
            return response()->json([
                'success' => false,
                'message' => 'Cancellation already requested',
            ], 422);
        }

        $validated = $request->validate([
            'reason_category' => 'required|in:personal,schedule_conflict,emergency,weather,other',
            'reason_details' => 'required|string|min:10|max:500',
        ]);

        $booking->update([
            'status' => 'cancel_requested',
            'cancellation_reason' => $validated['reason_category'],
            'cancellation_details' => $validated['reason_details'],
            'cancellation_requested_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cancellation request submitted successfully',
        ]);
    }
}
