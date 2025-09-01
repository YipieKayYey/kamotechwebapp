<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\CategoryScore;
use App\Models\RatingReview;
use App\Models\ReviewCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RatingReviewController extends Controller
{
    /**
     * Get active review categories for rating form
     */
    public function getReviewCategories()
    {
        $categories = ReviewCategory::active()
            ->ordered()
            ->get(['id', 'name', 'description']);

        return response()->json($categories);
    }

    /**
     * Get booking details for review form
     */
    public function getBookingForReview($bookingId)
    {
        $user = Auth::user();
        
        if (!$user || $user->role !== 'customer') {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $booking = Booking::where('customer_id', $user->id)
            ->where('id', $bookingId)
            ->where('status', 'completed')
            ->with(['service', 'airconType', 'technician.user', 'timeslot', 'review'])
            ->first();

        if (!$booking) {
            return response()->json(['error' => 'Booking not found or not eligible for review'], 404);
        }

        if ($booking->review) {
            return response()->json(['error' => 'Review already submitted for this booking'], 400);
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
            'scheduled_date' => $booking->scheduled_date->format('M d, Y'),
            'timeslot' => $booking->timeslot->time_range,
            'total_amount' => $booking->total_amount,
            'technician' => [
                'name' => $booking->technician->user->name,
                'specialization' => $booking->technician->specialization,
                'experience_years' => $booking->technician->experience_years,
            ],
            'service_location' => $booking->getServiceLocationAttribute(),
            'completed_date' => $booking->updated_at->format('M d, Y'),
        ]);
    }

    /**
     * Submit review with category ratings
     */
    public function submitReview(Request $request, $bookingId)
    {
        $user = Auth::user();
        
        if (!$user || $user->role !== 'customer') {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Validate request
        $request->validate([
            'category_scores' => 'required|array|min:1',
            'category_scores.*.category_id' => 'required|exists:review_categories,id',
            'category_scores.*.score' => 'required|integer|min:1|max:5',
            'review_text' => 'nullable|string|max:1000',
        ]);

        // Get booking and verify eligibility
        $booking = Booking::where('customer_id', $user->id)
            ->where('id', $bookingId)
            ->where('status', 'completed')
            ->with('review')
            ->first();

        if (!$booking) {
            return response()->json(['error' => 'Booking not found or not eligible for review'], 404);
        }

        if ($booking->review) {
            return response()->json(['error' => 'Review already submitted for this booking'], 400);
        }

        DB::beginTransaction();
        try {
            // Create the review record
            $review = RatingReview::create([
                'booking_id' => $booking->id,
                'customer_id' => $user->id,
                'technician_id' => $booking->technician_id,
                'service_id' => $booking->service_id,
                'review' => $request->review_text,
                'is_approved' => true, // Auto-approve customer reviews
            ]);

            // Create category scores
            $categoryScores = [];
            foreach ($request->category_scores as $scoreData) {
                $categoryScore = CategoryScore::create([
                    'review_id' => $review->id,
                    'category_id' => $scoreData['category_id'],
                    'score' => $scoreData['score'],
                ]);
                $categoryScores[] = $categoryScore;
            }

            // Calculate overall rating (this will be done automatically by model events)
            $review->refresh();

            DB::commit();

            return response()->json([
                'message' => 'Review submitted successfully',
                'review' => [
                    'id' => $review->id,
                    'overall_rating' => $review->overall_rating,
                    'review_text' => $review->review,
                    'category_scores' => $review->categoryScores->map(function ($score) {
                        return [
                            'category' => $score->category->name,
                            'score' => $score->score,
                        ];
                    }),
                    'created_at' => $review->created_at->format('M d, Y'),
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to submit review'], 500);
        }
    }

    /**
     * Get customer's reviews
     */
    public function getCustomerReviews(Request $request)
    {
        $user = Auth::user();
        
        if (!$user || $user->role !== 'customer') {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $perPage = $request->get('per_page', 10);

        $reviews = RatingReview::where('customer_id', $user->id)
            ->with(['booking.service', 'booking.airconType', 'technician.user', 'categoryScores.category'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $reviews->getCollection()->transform(function ($review) {
            return [
                'id' => $review->id,
                'booking_number' => $review->booking->booking_number,
                'service' => $review->booking->service->name,
                'aircon_type' => $review->booking->airconType->name,
                'technician_name' => $review->technician->user->name,
                'overall_rating' => $review->overall_rating,
                'review_text' => $review->review,
                'category_scores' => $review->categoryScores->map(function ($score) {
                    return [
                        'category' => $score->category->name,
                        'score' => $score->score,
                    ];
                }),
                'is_approved' => $review->is_approved,
                'created_at' => $review->created_at->format('M d, Y'),
            ];
        });

        return response()->json($reviews);
    }

    /**
     * Get review details
     */
    public function getReviewDetails($reviewId)
    {
        $user = Auth::user();
        
        if (!$user || $user->role !== 'customer') {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $review = RatingReview::where('customer_id', $user->id)
            ->where('id', $reviewId)
            ->with(['booking.service', 'booking.airconType', 'technician.user', 'categoryScores.category'])
            ->first();

        if (!$review) {
            return response()->json(['error' => 'Review not found'], 404);
        }

        return response()->json([
            'id' => $review->id,
            'booking' => [
                'id' => $review->booking->id,
                'booking_number' => $review->booking->booking_number,
                'service' => $review->booking->service->name,
                'aircon_type' => $review->booking->airconType->name,
                'scheduled_date' => $review->booking->scheduled_date->format('M d, Y'),
                'total_amount' => $review->booking->total_amount,
            ],
            'technician' => [
                'name' => $review->technician->user->name,
                'specialization' => $review->technician->specialization,
            ],
            'overall_rating' => $review->overall_rating,
            'review_text' => $review->review,
            'category_scores' => $review->categoryScores->map(function ($score) {
                return [
                    'category' => $score->category->name,
                    'description' => $score->category->description,
                    'score' => $score->score,
                ];
            }),
            'is_approved' => $review->is_approved,
            'created_at' => $review->created_at->format('M d, Y g:i A'),
        ]);
    }
}