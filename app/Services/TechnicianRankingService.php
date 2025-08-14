<?php

namespace App\Services;

use App\Models\Technician;
use App\Models\Service;
use App\Services\TechnicianAvailabilityService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * TechnicianRankingService for KAMOTECH
 * 
 * Implements the UPDATED GREEDY ALGORITHM for optimal technician selection.
 * This is the core intelligence that makes KAMOTECH smart.
 * 
 * UPDATED GREEDY ALGORITHM FORMULA (No GPS/Proximity):
 * SCORE = (ServiceRating × 70%) + (Availability × 30%)
 * 
 * The algorithm focuses on service quality and technician availability,
 * ensuring optimal customer experience without geographic constraints.
 */
class TechnicianRankingService
{
    private TechnicianAvailabilityService $availabilityService;

    // Updated Greedy Algorithm Weights (must sum to 1.0)
    private const WEIGHTS = [
        'service_rating' => 0.70,  // 70% - How good at this specific service
        'availability' => 0.30,    // 30% - How available (workload)
    ];

    public function __construct(TechnicianAvailabilityService $availabilityService)
    {
        $this->availabilityService = $availabilityService;
    }

    /**
     * Get ranked technicians for a specific service using UPDATED GREEDY ALGORITHM
     * 
     * This is the main method that implements our greedy optimization.
     * It returns technicians ranked by their greedy score for the specific service.
     * 
     * @param int $serviceId Service ID the customer wants
     * @param string $date Booking date (Y-m-d)
     * @param int|null $timeslotId Timeslot ID
     * @param float|null $customerLat Customer latitude (ignored - for backward compatibility)
     * @param float|null $customerLng Customer longitude (ignored - for backward compatibility)
     * @return Collection Ranked technicians with scores
     */
    public function getRankedTechniciansForService(
        int $serviceId, 
        string $date, 
        ?int $timeslotId = null, 
        ?float $customerLat = null, 
        ?float $customerLng = null
    ): Collection {
        
        // Step 1: Get available technicians for this date/timeslot
        $availableTechnicians = $this->availabilityService
            ->getAvailableTechniciansForDate($date, $timeslotId);

        if ($availableTechnicians->isEmpty()) {
            Log::info("No available technicians for service {$serviceId} on {$date}");
            return collect();
        }

        // Step 2: Calculate greedy scores for each technician (No GPS/Proximity)
        $rankedTechnicians = $availableTechnicians->map(function ($technician) use ($serviceId) {
            
            // Calculate individual component scores
            $serviceScore = $this->calculateServiceRatingScore($technician, $serviceId);
            $availabilityScore = $this->calculateAvailabilityScore($technician);
            
            // Apply updated greedy algorithm weights (No Proximity)
            $greedyScore = (
                ($serviceScore * self::WEIGHTS['service_rating']) +
                ($availabilityScore * self::WEIGHTS['availability'])
            );

            // Add debug info for transparency
            $technician->greedy_breakdown = [
                'service_rating' => [
                    'raw_score' => $serviceScore, 
                    'weighted' => $serviceScore * self::WEIGHTS['service_rating'],
                    'weight' => self::WEIGHTS['service_rating']
                ],
                'availability' => [
                    'raw_score' => $availabilityScore,
                    'weighted' => $availabilityScore * self::WEIGHTS['availability'],
                    'weight' => self::WEIGHTS['availability']
                ],
                'total_score' => $greedyScore
            ];

            $technician->greedy_score = $greedyScore;
            $technician->service_specific_rating = $technician->getServiceSpecificRating($serviceId);
            $technician->service_review_count = $technician->getServiceSpecificReviewCount($serviceId);
            $technician->service_completed_jobs = $technician->getServiceSpecificCompletedJobs($serviceId);

            return $technician;
        });

        // Step 3: Sort by greedy score (highest first) - This is the GREEDY selection
        return $rankedTechnicians->sortByDesc('greedy_score')->values();
    }

    /**
     * Get the BEST technician using updated greedy algorithm (auto-assignment)
     * 
     * @param int $serviceId Service ID
     * @param string $date Booking date
     * @param int|null $timeslotId Timeslot ID
     * @param float|null $customerLat Customer latitude (ignored - for backward compatibility)
     * @param float|null $customerLng Customer longitude (ignored - for backward compatibility)
     * @return Technician|null Best technician or null if none available
     */
    public function getBestTechnicianForService(
        int $serviceId,
        string $date, 
        ?int $timeslotId = null,
        ?float $customerLat = null,
        ?float $customerLng = null
    ): ?Technician {
        
        $rankedTechnicians = $this->getRankedTechniciansForService(
            $serviceId, $date, $timeslotId, $customerLat, $customerLng
        );

        $bestTechnician = $rankedTechnicians->first();

        if ($bestTechnician) {
            Log::info("Greedy algorithm selected technician", [
                'technician_id' => $bestTechnician->id,
                'technician_name' => $bestTechnician->user->name,
                'service_id' => $serviceId,
                'greedy_score' => $bestTechnician->greedy_score,
                'breakdown' => $bestTechnician->greedy_breakdown
            ]);
        }

        return $bestTechnician;
    }

    /**
     * Calculate Service Rating Score (70% weight)
     * 
     * This score represents how good the technician is at the specific service.
     * Uses service-specific overall_rating (calculated from category scores).
     * 
     * @param Technician $technician
     * @param int $serviceId
     * @return float Score from 0.0 to 1.0
     */
    private function calculateServiceRatingScore(Technician $technician, int $serviceId): float
    {
        $serviceRating = $technician->getServiceSpecificRating($serviceId);
        $reviewCount = $technician->getServiceSpecificReviewCount($serviceId);
        
        // Normalize rating from 1-5 scale to 0-1 scale
        $normalizedRating = ($serviceRating - 1) / 4;
        
        // Apply confidence penalty for technicians with few reviews for this service
        // Technicians with more reviews get slight boost in confidence
        if ($reviewCount < 3) {
            $confidencePenalty = 0.1; // 10% penalty for new technicians in this service
            $normalizedRating = max(0, $normalizedRating - $confidencePenalty);
        } elseif ($reviewCount >= 10) {
            $confidenceBoost = 0.05; // 5% boost for very experienced technicians
            $normalizedRating = min(1, $normalizedRating + $confidenceBoost);
        }
        
        return $normalizedRating;
    }



    /**
     * Calculate Availability Score (30% weight)
     * 
     * Technicians with fewer current jobs get higher scores for better work-life balance.
     * 
     * @param Technician $technician
     * @return float Score from 0.0 to 1.0
     */
    private function calculateAvailabilityScore(Technician $technician): float
    {
        $currentJobs = $technician->current_jobs;
        $maxJobs = $technician->max_daily_jobs;
        
        // Prevent division by zero
        if ($maxJobs <= 0) {
            return 0.0;
        }
        
        // Higher score for technicians with more availability
        return max(0, ($maxJobs - $currentJobs) / $maxJobs);
    }

    /**
     * Get ranking explanation for transparency
     * 
     * @param Collection $rankedTechnicians
     * @return array Human-readable explanation
     */
    public function getRankingExplanation(Collection $rankedTechnicians): array
    {
        return $rankedTechnicians->map(function ($technician) {
            $breakdown = $technician->greedy_breakdown;
            
            return [
                'technician_id' => $technician->id,
                'name' => $technician->user->name,
                'overall_score' => round($technician->greedy_score, 3),
                'explanation' => [
                    'service_expertise' => "Rating: {$technician->service_specific_rating}/5 (from {$technician->service_review_count} reviews) = " . round($breakdown['service_rating']['weighted'], 3),
                    'availability' => "Workload score = " . round($breakdown['availability']['weighted'], 3),
                ],
                'why_chosen' => $this->generateRankingReason($technician, $breakdown)
            ];
        })->toArray();
    }

    /**
     * Generate human-readable reason for ranking
     * 
     * @param Technician $technician
     * @param array $breakdown
     * @return string
     */
    private function generateRankingReason(Technician $technician, array $breakdown): string
    {
        $reasons = [];
        
        // Find the strongest factor
        $factors = [
            'service_rating' => ['score' => $breakdown['service_rating']['weighted'], 'label' => 'service expertise'],
            'availability' => ['score' => $breakdown['availability']['weighted'], 'label' => 'availability']
        ];
        
        $topFactor = collect($factors)->sortByDesc('score')->first();
        
        $reasons[] = "Strong {$topFactor['label']}";
        
        if ($technician->service_specific_rating >= 4.5) {
            $reasons[] = "excellent service rating ({$technician->service_specific_rating}/5)";
        }
        
        if ($breakdown['availability']['raw_score'] > 0.8) {
            $reasons[] = "high availability";
        }
        
        return "Ranked high due to: " . implode(', ', $reasons);
    }

    /**
     * Get service-specific leaderboard for analytics
     * 
     * @param int $serviceId
     * @return Collection Top technicians for this service
     */
    public function getServiceLeaderboard(int $serviceId): Collection
    {
        return Technician::available()
            ->with(['user', 'reviews'])
            ->get()
            ->map(function ($technician) use ($serviceId) {
                $technician->service_rating = $technician->getServiceSpecificRating($serviceId);
                $technician->service_review_count = $technician->getServiceSpecificReviewCount($serviceId);
                return $technician;
            })
            ->sortByDesc('service_rating')
            ->take(10);
    }
}