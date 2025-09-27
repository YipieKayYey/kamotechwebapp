<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    /**
     * Get customer dashboard data
     */
    public function getDashboardData()
    {
        try {
            $user = Auth::user();

            \Log::info('Dashboard API called', [
                'user' => $user ? $user->id : 'null',
                'role' => $user ? $user->role : 'null',
            ]);

            if (! $user || $user->role !== 'customer') {
                \Log::warning('Unauthorized dashboard access', [
                    'user_id' => $user ? $user->id : null,
                    'user_role' => $user ? $user->role : null,
                ]);

                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // Get customer's bookings with relationships
            $bookings = Booking::where('customer_id', $user->id)
                ->with(['service', 'airconType', 'technician.user', 'review'])
                ->orderBy('created_at', 'desc')
                ->get();

            \Log::info('Bookings retrieved', ['count' => $bookings->count()]);

            // Calculate statistics
            $totalBookings = $bookings->count();
            $completedBookings = $bookings->where('status', 'completed')->count();
            $pendingBookings = $bookings->where('status', 'pending')->count();
            $totalSpent = $bookings->where('status', 'completed')->sum('total_amount');

            // Recent bookings (last 5)
            $recentBookings = $bookings->take(5)->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'service' => $booking->service->name,
                    'aircon_type' => $booking->airconType->name,
                    'scheduled_start' => optional($booking->scheduled_start_at)->format('M d, Y g:i A'),
                    'scheduled_end' => optional($booking->scheduled_end_at)->format('M d, Y g:i A'),
                    'status' => $booking->status,
                    'total_amount' => $booking->total_amount,
                    'technician_name' => $booking->technician?->user->name ?? 'Not assigned',
                    'technician_phone' => $booking->technician?->user->phone ?? null,
                    'has_review' => $booking->review !== null,
                    'can_review' => $booking->status === 'completed' && $booking->review === null,
                ];
            })->values()->toArray(); // Convert to array

            // Upcoming bookings (pending bookings only)
            $upcomingBookings = $bookings
                ->where('scheduled_start_at', '>=', Carbon::today())
                ->where('status', 'pending')
                ->sortByDesc('created_at')
                ->values() // Reset keys
                ->map(function ($booking) {
                    return [
                        'id' => $booking->id,
                        'booking_number' => $booking->booking_number,
                        'service' => $booking->service->name,
                        'scheduled_start' => optional($booking->scheduled_start_at)->format('M d, Y g:i A'),
                        'scheduled_end' => optional($booking->scheduled_end_at)->format('M d, Y g:i A'),
                        'status' => $booking->status,
                        'technician_name' => $booking->technician?->user->name ?? 'Not assigned',
                        'technician_phone' => $booking->technician?->user->phone ?? null,
                    ];
                })
                ->toArray(); // Convert to array

            $response = [
                'stats' => [
                    'total_bookings' => $totalBookings,
                    'completed_bookings' => $completedBookings,
                    'pending_bookings' => $pendingBookings,
                    'total_spent' => number_format($totalSpent, 2),
                ],
                'recent_bookings' => $recentBookings,
                'upcoming_bookings' => $upcomingBookings,
            ];

            \Log::info('Dashboard data prepared successfully');

            return response()->json($response);

        } catch (\Exception $e) {
            \Log::error('Dashboard API error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Internal server error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get customer's booking history with pagination
     */
    public function getBookingHistory(Request $request)
    {
        $user = Auth::user();

        if (! $user || $user->role !== 'customer') {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $perPage = $request->get('per_page', 10);
        $status = $request->get('status');

        $query = Booking::where('customer_id', $user->id)
            ->with(['service', 'airconType', 'technician.user', 'review']);

        if ($status) {
            $query->where('status', $status);
        }

        $bookings = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $bookings->getCollection()->transform(function ($booking) {
            return [
                'id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'service' => $booking->service->name,
                'aircon_type' => $booking->airconType->name,
                'number_of_units' => $booking->number_of_units,
                'ac_brand' => $booking->ac_brand,
                'scheduled_start' => optional($booking->scheduled_start_at)->format('M d, Y g:i A'),
                'scheduled_end' => optional($booking->scheduled_end_at)->format('M d, Y g:i A'),
                'status' => $booking->status,
                'total_amount' => $booking->total_amount,
                'payment_status' => $booking->payment_status,
                'technician_name' => $booking->technician?->user->name ?? 'Not assigned',
                'technician_phone' => $booking->technician?->user->phone ?? null,
                'service_location' => $booking->getServiceLocationAttribute(),
                'has_review' => $booking->review !== null,
                'can_review' => $booking->status === 'completed' && $booking->review === null,
                'review_rating' => $booking->review?->overall_rating,
                'created_at' => $booking->created_at->format('M d, Y g:i A'),
            ];
        });

        return response()->json($bookings);
    }

    /**
     * Get specific booking details
     */
    public function getBookingDetails($bookingId)
    {
        $user = Auth::user();

        if (! $user || $user->role !== 'customer') {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $booking = Booking::where('customer_id', $user->id)
            ->where('id', $bookingId)
            ->with(['service', 'airconType', 'technician.user', 'review.categoryScores.category'])
            ->first();

        if (! $booking) {
            return response()->json(['error' => 'Booking not found'], 404);
        }

        return response()->json([
            'id' => $booking->id,
            'booking_number' => $booking->booking_number,
            'service' => [
                'name' => $booking->service->name,
                'description' => $booking->service->description,
            ],
            'aircon_type' => [
                'name' => $booking->airconType->name,
                'description' => $booking->airconType->description,
            ],
            'number_of_units' => $booking->number_of_units,
            'ac_brand' => $booking->ac_brand,
            'scheduled_start_at' => optional($booking->scheduled_start_at)->format('Y-m-d H:i:s'),
            'scheduled_end_at' => optional($booking->scheduled_end_at)->format('Y-m-d H:i:s'),
            'estimated_duration' => $booking->estimated_duration_minutes,
            'estimated_days' => $booking->estimated_days,
            'status' => $booking->status,
            'total_amount' => $booking->total_amount,
            'payment_status' => $booking->payment_status,
            'technician' => $booking->technician ? [
                'name' => $booking->technician->user->name,
                'phone' => $booking->technician->user->phone,
                'specialization' => $booking->technician->specialization,
                'experience_years' => $booking->technician->experience_years,
            ] : null,
            'service_location' => $booking->getServiceLocationAttribute(),
            'special_instructions' => $booking->special_instructions,
            'nearest_landmark' => $booking->nearest_landmark,
            'has_review' => $booking->review !== null,
            'can_review' => $booking->status === 'completed' && $booking->review === null,
            'review' => $booking->review ? [
                'overall_rating' => $booking->review->overall_rating,
                'review_text' => $booking->review->review,
                'category_scores' => $booking->review->categoryScores->map(function ($score) {
                    return [
                        'category' => $score->category->name,
                        'score' => $score->score,
                    ];
                }),
                'created_at' => $booking->review->created_at->format('M d, Y'),
            ] : null,
            'created_at' => $booking->created_at->format('M d, Y g:i A'),
        ]);
    }

    /**
     * Update customer profile information
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        if (! $user || $user->role !== 'customer') {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => [
                'sometimes',
                'required',
                'string',
                'regex:/^(09\d{2}-\d{3}-\d{4}|09\d{9}|\+639\d{9}|639\d{9})$/',
            ],
            'house_no_street' => 'sometimes|nullable|string|max:255',
            'barangay' => 'sometimes|nullable|string|max:255',
            'city_municipality' => 'sometimes|nullable|string|max:255',
            'province' => 'sometimes|nullable|string|max:255',
            'nearest_landmark' => 'sometimes|nullable|string|max:255',
        ], [
            'phone.regex' => 'Please enter a valid Philippine mobile number (e.g., 0917-123-4567)',
        ]);

        try {
            $data = $request->only([
                'name',
                'phone',
                'house_no_street',
                'barangay',
                'city_municipality',
                'province',
                'nearest_landmark',
            ]);

            // Normalize phone number if provided
            if (isset($data['phone'])) {
                $phone = preg_replace('/[^0-9]/', '', $data['phone']);
                if (strlen($phone) === 10 && $phone[0] === '9') {
                    $phone = '0'.$phone;
                } elseif (strlen($phone) === 12 && substr($phone, 0, 2) === '63') {
                    $phone = '0'.substr($phone, 2);
                }
                $data['phone'] = $phone;
            }

            $user->update($data);

            \Log::info('Customer profile updated', [
                'user_id' => $user->id,
                'updated_fields' => array_keys($request->only([
                    'name', 'phone', 'house_no_street', 'barangay',
                    'city_municipality', 'province', 'nearest_landmark',
                ])),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone ?? '',
                    'house_no_street' => $user->house_no_street ?? '',
                    'barangay' => $user->barangay ?? '',
                    'city_municipality' => $user->city_municipality ?? '',
                    'province' => $user->province ?? '',
                    'nearest_landmark' => $user->nearest_landmark ?? '',
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Profile update failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to update profile. Please try again.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Request cancellation for a booking
     */
    public function requestCancellation(Request $request, $bookingNumber)
    {
        $user = Auth::user();

        if (! $user || $user->role !== 'customer') {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Find the booking by booking number and customer
        $booking = Booking::where('customer_id', $user->id)
            ->where('booking_number', $bookingNumber)
            ->first();

        if (! $booking) {
            return response()->json(['error' => 'Booking not found'], 404);
        }

        // Check if booking can be cancelled
        if (! in_array($booking->status, ['pending', 'confirmed'])) {
            return response()->json([
                'error' => 'This booking cannot be cancelled. Only pending or confirmed bookings can be cancelled.',
                'current_status' => $booking->status,
            ], 400);
        }

        // Check if booking is too close to the scheduled date
        $scheduledDate = $booking->scheduled_start_at;
        $now = Carbon::now();
        $hoursUntilService = $now->diffInHours($scheduledDate, false);

        if ($hoursUntilService < 24) {
            return response()->json([
                'error' => 'Cancellation requests must be made at least 24 hours before the scheduled service time.',
                'hours_remaining' => max(0, $hoursUntilService),
            ], 400);
        }

        try {
            // Update booking status to cancel_requested
            $booking->status = 'cancel_requested';
            $booking->save();

            // Create a notification for admin/staff
            Notification::create([
                'user_id' => null, // System notification
                'type' => 'cancellation_request',
                'title' => 'Cancellation Request',
                'message' => "Customer {$user->name} has requested cancellation for booking #{$booking->booking_number}",
                'data' => [
                    'booking_id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'customer_name' => $user->name,
                    'customer_id' => $user->id,
                    'scheduled_start_at' => optional($booking->scheduled_start_at)->format('Y-m-d H:i:s'),
                    'service_name' => $booking->service->name ?? 'Unknown Service',
                ],
            ]);

            \Log::info('Cancellation request submitted', [
                'booking_id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'customer_id' => $user->id,
                'customer_name' => $user->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cancellation request submitted successfully. Our team will contact you shortly to process your request.',
                'booking' => [
                    'id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'status' => $booking->status,
                    // Keep response key for compatibility, sourced from dynamic field
                    'scheduled_date' => optional($booking->scheduled_start_at)->format('M d, Y'),
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Cancellation request failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to submit cancellation request. Please try again.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
