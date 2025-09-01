<?php

namespace App\Services;

use App\Models\Technician;
use App\Models\Booking;
use App\Models\Timeslot;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * TechnicianAvailabilityService for KAMOTECH
 * 
 * Handles real-time technician availability checking for the booking system.
 * This is crucial for showing "X technicians available" per timeslot.
 * 
 * Key Features:
 * - Real-time availability checking per date/timeslot
 * - Multi-day booking conflict detection  
 * - Availability matrix generation for calendar views
 * - Smart scheduling with workload limits
 */
class TechnicianAvailabilityService
{
    /**
     * Get the number of available technicians for a specific date and timeslot
     * 
     * @param string $date Date in Y-m-d format
     * @param int|null $timeslotId Specific timeslot ID (null = any timeslot)
     * @return int Number of available technicians
     */
    public function getAvailableTechniciansCount(string $date, ?int $timeslotId = null): int
    {
        return $this->getAvailableTechniciansForDate($date, $timeslotId)->count();
    }

    /**
     * Get collection of available technicians for a specific date and timeslot
     * 
     * @param string $date Date in Y-m-d format  
     * @param int|null $timeslotId Specific timeslot ID
     * @return Collection Available technicians
     */
    public function getAvailableTechniciansForDate(string $date, ?int $timeslotId = null): Collection
    {
        $dateObj = Carbon::parse($date);
        $dayOfWeek = $dateObj->dayOfWeek; // 0 = Sunday, 6 = Saturday

        // Get all active technicians
        $allTechnicians = Technician::available()
            ->with(['user', 'availability'])
            ->get();

        // Filter by day-of-week availability and timeslot overlap
        $availableBySchedule = $allTechnicians->filter(function ($technician) use ($dayOfWeek, $timeslotId) {
            $dayAvailability = $technician->availability
                ->where('day_of_week', $dayOfWeek)
                ->where('is_available', true)
                ->first();
                
            if (!$dayAvailability) {
                return false; // Not available on this day
            }
            
            // If no specific timeslot requested, just check day availability
            if (!$timeslotId) {
                return true;
            }
            
            // Check if technician's work hours overlap with requested timeslot
            $timeslot = Timeslot::find($timeslotId);
            if (!$timeslot) {
                return false;
            }
            
            return $this->timesOverlap(
                $dayAvailability->start_time, 
                $dayAvailability->end_time,
                $timeslot->start_time,
                $timeslot->end_time
            );
        });

        // Get technicians blocked by existing bookings (including multi-day)
        $blockedTechnicians = $this->getBlockedTechniciansForDate($date, $timeslotId);

        // Get technicians who are at their daily job limit
        $overloadedTechnicians = $this->getOverloadedTechniciansForDate($date);

        // Remove blocked and overloaded technicians
        return $availableBySchedule->whereNotIn('id', $blockedTechnicians->merge($overloadedTechnicians));
    }

    /**
     * Get technicians blocked by existing bookings on a specific date
     * 
     * @param string $date Date to check
     * @param int|null $timeslotId Specific timeslot (null = any timeslot)
     * @return Collection Blocked technician IDs
     */
    private function getBlockedTechniciansForDate(string $date, ?int $timeslotId = null): Collection
    {
        $query = Booking::where('status', '!=', 'cancelled')
            ->where('status', '!=', 'completed') // Don't block if job is completed
            ->where(function ($query) use ($date) {
                // Single-day bookings on this date
                $query->where(function ($q) use ($date) {
                    $q->where('scheduled_date', $date)
                      ->whereNull('scheduled_end_date');
                })
                // Multi-day bookings that span this date (improved logic)
                ->orWhere(function ($q) use ($date) {
                    $q->where('scheduled_date', '<=', $date)
                      ->where('scheduled_end_date', '>=', $date)
                      ->whereNotNull('scheduled_end_date');
                });
            });

        // If specific timeslot requested, filter by timeslot
        if ($timeslotId) {
            $query->where('timeslot_id', $timeslotId);
        }

        return $query->pluck('technician_id')->unique();
    }

    /**
     * Get technicians who have reached their daily job limit
     * 
     * @param string $date Date to check
     * @return Collection Overloaded technician IDs
     */
    private function getOverloadedTechniciansForDate(string $date): Collection
    {
        // Count jobs scheduled for this date
        $jobCounts = Booking::where('scheduled_date', $date)
            ->where('status', '!=', 'cancelled')
            ->groupBy('technician_id')
            ->selectRaw('technician_id, COUNT(*) as job_count')
            ->pluck('job_count', 'technician_id');

        // Get technicians at or over their limit
        return Technician::whereIn('id', $jobCounts->keys())
            ->get()
            ->filter(function ($technician) use ($jobCounts) {
                return $jobCounts[$technician->id] >= $technician->max_daily_jobs;
            })
            ->pluck('id');
    }

    /**
     * Generate availability matrix for multiple days
     * Perfect for calendar/schedule views
     * 
     * @param string $startDate Start date (Y-m-d)
     * @param int $days Number of days to generate (default 7 for week view)
     * @return array Matrix of availability counts
     */
    public function getAvailabilityMatrix(string $startDate, int $days = 7): array
    {
        $matrix = [];
        $timeslots = Timeslot::orderBy('start_time')->get();
        
        for ($i = 0; $i < $days; $i++) {
            $currentDate = Carbon::parse($startDate)->addDays($i)->format('Y-m-d');
            $matrix[$currentDate] = [];
            
            foreach ($timeslots as $timeslot) {
                $availableCount = $this->getAvailableTechniciansCount($currentDate, $timeslot->id);
                $matrix[$currentDate][$timeslot->id] = [
                    'timeslot' => $timeslot->display_time,
                    'time_range' => $timeslot->start_time . ' - ' . $timeslot->end_time,
                    'available_count' => $availableCount,
                    'is_available' => $availableCount > 0,
                ];
            }
        }
        
        return $matrix;
    }

    /**
     * Check if a technician is available for a multi-day booking
     * 
     * @param int $technicianId Technician ID
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @param int|null $timeslotId Timeslot ID
     * @return bool True if available for entire period
     */
    public function isTechnicianAvailableForMultiDay(int $technicianId, string $startDate, string $endDate, ?int $timeslotId = null): bool
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        
        // Check each day in the range
        for ($date = $start; $date->lte($end); $date->addDay()) {
            $availableTechnicians = $this->getAvailableTechniciansForDate($date->format('Y-m-d'), $timeslotId);
            
            if (!$availableTechnicians->contains('id', $technicianId)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get next available date for a specific number of technicians
     * Useful for "Next available: January 15" features
     * 
     * @param int $requiredTechnicians Number of technicians needed
     * @param int|null $timeslotId Specific timeslot
     * @param int $maxDaysToCheck Maximum days to search (default 30)
     * @return string|null Next available date or null if none found
     */
    public function getNextAvailableDate(int $requiredTechnicians = 1, ?int $timeslotId = null, int $maxDaysToCheck = 30): ?string
    {
        $today = Carbon::today();
        
        for ($i = 0; $i < $maxDaysToCheck; $i++) {
            $checkDate = $today->copy()->addDays($i)->format('Y-m-d');
            $availableCount = $this->getAvailableTechniciansCount($checkDate, $timeslotId);
            
            if ($availableCount >= $requiredTechnicians) {
                return $checkDate;
            }
        }
        
        return null;
    }

    /**
     * Get availability summary for admin dashboard
     * 
     * @return array Summary statistics
     */
    public function getAvailabilitySummary(): array
    {
        $today = Carbon::today()->format('Y-m-d');
        $totalTechnicians = Technician::available()->count();
        
        return [
            'total_technicians' => $totalTechnicians,
            'available_today' => $this->getAvailableTechniciansCount($today),
            'next_available_date' => $this->getNextAvailableDate(),
            'peak_availability_today' => $this->getPeakAvailabilityForDate($today),
        ];
    }

    /**
     * Get the timeslot with highest availability for a date
     * 
     * @param string $date Date to check
     * @return array Peak availability info
     */
    private function getPeakAvailabilityForDate(string $date): array
    {
        $timeslots = Timeslot::orderBy('start_time')->get();
        $peakSlot = null;
        $maxAvailable = 0;
        
        foreach ($timeslots as $timeslot) {
            $available = $this->getAvailableTechniciansCount($date, $timeslot->id);
            if ($available > $maxAvailable) {
                $maxAvailable = $available;
                $peakSlot = $timeslot;
            }
        }
        
        return [
            'timeslot' => $peakSlot?->display_time,
            'time_range' => $peakSlot ? $peakSlot->start_time . ' - ' . $peakSlot->end_time : null,
            'available_count' => $maxAvailable,
        ];
    }

    /**
     * Check if two time ranges overlap
     * 
     * @param string $start1 First range start time (H:i:s)
     * @param string $end1 First range end time (H:i:s)  
     * @param string $start2 Second range start time (H:i:s)
     * @param string $end2 Second range end time (H:i:s)
     * @return bool True if ranges overlap
     */
    private function timesOverlap($start1, $end1, $start2, $end2): bool
    {
        // Convert to Carbon instances (handles both time and datetime formats)
        $start1 = Carbon::parse($start1);
        $end1 = Carbon::parse($end1);
        $start2 = Carbon::parse($start2);
        $end2 = Carbon::parse($end2);
        
        // Compare just the time portion (ignore date)
        $start1Time = $start1->format('H:i:s');
        $end1Time = $end1->format('H:i:s');
        $start2Time = $start2->format('H:i:s');
        $end2Time = $end2->format('H:i:s');
        
        // Two ranges overlap if: start1 < end2 AND start2 < end1
        return $start1Time < $end2Time && $start2Time < $end1Time;
    }

    /**
     * Check if technician is available for consecutive days
     * 
     * @param int $technicianId Technician ID
     * @param string $startDate Start date (Y-m-d)  
     * @param int $days Number of consecutive days needed
     * @param int|null $timeslotId Timeslot ID
     * @return bool True if available for all consecutive days
     */
    public function checkConsecutiveDayAvailability(int $technicianId, string $startDate, int $days, ?int $timeslotId = null): bool
    {
        for ($i = 0; $i < $days; $i++) {
            $checkDate = Carbon::parse($startDate)->addDays($i)->format('Y-m-d');
            $available = $this->getAvailableTechniciansForDate($checkDate, $timeslotId);
            
            if (!$available->contains('id', $technicianId)) {
                return false;
            }
        }
        
        return true;
    }
}