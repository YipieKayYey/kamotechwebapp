<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Technician;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * TechnicianAvailabilityService for KAMOTECH
 *
 * Simplified availability service using standard schedule:
 * - All technicians work 7 days a week (Sunday-Saturday) 
 * - Standard hours: 8:00 AM - 5:00 PM
 * - Availability controlled by simple is_available toggle
 * - Perfect for commission-based business model
 */
class TechnicianAvailabilityService
{
    // Standard work schedule - all technicians work the same hours
    const WORK_START = '08:00:00';
    const WORK_END = '17:00:00';
    
    /**
     * Backward-compatible wrapper: count available technicians for a given date.
     * Uses full workday window (08:00–17:00).
     */
    public function getAvailableTechniciansCount(string $date, ?int $unused = null): int
    {
        $startAt = Carbon::parse($date.' '.self::WORK_START)->format('Y-m-d H:i:s');
        $endAt = Carbon::parse($date.' '.self::WORK_END)->format('Y-m-d H:i:s');

        return $this->getAvailableTechniciansCountForWindow($startAt, $endAt);
    }

    /**
     * Get available technicians count for a specific time window
     */
    public function getAvailableTechniciansCountForWindow(string $startAt, string $endAt): int
    {
        return $this->getAvailableTechniciansForWindow($startAt, $endAt)->count();
    }

    /**
     * Get available technicians for a specific time window
     * 
     * Simplified logic:
     * 1. Check if technician is_available = true
     * 2. Check if requested time overlaps with standard work hours (8am-5pm)
     * 3. Check if technician has no conflicting bookings
     */
    public function getAvailableTechniciansForWindow(string $startAt, string $endAt): Collection
    {
        $start = Carbon::parse($startAt);
        $end = Carbon::parse($endAt);

        // Get all active technicians (simplified - no need to load availability relationship)
        $allTechnicians = Technician::available()
            ->with(['user'])
            ->get();

        // Check if requested window overlaps with standard work hours (8am-5pm)
        $workHoursOverlap = $this->timesOverlap(
            self::WORK_START,
            self::WORK_END,
            $start->format('H:i:s'),
            $end->format('H:i:s')
        );

        if (!$workHoursOverlap) {
            return collect(); // Requested time is outside work hours
        }

        // Filter technicians who don't have conflicting bookings
        $availableTechnicians = $allTechnicians->filter(function ($technician) use ($startAt, $endAt) {
            // Check for overlapping bookings
            $overlapExists = Booking::where('technician_id', $technician->id)
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->where(function ($q) use ($startAt, $endAt) {
                    $q->where('scheduled_start_at', '<', $endAt)
                        ->where('scheduled_end_at', '>', $startAt);
                })
                ->exists();

            return !$overlapExists;
        });

        return $availableTechnicians->values();
    }

    /**
     * Generate simple availability counts per hour window for N days (08-17)
     */
    public function getAvailabilityMatrix(string $startDate, int $days = 7): array
    {
        $matrix = [];
        for ($i = 0; $i < $days; $i++) {
            $date = Carbon::parse($startDate)->addDays($i)->toDateString();
            $matrix[$date] = [];
            foreach ([8, 9, 10, 11, 13, 14, 15, 16] as $hour) {
                $startAt = Carbon::parse($date)->setTime($hour, 0)->format('Y-m-d H:i:s');
                $endAt = Carbon::parse($date)->setTime($hour + 1, 0)->format('Y-m-d H:i:s');
                $count = $this->getAvailableTechniciansCountForWindow($startAt, $endAt);
                $matrix[$date][sprintf('%02d:00-%02d:00', $hour, $hour + 1)] = [
                    'start' => $startAt,
                    'end' => $endAt,
                    'available_count' => $count,
                    'is_available' => $count > 0,
                ];
            }
        }

        return $matrix;
    }

    /**
     * Check if a technician is available for a multi-day booking
     * Simplified: just check if technician is_available and no conflicting bookings
     */
    public function isTechnicianAvailableForMultiDay(int $technicianId, string $startDate, string $endDate, ?int $unused = null): bool
    {
        $technician = Technician::find($technicianId);
        if (!$technician || !$technician->is_available) {
            return false;
        }

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        // Check each day in the range
        for ($date = $start; $date->lte($end); $date->addDay()) {
            $dayStart = $date->copy()->setTime(8, 0);
            $dayEnd = $date->copy()->setTime(17, 0);
            
            $overlapExists = Booking::where('technician_id', $technicianId)
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->where(function ($q) use ($dayStart, $dayEnd) {
                    $q->where('scheduled_start_at', '<', $dayEnd->format('Y-m-d H:i:s'))
                        ->where('scheduled_end_at', '>', $dayStart->format('Y-m-d H:i:s'));
                })
                ->exists();
                
            if ($overlapExists) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get next available date for a specific number of technicians
     */
    public function getNextAvailableDate(int $requiredTechnicians = 1, ?int $unused = null, int $maxDaysToCheck = 30): ?string
    {
        $today = Carbon::today();

        for ($i = 0; $i < $maxDaysToCheck; $i++) {
            $checkDate = $today->copy()->addDays($i)->format('Y-m-d');
            $startAt = Carbon::parse($checkDate.' '.self::WORK_START);
            $endAt = Carbon::parse($checkDate.' '.self::WORK_END);
            $availableCount = $this->getAvailableTechniciansCountForWindow($startAt->format('Y-m-d H:i:s'), $endAt->format('Y-m-d H:i:s'));

            if ($availableCount >= $requiredTechnicians) {
                return $checkDate;
            }
        }

        return null;
    }

    /**
     * Get availability summary for admin dashboard
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
     */
    private function getPeakAvailabilityForDate(string $date): array
    {
        $peakSlot = null;
        $maxAvailable = 0;

        foreach ([8, 9, 10, 11, 13, 14, 15, 16] as $hour) {
            $startAt = Carbon::parse($date.' '.$hour.':00:00');
            $endAt = Carbon::parse($date.' '.($hour + 1).':00:00');
            $available = $this->getAvailableTechniciansCountForWindow($startAt->format('Y-m-d H:i:s'), $endAt->format('Y-m-d H:i:s'));
            if ($available > $maxAvailable) {
                $maxAvailable = $available;
                $peakSlot = [
                    'start' => $startAt->format('H:i'),
                    'end' => $endAt->format('H:i'),
                ];
            }
        }

        return [
            'timeslot' => $peakSlot ? ($peakSlot['start'].' - '.$peakSlot['end']) : null,
            'time_range' => $peakSlot ? ($peakSlot['start'].' - '.$peakSlot['end']) : null,
            'available_count' => $maxAvailable,
        ];
    }

    /**
     * Check if two time ranges overlap
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
     */
    public function checkConsecutiveDayAvailability(int $technicianId, string $startDate, int $days, ?int $timeslotId = null): bool
    {
        $technician = Technician::find($technicianId);
        if (!$technician || !$technician->is_available) {
            return false;
        }

        for ($i = 0; $i < $days; $i++) {
            $date = Carbon::parse($startDate)->addDays($i);
            $dayStart = $date->copy()->setTime(8, 0);
            $dayEnd = $date->copy()->setTime(17, 0);
            
            $overlapExists = Booking::where('technician_id', $technicianId)
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->where(function ($q) use ($dayStart, $dayEnd) {
                    $q->where('scheduled_start_at', '<', $dayEnd->format('Y-m-d H:i:s'))
                        ->where('scheduled_end_at', '>', $dayStart->format('Y-m-d H:i:s'));
                })
                ->exists();
                
            if ($overlapExists) {
                return false;
            }
        }

        return true;
    }
}