<?php

namespace Database\Seeders;

use App\Models\AirconType;
use App\Models\Booking;
use App\Models\Service;
use App\Models\ServicePricing;
use App\Models\Technician;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Make sure all required data exists
        $customers = User::where('role', 'customer')->get();
        $services = Service::all();

        $technicians = Technician::all();
        $airconTypes = AirconType::all();
        $adminUser = User::where('role', 'admin')->first();

        if ($customers->isEmpty() || $services->isEmpty() ||
            $technicians->isEmpty() || $airconTypes->isEmpty()) {
            throw new \Exception('Missing required data. Make sure Users, Services, Technicians, and AirconTypes are seeded first.');
        }

        echo "Creating enhanced bookings with:\n";
        echo "- {$customers->count()} customers\n";
        echo "- {$services->count()} services\n";

        echo "- {$technicians->count()} technicians\n";
        echo "- {$airconTypes->count()} aircon types\n";
        echo "\n🎯 Booking Distribution for Algorithm Testing:\n";
        echo "- Recent bookings: 15 (testing current algorithm)\n";
        echo "- Historical bookings: 50 (rating/review data)\n";
        echo "- Mixed statuses: pending, completed, in-progress\n";
        echo "- Technician specialization-based assignments\n\n";

        $bookings = [];

        // Create diverse booking scenarios for testing

        // 1. Recent single-day bookings (15 bookings, last 2 weeks)
        for ($i = 1; $i <= 12; $i++) {
            $bookings[] = $this->createSingleDayBooking($i, $customers, $services, $technicians, $airconTypes, $adminUser, 'recent');
        }

        // 2. Multi-day bookings for testing (3 bookings, recent)
        for ($i = 13; $i <= 15; $i++) {
            $bookings[] = $this->createMultiDayBooking($i, $customers, $services, $technicians, $airconTypes, $adminUser, 'recent');
        }

        // 3. Historical bookings (50 bookings, past 6 months)
        for ($i = 16; $i <= 65; $i++) {
            if (rand(1, 10) <= 8) { // 80% single-day
                $bookings[] = $this->createSingleDayBooking($i, $customers, $services, $technicians, $airconTypes, $adminUser, 'historical');
            } else { // 20% multi-day
                $bookings[] = $this->createMultiDayBooking($i, $customers, $services, $technicians, $airconTypes, $adminUser, 'historical');
            }
        }

        // Insert all bookings
        echo 'Creating '.count($bookings)." enhanced bookings...\n";

        foreach ($bookings as $booking) {
            Booking::create($booking);
        }

        echo '✅ Successfully created '.count($bookings)." bookings!\n";
        echo '📋 Booking range: KMT-000001 to KMT-'.str_pad(count($bookings), 6, '0', STR_PAD_LEFT)."\n";
        echo "🏠 Single-day bookings: ~80%\n";
        echo "🏢 Multi-day bookings: ~20%\n";
        echo "🔧 AC units range: 1-10 per booking\n";
        echo "🏷️ AC brands: Mixed (including 'Unknown')\n\n";
    }

    private function createSingleDayBooking($index, $customers, $services, $technicians, $airconTypes, $adminUser, $period)
    {
        $customer = $customers->random();
        $service = $services->random();

        // Assign technician based on service expertise (weighted selection)
        $technician = $this->selectTechnicianForService($service->name, $technicians);

        $airconType = $airconTypes->random();

        // Number of AC units (mostly 1-3 for residential)
        $numberOfUnits = $this->getRealisticUnitCount();

        // Calculate duration and pricing
        $durationData = $this->calculateServiceDuration($service, $numberOfUnits);
        $pricingData = $this->calculatePricing($service, $airconType, $numberOfUnits);

        // Date based on period
        $scheduledDate = $period === 'recent'
            ? Carbon::now()->subDays(rand(1, 14))
            : Carbon::now()->subDays(rand(15, 180));

        // Choose an allowed start hour (08,09,10,11,13,14,15,16)
        $allowedHours = [8, 9, 10, 11, 13, 14, 15, 16];
        $startHour = $allowedHours[array_rand($allowedHours)];
        $paddingMinutes = 0; // planning uses service prep, no technician buffer
        [$startAt, $endAt] = $this->planSchedule($scheduledDate->copy()->setTime($startHour, 0), $durationData['total_minutes'], $paddingMinutes);

        $status = $period === 'recent'
            ? $this->getRecentBookingStatus($scheduledDate)
            : $this->getHistoricalBookingStatus();

        $creatorId = $adminUser?->id ?? $customer->id;

        return [
            'booking_number' => 'KMT-'.str_pad($index, 6, '0', STR_PAD_LEFT),
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'aircon_type_id' => $airconType->id,
            'number_of_units' => $numberOfUnits,
            'ac_brand' => $this->getRandomAcBrand(),
            'technician_id' => $technician->id,
            'scheduled_start_at' => $startAt,
            'scheduled_end_at' => $endAt,
            // removed: technician padding
            'estimated_duration_minutes' => $durationData['total_minutes'],
            // removed column; compute when needed from start/end
            'status' => $status,
            'total_amount' => $pricingData['total_amount'],
            'payment_status' => $status === 'completed' ? 'paid' : ($status === 'cancelled' ? 'unpaid' : 'pending'),
            'customer_address' => $customer->address ?? $customer->name.' Address',
            'province' => 'Bataan',
            'city_municipality' => collect(['Balanga City', 'Mariveles', 'Hermosa', 'Orani', 'Bagac'])->random(),
            'barangay' => 'Barangay '.rand(1, 20),
            'house_no_street' => rand(100, 999).' '.collect(['Rizal St', 'Magsaysay Ave', 'Del Pilar Rd', 'Bonifacio St', 'National Highway'])->random(),
            'customer_mobile' => '+63 917 '.str_pad(rand(1000000, 9999999), 7, '0', STR_PAD_LEFT),
            'nearest_landmark' => collect(['Near SM Mall', 'Opposite Jollibee', 'Behind Gas Station', 'Near Church', 'Beside School', null])->random(),
            'special_instructions' => $this->getRandomInstructions(),
            'created_by' => rand(0, 1) ? $customer->id : $creatorId,
            'created_at' => $scheduledDate->copy()->subDays(rand(1, 3)),
            'updated_at' => $scheduledDate->copy()->addDays(rand(0, 2)),
        ];
    }

    private function createMultiDayBooking($index, $customers, $services, $technicians, $airconTypes, $adminUser, $period)
    {
        $customer = $customers->random();
        $technician = $technicians->random();
        $airconType = $airconTypes->random();

        // Multi-day bookings are typically installations or large commercial jobs
        $service = $services->where('name', 'AC Installation')->first() ?? $services->random();

        // Multi-day bookings have more units (5-10)
        $numberOfUnits = rand(5, 10);

        // Calculate duration and pricing for multi-day
        $durationData = $this->calculateServiceDuration($service, $numberOfUnits);
        $estimatedDays = max(2, ceil($durationData['total_minutes'] / 480)); // 8 hours per day
        $pricingData = $this->calculatePricing($service, $airconType, $numberOfUnits);

        // Date based on period
        $scheduledDate = $period === 'recent'
            ? Carbon::now()->subDays(rand(1, 14))
            : Carbon::now()->subDays(rand(15, 180));

        // Multi-day: start at 08:00, let planner split over days
        $startAtCandidate = $scheduledDate->copy()->setTime(8, 0);
        $paddingMinutes = 0;
        [$startAt, $endAt] = $this->planSchedule($startAtCandidate, $durationData['total_minutes'], $paddingMinutes);

        $status = $period === 'recent'
            ? $this->getRecentBookingStatus($scheduledDate)
            : $this->getHistoricalBookingStatus();

        $creatorId = $adminUser?->id ?? $customer->id;

        return [
            'booking_number' => 'KMT-'.str_pad($index, 6, '0', STR_PAD_LEFT),
            'customer_id' => $customer->id,
            'customer_name' => rand(0, 1) ? null : $customer->name.' Company', // Some are commercial
            'service_id' => $service->id,
            'aircon_type_id' => $airconType->id,
            'number_of_units' => $numberOfUnits,
            'ac_brand' => rand(0, 1) ? $this->getRandomAcBrand() : 'Multiple Brands',
            'technician_id' => $technician->id,
            'scheduled_start_at' => $startAt,
            'scheduled_end_at' => $endAt,
            // removed: booking_padding_minutes column
            'estimated_duration_minutes' => $durationData['total_minutes'],
            // removed column; compute when needed from start/end
            'status' => $status,
            'total_amount' => $pricingData['total_amount'],
            'payment_status' => $status === 'completed' ? 'paid' : ($status === 'cancelled' ? 'unpaid' : 'pending'),
            'customer_address' => $customer->address ?? $customer->name.' Commercial Building',
            'province' => 'Bataan',
            'city_municipality' => collect(['Balanga City', 'Mariveles', 'Hermosa', 'Orani', 'Bagac'])->random(),
            'barangay' => 'Barangay '.rand(1, 20),
            'house_no_street' => rand(100, 999).' '.collect(['Commercial Complex', 'Business Center', 'Industrial Zone', 'Corporate Building'])->random(),
            'customer_mobile' => '+63 917 '.str_pad(rand(1000000, 9999999), 7, '0', STR_PAD_LEFT),
            'nearest_landmark' => collect(['Near Business District', 'Beside Factory', 'Main Commercial Area', 'Industrial Park', null])->random(),
            'special_instructions' => $this->getMultiDayInstructions(),
            'created_by' => rand(0, 1) ? $customer->id : $creatorId,
            'created_at' => $scheduledDate->copy()->subDays(rand(3, 7)),
            'updated_at' => $scheduledDate->copy()->addDays(rand(0, $estimatedDays)),
        ];
    }

    /**
     * Plan a schedule across business hours (08-12, 13-17), skipping lunch, with end padding.
     * Returns [scheduled_start_at, scheduled_end_at] ISO strings.
     */
    private function planSchedule(Carbon $startAt, int $workMinutes, int $paddingMinutes = 0): array
    {
        // Align start to business window: if in lunch, jump to 13:00; if before 08:00, set to 08:00; if after 17:00, next day 08:00
        $startAt = $this->normalizeStart($startAt);

        $current = $startAt->copy();
        $remaining = $workMinutes;
        $safetyHops = 0;

        while ($remaining > 0) {
            // Determine available minutes in current window
            [$winStart, $winEnd] = $this->currentWindow($current);
            if ($current->lt($winStart)) {
                $current = $winStart->copy();
            }
            // Minutes available until window end from current time
            $available = max(0, $current->diffInMinutes($winEnd, false));
            if ($available <= 0) {
                // Move to next window
                $current = $this->nextWindowStart($current);
                if (++$safetyHops > 1000) {
                    break;
                }

                continue;
            }
            $consume = min($remaining, $available);
            $current->addMinutes($consume);
            $remaining -= $consume;
            if ($remaining > 0) {
                // Jump to next window start
                $current = $this->nextWindowStart($current);
                if (++$safetyHops > 1000) {
                    break;
                }
            }
        }

        // Apply end padding
        $endWithPadding = $current->copy()->addMinutes($paddingMinutes);

        return [
            $startAt->format('Y-m-d H:i:s'),
            $endWithPadding->format('Y-m-d H:i:s'),
        ];
    }

    private function normalizeStart(Carbon $dt): Carbon
    {
        $h = (int) $dt->format('H');
        $m = (int) $dt->format('i');
        // Before 08:00 → set to 08:00
        if ($dt->lt($dt->copy()->setTime(8, 0))) {
            return $dt->copy()->setTime(8, 0);
        }
        // Lunch 12:00–13:00 → set to 13:00
        if ($dt->gte($dt->copy()->setTime(12, 0)) && $dt->lt($dt->copy()->setTime(13, 0))) {
            return $dt->copy()->setTime(13, 0);
        }
        // After 17:00 → next day 08:00
        if ($dt->gte($dt->copy()->setTime(17, 0))) {
            return $dt->copy()->addDay()->setTime(8, 0);
        }

        return $dt;
    }

    private function currentWindow(Carbon $dt): array
    {
        $morningStart = $dt->copy()->setTime(8, 0);
        $morningEnd = $dt->copy()->setTime(12, 0);
        $afternoonStart = $dt->copy()->setTime(13, 0);
        $afternoonEnd = $dt->copy()->setTime(17, 0);

        if ($dt->lt($morningEnd)) {
            return [$morningStart, $morningEnd];
        }

        return [$afternoonStart, $afternoonEnd];
    }

    private function nextWindowStart(Carbon $dt): Carbon
    {
        // If we are at/before lunch (<= 12:00), next is 13:00 same day; else next day 08:00
        if ($dt->lte($dt->copy()->setTime(12, 0))) {
            return $dt->copy()->setTime(13, 0);
        }

        return $dt->copy()->addDay()->setTime(8, 0);
    }

    private function getRealisticUnitCount(): int
    {
        // Weighted distribution for realistic AC unit counts
        $weights = [
            1 => 50, // 50% - single unit (most common)
            2 => 25, // 25% - two units
            3 => 15, // 15% - three units
            4 => 7,  // 7% - four units
            5 => 3,  // 3% - five units (commercial)
        ];

        $rand = rand(1, 100);
        $cumulative = 0;

        foreach ($weights as $units => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $units;
            }
        }

        return 1; // fallback
    }

    private function calculateServiceDuration($service, $numberOfUnits): array
    {
        $baseMinutes = $service->duration_minutes ?? 90;

        // Progressive time calculation (efficiency improves with more units)
        $unit1 = $baseMinutes;
        $units2to5 = min(4, max(0, $numberOfUnits - 1)) * ($baseMinutes * 0.8);
        $units6plus = max(0, $numberOfUnits - 5) * ($baseMinutes * 0.6);

        $totalMinutes = $unit1 + $units2to5 + $units6plus;

        return [
            'total_minutes' => $totalMinutes,
            'estimated_hours' => round($totalMinutes / 60, 1),
        ];
    }

    private function calculatePricing($service, $airconType, $numberOfUnits): array
    {
        // Try to get dynamic pricing, fall back to base price
        $basePrice = ServicePricing::getPricing($service->id, $airconType->id) ?? $service->base_price ?? 1500;

        // Simple pricing: base price × number of units
        $totalServicePrice = $basePrice * $numberOfUnits;
        $totalAmount = $totalServicePrice;

        return [
            'service_total' => $totalServicePrice,
            'total_amount' => round($totalAmount, 2),
        ];
    }

    private function getRandomAcBrand(): string
    {
        $brands = [
            'Samsung', 'LG', 'Carrier', 'Daikin', 'Panasonic', 'Sharp',
            'Kolin', 'Koppel', 'Condura', 'Hitachi', 'TCL', 'Haier',
            'Unknown', 'Unknown', 'Not Sure', // Higher chance of unknown
        ];

        return $brands[array_rand($brands)];
    }

    private function getRecentBookingStatus($scheduledDate)
    {
        if ($scheduledDate->isFuture()) {
            return collect(['pending', 'pending', 'confirmed', 'cancel_requested'])->random();
        } elseif ($scheduledDate->isToday()) {
            return collect(['confirmed', 'in_progress'])->random();
        } else {
            return collect(['completed', 'completed', 'completed', 'cancelled'])->random();
        }
    }

    private function getHistoricalBookingStatus()
    {
        return collect([
            'completed', 'completed', 'completed', 'completed', 'completed',
            'completed', 'completed', 'cancelled', 'cancelled',
        ])->random();
    }

    private function getRandomInstructions()
    {
        $instructions = [
            'Please call before arriving',
            'AC unit is on the second floor',
            'Gate is usually locked, please ring the bell',
            'AC has been making loud noises recently',
            'Last cleaning was 6 months ago',
            'Unit not cooling properly',
            'Remote control not working',
            'Water leaking from indoor unit',
            'Please bring ladder for high units',
            'Customer will be available after 10 AM',
            null, null, null, // Some bookings have no instructions
        ];

        return $instructions[array_rand($instructions)];
    }

    private function getMultiDayInstructions()
    {
        $instructions = [
            'Commercial installation - coordinate with building management',
            'Multiple floors - elevator access required',
            'Large project - materials delivery needed',
            'Office building - work after hours preferred',
            'Multiple brands and types - bring various tools',
            'Phased installation over multiple days',
            'Coordinate with electrical contractor',
            null, null, // Some have no special instructions
        ];

        return $instructions[array_rand($instructions)];
    }

    /**
     * Select technician based on service expertise (weighted selection)
     */
    private function selectTechnicianForService($serviceName, $technicians)
    {
        // Map technicians by employee_id to ensure consistency
        $techniciansByEmployee = $technicians->keyBy('employee_id');

        // Define technician expertise weights by employee_id for reliability
        $technicianWeights = [
            'AC Cleaning' => [
                'KMT-001' => 45, // Pedro - Cleaning Expert
                'KMT-002' => 15, // Jonathan
                'KMT-003' => 10, // Jose
                'KMT-004' => 25, // John Carl - Good at everything
                'KMT-005' => 5,  // Carlos
            ],
            'AC Maintenance' => [
                'KMT-001' => 40, // Pedro - Maintenance Expert
                'KMT-002' => 15, // Jonathan
                'KMT-003' => 15, // Jose
                'KMT-004' => 25, // John Carl
                'KMT-005' => 5,  // Carlos
            ],
            'AC Installation' => [
                'KMT-001' => 5,  // Pedro - Weak at installation
                'KMT-002' => 50, // Jonathan - Installation Expert
                'KMT-003' => 10, // Jose
                'KMT-004' => 25, // John Carl
                'KMT-005' => 10, // Carlos
            ],
            'AC Relocation' => [
                'KMT-001' => 5,  // Pedro
                'KMT-002' => 45, // Jonathan - Installation/Relocation Expert
                'KMT-003' => 15, // Jose
                'KMT-004' => 25, // John Carl
                'KMT-005' => 10, // Carlos
            ],
            'AC Repair' => [
                'KMT-001' => 10, // Pedro
                'KMT-002' => 15, // Jonathan
                'KMT-003' => 45, // Jose - Repair Expert
                'KMT-004' => 25, // John Carl
                'KMT-005' => 5,  // Carlos
            ],
            'Freon Charging' => [
                'KMT-001' => 8,  // Pedro
                'KMT-002' => 12, // Jonathan
                'KMT-003' => 25, // Jose - Good with freon
                'KMT-004' => 20, // John Carl
                'KMT-005' => 35, // Carlos - Freon Expert
            ],
            'AC Troubleshooting' => [
                'KMT-001' => 15, // Pedro
                'KMT-002' => 10, // Jonathan - Weak at diagnosis
                'KMT-003' => 5,  // Jose - Weak at diagnosis
                'KMT-004' => 60, // John Carl - Diagnostic Expert
                'KMT-005' => 10, // Carlos
            ],
            'Repiping Service' => [
                'KMT-001' => 8,  // Pedro
                'KMT-002' => 15, // Jonathan
                'KMT-003' => 40, // Jose - Repiping Expert
                'KMT-004' => 25, // John Carl
                'KMT-005' => 12, // Carlos
            ],
        ];

        // Get weights for this service or use balanced weights
        $weights = $technicianWeights[$serviceName] ?? [
            'KMT-001' => 20, 'KMT-002' => 20, 'KMT-003' => 20, 'KMT-004' => 20, 'KMT-005' => 20,
        ];

        // Weighted random selection
        $totalWeight = array_sum($weights);
        $random = rand(1, $totalWeight);

        foreach ($weights as $employeeId => $weight) {
            $random -= $weight;
            if ($random <= 0 && $techniciansByEmployee->has($employeeId)) {
                return $techniciansByEmployee->get($employeeId);
            }
        }

        // Fallback to random selection
        return $technicians->random();
    }
}
