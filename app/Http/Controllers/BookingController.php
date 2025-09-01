<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\AirconType;
use App\Models\Timeslot;
use App\Models\Booking;
use App\Services\TechnicianAvailabilityService;
use App\Services\TechnicianRankingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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

            // Get all timeslots
            $timeslots = Timeslot::orderBy('start_time')->get()->map(function ($timeslot) {
                return [
                    'id' => $timeslot->id,
                    'display_time' => $timeslot->display_time,
                    'start_time' => $timeslot->start_time,
                    'end_time' => $timeslot->end_time,
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
                'Bataan', 'Pampanga', 'Bulacan', 'Nueva Ecija', 'Tarlac', 'Zambales'
            ];

            // Municipalities per province (from your existing data)
            $municipalities = [
                'Bataan' => [
                    'Balanga', 'Mariveles', 'Dinalupihan', 'Hermosa', 'Orani', 
                    'Samal', 'Abucay', 'Pilar', 'Orion', 'Limay', 'Bagac', 'Morong'
                ],
                'Pampanga' => [
                    'Angeles', 'San Fernando', 'Mabalacat', 'Mexico', 'San Luis', 
                    'Guagua', 'Apalit', 'Candaba', 'Floridablanca', 'Lubao'
                ],
                // Add other provinces as needed
            ];

            return Inertia::render('booking', [
                'services' => $services,
                'airconTypes' => $airconTypes,
                'timeslots' => $timeslots,
                'brands' => $brands,
                'provinces' => $provinces,
                'municipalities' => $municipalities,
                'auth' => [
                    'user' => auth()->user() ? [
                        'id' => auth()->user()->id,
                        'name' => auth()->user()->name,
                        'email' => auth()->user()->email,
                        'phone' => auth()->user()->phone,
                        'province' => auth()->user()->province,
                        'city_municipality' => auth()->user()->city_municipality,
                        'barangay' => auth()->user()->barangay,
                        'house_no_street' => auth()->user()->house_no_street,
                        'formatted_address' => auth()->user()->formatted_address,
                        'has_structured_address' => auth()->user()->hasStructuredAddress(),
                    ] : null
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading booking page', ['error' => $e->getMessage()]);
            
            return Inertia::render('booking', [
                'error' => 'Failed to load booking data. Please try again.',
                'services' => [],
                'airconTypes' => [],
                'timeslots' => [],
                'brands' => [],
                'provinces' => [],
                'municipalities' => [],
                'auth' => ['user' => null]
            ]);
        }
    }

    /**
     * Check real-time availability for timeslots (AJAX endpoint)
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date|after_or_equal:today',
            'timeslot_id' => 'nullable|exists:timeslots,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request data',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $date = $request->input('date');
            $timeslotId = $request->input('timeslot_id');

            if ($timeslotId) {
                // Check specific timeslot
                $availableCount = $this->availabilityService->getAvailableTechniciansCount($date, $timeslotId);
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'date' => $date,
                        'timeslot_id' => $timeslotId,
                        'available_count' => $availableCount,
                        'is_available' => $availableCount > 0
                    ]
                ]);
            } else {
                // Check all timeslots for the date
                $timeslots = Timeslot::orderBy('start_time')->get();
                $availability = [];

                foreach ($timeslots as $timeslot) {
                    $availableCount = $this->availabilityService->getAvailableTechniciansCount($date, $timeslot->id);
                    $availability[$timeslot->id] = [
                        'timeslot_id' => $timeslot->id,
                        'display_time' => $timeslot->display_time,
                        'available_count' => $availableCount,
                        'is_available' => $availableCount > 0
                    ];
                }

                return response()->json([
                    'success' => true,
                    'data' => [
                        'date' => $date,
                        'availability' => $availability
                    ]
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error checking availability', [
                'error' => $e->getMessage(),
                'date' => $request->input('date'),
                'timeslot_id' => $request->input('timeslot_id')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to check availability'
            ], 500);
        }
    }

    /**
     * Get ranked technicians using Greedy Algorithm (AJAX endpoint)
     */
    public function getTechnicianRanking(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service_id' => 'required|exists:services,id',
            'date' => 'required|date|after_or_equal:today',
            'timeslot_id' => 'required|exists:timeslots,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request data',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $serviceId = $request->input('service_id');
            $date = $request->input('date');
            $timeslotId = $request->input('timeslot_id');

            // Execute Greedy Algorithm (same as admin panel)
            $rankedTechnicians = $this->rankingService->getRankedTechniciansForService(
                $serviceId,
                $date,
                $timeslotId
            );

            // Debug logging to track technician count
            Log::info('DEBUG: Technician ranking results', [
                'service_id' => $serviceId,
                'date' => $date,
                'timeslot_id' => $timeslotId,
                'total_technicians_returned' => $rankedTechnicians->count(),
                'technician_ids' => $rankedTechnicians->pluck('id')->toArray(),
                'technician_names' => $rankedTechnicians->pluck('user.name')->toArray()
            ]);

            // Format for frontend (same format as your static technician array)
            $technicianData = $rankedTechnicians->map(function ($technician, $index) use ($serviceId) {
                $rank = $index + 1;
                
                return [
                    'id' => (string) $technician->id, // Keep as string to match your existing format
                    'name' => $technician->user->name,
                    'rating' => round($technician->service_specific_rating, 1),
                    'experience' => $this->formatExperience($technician),
                    'specializations' => $this->getTechnicianSpecializations($technician, $serviceId),
                    // Additional data for transparency
                    'rank' => $rank,
                    'greedy_score' => round($technician->greedy_score, 3),
                    'service_review_count' => $technician->service_review_count,
                    'service_completed_jobs' => $technician->service_completed_jobs ?? 0,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'service_id' => $serviceId,
                    'date' => $date,
                    'timeslot_id' => $timeslotId,
                    'technicians' => $technicianData,
                    'algorithm_used' => 'Pure Service-Rating Algorithm (100% Service Expertise)'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting technician ranking', [
                'error' => $e->getMessage(),
                'service_id' => $request->input('service_id'),
                'date' => $request->input('date'),
                'timeslot_id' => $request->input('timeslot_id')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get technician ranking'
            ], 500);
        }
    }

    /**
     * Calculate dynamic pricing (AJAX endpoint)
     */
    public function calculatePricing(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service_id' => 'required|exists:services,id',
            'aircon_type_id' => 'required|exists:aircon_types,id',
            'number_of_units' => 'required|integer|min:1|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request data',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Create a new Booking instance and set the attributes
            $booking = new Booking();
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
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error calculating pricing', [
                'error' => $e->getMessage(),
                'service_id' => $request->input('service_id'),
                'aircon_type_id' => $request->input('aircon_type_id'),
                'number_of_units' => $request->input('number_of_units')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate pricing'
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
            'user_id' => auth()->id()
        ]);

        // Use the same validation as your admin panel CustomerBookingResource
        try {
            $validated = $request->validate([
                'service_id' => 'required|exists:services,id',
                'aircon_type_id' => 'required|exists:aircon_types,id',
                'number_of_units' => 'required|integer|min:1|max:50',
                'ac_brand' => 'nullable|string|max:255',
                'scheduled_date' => 'required|date|after_or_equal:today',
                'timeslot_id' => 'required|exists:timeslots,id',
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
                'request_data' => $request->all()
            ]);
            throw $e; // Re-throw to let Laravel handle the response
        }

        try {
            // Set customer data
            if (auth()->check()) {
                $validated['customer_id'] = auth()->id();
                $validated['use_custom_address'] = $request->input('use_custom_address', false);
            } else {
                // Guest booking
                $validated['customer_name'] = $request->input('customer_name', 'Guest Customer');
                $validated['use_custom_address'] = true; // Guests always use custom address
            }

            $validated['created_by'] = auth()->id() ?? 1; // Fallback to admin for guests
            $validated['status'] = 'pending';
            $validated['payment_status'] = 'pending';

            // Create booking (will auto-calculate total_amount, duration, etc.)
            $booking = Booking::create($validated);

            // Return booking data to frontend for success modal using Inertia redirect
            return Inertia::render('booking', [
                'services' => $this->getServicesData(),
                'airconTypes' => $this->getAirconTypesData(), 
                'timeslots' => $this->getTimeslotsData(),
                'brands' => $this->getBrandsData(),
                'provinces' => $this->getProvincesData(),
                'municipalities' => $this->getMunicipalitiesData(),
                'auth' => [
                    'user' => auth()->user() ? [
                        'id' => auth()->user()->id,
                        'name' => auth()->user()->name,
                        'email' => auth()->user()->email,
                        'phone' => auth()->user()->phone,
                        'province' => auth()->user()->province,
                        'city_municipality' => auth()->user()->city_municipality,
                        'barangay' => auth()->user()->barangay,
                        'house_no_street' => auth()->user()->house_no_street,
                        'formatted_address' => auth()->user()->formatted_address,
                        'has_structured_address' => auth()->user()->hasStructuredAddress(),
                    ] : null
                ],
                'booking' => [
                    'booking_number' => $booking->booking_number,
                    'id' => $booking->id,
                    'total_amount' => $booking->total_amount,
                    'service_name' => $booking->service->name,
                    'scheduled_date' => $booking->scheduled_date,
                    'message' => "Booking {$booking->booking_number} created successfully!"
                ],
                'success' => true
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating booking', [
                'error' => $e->getMessage(),
                'data' => $validated
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
        return $years > 0 ? "{$years} years" : "< 1 year";
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

    private function getTimeslotsData()
    {
        return Timeslot::orderBy('start_time')->get()->map(function ($timeslot) {
            return [
                'id' => $timeslot->id,
                'display_time' => $timeslot->display_time,
                'start_time' => $timeslot->start_time,
                'end_time' => $timeslot->end_time,
            ];
        });
    }

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
            'Bataan', 'Pampanga', 'Bulacan', 'Nueva Ecija', 'Tarlac', 'Zambales'
        ];
    }

    private function getMunicipalitiesData()
    {
        return [
            'Bataan' => [
                'Balanga', 'Mariveles', 'Dinalupihan', 'Hermosa', 'Orani', 
                'Samal', 'Abucay', 'Pilar', 'Orion', 'Limay', 'Bagac', 'Morong'
            ],
            'Pampanga' => [
                'Angeles', 'San Fernando', 'Mabalacat', 'Mexico', 'San Luis', 
                'Guagua', 'Apalit', 'Candaba', 'Floridablanca', 'Lubao'
            ],
            // Add other provinces as needed
        ];
    }

    /**
     * Customer requests cancellation (24-hour rule enforced)
     */
    public function requestCancellation(Request $request, Booking $booking): JsonResponse
    {
        // Validate 24-hour rule
        if ($booking->scheduled_date <= now()->addDay()) {
            return response()->json([
                'success' => false,
                'message' => 'Cancellation not allowed - booking is within 24 hours'
            ], 422);
        }
        
        // Validate ownership (for logged in users)
        if (auth()->check() && $booking->customer_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        
        // Check if already requested
        if ($booking->status === 'cancel_requested') {
            return response()->json([
                'success' => false,
                'message' => 'Cancellation already requested'
            ], 422);
        }
        
        $validated = $request->validate([
            'reason_category' => 'required|in:personal,schedule_conflict,emergency,weather,other',
            'reason_details' => 'required|string|min:10|max:500'
        ]);
        
        $booking->update([
            'status' => 'cancel_requested',
            'cancellation_reason' => $validated['reason_category'],
            'cancellation_details' => $validated['reason_details'],
            'cancellation_requested_at' => now()
        ]);
        
        return response()->json([
            'success' => true, 
            'message' => 'Cancellation request submitted successfully'
        ]);
    }
}