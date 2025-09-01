<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\CategoryScore;
use App\Models\RatingReview;
use App\Models\ReviewCategory;
use App\Models\Technician;
use Illuminate\Database\Seeder;

class RatingReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all completed bookings
        $completedBookings = Booking::where('status', 'completed')
            ->with(['customer', 'technician.user', 'service'])
            ->get();

        // Get review categories
        $categories = ReviewCategory::getActiveOrdered();

        if ($categories->isEmpty()) {
            echo "❌ No review categories found. Please run ReviewCategorySeeder first.\n";

            return;
        }

        // Define technician category expertise profiles
        $technicianProfiles = $this->getTechnicianProfiles();

        $reviewCount = 0;
        foreach ($completedBookings as $booking) {
            // Skip bookings without assigned technicians
            if (! $booking->technician_id) {
                echo "  ⚠️  Skipping booking {$booking->booking_number} - no technician assigned\n";

                continue;
            }

            // Not all customers leave reviews (about 75% review rate)
            if (rand(1, 100) <= 75) {
                $technicianId = $booking->technician_id;
                $serviceName = $booking->service->name;

                // Create the main review record
                $review = RatingReview::create([
                    'booking_id' => $booking->id,
                    'customer_id' => $booking->customer_id,
                    'technician_id' => $booking->technician_id,
                    'service_id' => $booking->service_id,
                    'overall_rating' => null, // Will be calculated from category scores
                    'review' => '',  // Will be updated after scores are created
                    'is_approved' => true,
                    'created_at' => $booking->updated_at->addHours(rand(2, 48)),
                    'updated_at' => $booking->updated_at->addHours(rand(2, 48)),
                ]);

                // Create category scores based on technician expertise
                $categoryScores = [];
                foreach ($categories as $category) {
                    $score = $this->generateCategoryScore($technicianId, $serviceName, $category->name);
                    $categoryScores[] = $score;

                    CategoryScore::create([
                        'review_id' => $review->id,
                        'category_id' => $category->id,
                        'score' => $score,
                    ]);
                }

                // Calculate overall rating (this will be done automatically by model events)
                $review->refresh();

                // Generate review text based on overall rating
                $review->update([
                    'review' => $this->generateReviewText($review->overall_rating, $booking),
                ]);

                $reviewCount++;
            }
        }

        echo "✅ Created {$reviewCount} reviews with category-based ratings\n";
    }

    /**
     * Get technician expertise profiles for category-based ratings
     */
    private function getTechnicianProfiles(): array
    {
        return [
            1 => [ // Pedro Mendoza - Cleaning/Maintenance Specialist
                'strengths' => ['Cleanliness', 'Work Quality'],
                'weaknesses' => ['Tools'],
                'base_ratings' => [
                    'Work Quality' => 4.3,
                    'Punctuality' => 4.1,
                    'Cleanliness' => 4.8,
                    'Attitude' => 4.2,
                    'Tools' => 3.9,
                ],
            ],
            2 => [ // Maria Garcia - Installation Expert
                'strengths' => ['Work Quality', 'Tools'],
                'weaknesses' => ['Punctuality'],
                'base_ratings' => [
                    'Work Quality' => 4.7,
                    'Punctuality' => 4.0,
                    'Cleanliness' => 4.3,
                    'Attitude' => 4.4,
                    'Tools' => 4.8,
                ],
            ],
            3 => [ // Jose Cruz - Repair Specialist
                'strengths' => ['Work Quality', 'Tools'],
                'weaknesses' => ['Cleanliness', 'Attitude'],
                'base_ratings' => [
                    'Work Quality' => 4.6,
                    'Punctuality' => 4.2,
                    'Cleanliness' => 3.8,
                    'Attitude' => 3.9,
                    'Tools' => 4.7,
                ],
            ],
            4 => [ // Ana Reyes - All-rounder Expert
                'strengths' => ['Work Quality', 'Attitude', 'Punctuality'],
                'weaknesses' => [],
                'base_ratings' => [
                    'Work Quality' => 4.8,
                    'Punctuality' => 4.7,
                    'Cleanliness' => 4.6,
                    'Attitude' => 4.9,
                    'Tools' => 4.5,
                ],
            ],
            5 => [ // Carlos - Chemical/Freon Specialist
                'strengths' => ['Tools', 'Work Quality'],
                'weaknesses' => ['Punctuality'],
                'base_ratings' => [
                    'Work Quality' => 4.5,
                    'Punctuality' => 3.9,
                    'Cleanliness' => 4.2,
                    'Attitude' => 4.3,
                    'Tools' => 4.6,
                ],
            ],
        ];
    }

    /**
     * Generate category score based on technician expertise and service type
     */
    private function generateCategoryScore($technicianId, $serviceName, $categoryName): int
    {
        $profiles = $this->getTechnicianProfiles();
        $profile = $profiles[$technicianId] ?? null;

        if (! $profile) {
            return rand(3, 5); // Default random score
        }

        $baseRating = $profile['base_ratings'][$categoryName] ?? 4.0;

        // Adjust based on service type and technician specialization
        $serviceAdjustment = $this->getServiceAdjustment($technicianId, $serviceName, $categoryName);
        $adjustedRating = $baseRating + $serviceAdjustment;

        // Convert to 1-5 integer score with some randomness
        $targetScore = max(1, min(5, round($adjustedRating)));

        // Add some variation around the target
        $variation = rand(-1, 1) * 0.3;
        $finalScore = max(1, min(5, round($adjustedRating + $variation)));

        return (int) $finalScore;
    }

    /**
     * Get service-specific adjustment for category scores
     */
    private function getServiceAdjustment($technicianId, $serviceName, $categoryName): float
    {
        $adjustments = [
            // Pedro specializes in cleaning - gets bonus for cleaning services
            1 => [
                'AC Cleaning' => ['Cleanliness' => 0.6, 'Work Quality' => 0.4],
                'AC Maintenance' => ['Cleanliness' => 0.3, 'Work Quality' => 0.2],
                'AC Installation' => ['Work Quality' => -0.5, 'Tools' => -0.3],
            ],
            // Maria specializes in installation
            2 => [
                'AC Installation' => ['Work Quality' => 0.5, 'Tools' => 0.4],
                'AC Relocation' => ['Work Quality' => 0.3, 'Tools' => 0.3],
                'AC Cleaning' => ['Work Quality' => -0.2],
            ],
            // Jose specializes in repair
            3 => [
                'AC Repair' => ['Work Quality' => 0.4, 'Tools' => 0.3],
                'Repiping Service' => ['Work Quality' => 0.5, 'Tools' => 0.4],
                'Freon Charging' => ['Work Quality' => 0.3, 'Tools' => 0.2],
                'AC Troubleshooting' => ['Attitude' => -0.3], // Not great at diagnosis
            ],
            // Ana is good at everything
            4 => [
                'AC Troubleshooting' => ['Work Quality' => 0.5, 'Attitude' => 0.3],
                // Small bonus across all services
            ],
            // Carlos specializes in chemical work
            5 => [
                'Freon Charging' => ['Work Quality' => 0.4, 'Tools' => 0.5],
                'Repiping Service' => ['Work Quality' => 0.3, 'Tools' => 0.3],
            ],
        ];

        return $adjustments[$technicianId][$serviceName][$categoryName] ?? 0;
    }

    private function generateReviewText($overallRating, $booking): string
    {
        $technicianName = $booking->technician->user->name ?? 'The technician';
        $serviceName = $booking->service->name ?? 'the service';
        $rating = round($overallRating);

        $reviews = [
            5 => [
                "Excellent service! {$technicianName} was very professional and thorough. My AC is working perfectly now. Highly recommended!",
                "Outstanding work by {$technicianName}. Arrived on time, explained everything clearly, and did a fantastic job. Will definitely book again.",
                "Amazing service! {$technicianName} was courteous, skilled, and efficient. The AC is running like new. Thank you Kamotech!",
            ],
            4 => [
                "Good service overall. {$technicianName} was professional and did the job well. AC is working fine now.",
                "Solid work by {$technicianName}. Arrived on time and completed the {$serviceName} efficiently.",
                "Very satisfied with the service. {$technicianName} was knowledgeable and courteous.",
            ],
            3 => [
                "Service was okay. {$technicianName} did the job but could have been more thorough.",
                'Average service. The job was done but nothing exceptional. AC is working though.',
                "Decent work by {$technicianName}. Service was completed but took longer than expected.",
            ],
            2 => [
                "Service was below expectations. {$technicianName} did the work but seemed rushed.",
                'Not very satisfied. The job was done but not very thoroughly. Had some issues after.',
            ],
            1 => [
                "Very disappointed with the service. Had to call them back to fix issues that weren't resolved.",
                'Poor service. The technician seemed unprepared and left the job incomplete.',
            ],
        ];

        $ratingReviews = $reviews[$rating] ?? $reviews[3];

        return $ratingReviews[array_rand($ratingReviews)];
    }
}
