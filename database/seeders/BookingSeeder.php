<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\User;
use App\Models\Service;

use App\Models\Technician;
use App\Models\Timeslot;
use App\Models\AirconType;
use App\Models\ServicePricing;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

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
        $timeslots = Timeslot::all();
        $airconTypes = AirconType::all();
        $adminUser = User::where('role', 'admin')->first();

        if ($customers->isEmpty() || $services->isEmpty() || 
            $technicians->isEmpty() || $timeslots->isEmpty() || $airconTypes->isEmpty()) {
            throw new \Exception('Missing required data. Make sure Users, Services, Technicians, Timeslots, and AirconTypes are seeded first.');
        }

        echo "Creating enhanced bookings with:\n";
        echo "- {$customers->count()} customers\n";
        echo "- {$services->count()} services\n";

        echo "- {$technicians->count()} technicians\n";
        echo "- {$timeslots->count()} timeslots\n";
        echo "- {$airconTypes->count()} aircon types\n\n";

        $bookings = [];
        
        // Create diverse booking scenarios for testing
        
        // 1. Recent single-day bookings (15 bookings, last 2 weeks)
        for ($i = 1; $i <= 12; $i++) {
            $bookings[] = $this->createSingleDayBooking($i, $customers, $services, $technicians, $timeslots, $airconTypes, $adminUser, 'recent');
        }
        
        // 2. Multi-day bookings for testing (3 bookings, recent)
        for ($i = 13; $i <= 15; $i++) {
            $bookings[] = $this->createMultiDayBooking($i, $customers, $services, $technicians, $timeslots, $airconTypes, $adminUser, 'recent');
        }
        
        // 3. Historical bookings (50 bookings, past 6 months) 
        for ($i = 16; $i <= 65; $i++) {
            if (rand(1, 10) <= 8) { // 80% single-day
                $bookings[] = $this->createSingleDayBooking($i, $customers, $services, $technicians, $timeslots, $airconTypes, $adminUser, 'historical');
            } else { // 20% multi-day
                $bookings[] = $this->createMultiDayBooking($i, $customers, $services, $technicians, $timeslots, $airconTypes, $adminUser, 'historical');
            }
        }

        // Insert all bookings
        echo "Creating " . count($bookings) . " enhanced bookings...\n";
        
        foreach ($bookings as $booking) {
            Booking::create($booking);
        }
        
        echo "✅ Successfully created " . count($bookings) . " bookings!\n";
        echo "📋 Booking range: KMT-000001 to KMT-" . str_pad(count($bookings), 6, '0', STR_PAD_LEFT) . "\n";
        echo "🏠 Single-day bookings: ~80%\n";
        echo "🏢 Multi-day bookings: ~20%\n";
        echo "🔧 AC units range: 1-10 per booking\n";
        echo "🏷️ AC brands: Mixed (including 'Unknown')\n\n";
    }

    private function createSingleDayBooking($index, $customers, $services, $technicians, $timeslots, $airconTypes, $adminUser, $period)
    {
        $customer = $customers->random();
        $service = $services->random();
        
        // Assign technician based on service expertise (weighted selection)
        $technician = $this->selectTechnicianForService($service->name, $technicians);
        
        $timeslot = $timeslots->random();
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
            
        $status = $period === 'recent'
            ? $this->getRecentBookingStatus($scheduledDate)
            : $this->getHistoricalBookingStatus();

        return [
            'booking_number' => 'KMT-' . str_pad($index, 6, '0', STR_PAD_LEFT),
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'aircon_type_id' => $airconType->id,
            'number_of_units' => $numberOfUnits,
            'ac_brand' => $this->getRandomAcBrand(),
            'technician_id' => $technician->id,
            'scheduled_date' => $scheduledDate->format('Y-m-d'),
            'scheduled_end_date' => $scheduledDate->format('Y-m-d'), // Same day
            'timeslot_id' => $timeslot->id,
            'estimated_duration_minutes' => $durationData['total_minutes'],
            'estimated_days' => 1,
            'status' => $status,
            'total_amount' => $pricingData['total_amount'],
            'payment_status' => $status === 'completed' ? 'paid' : ($status === 'cancelled' ? 'refunded' : 'pending'),
            'customer_address' => $customer->address ?? $customer->name . ' Address',
            'province' => 'Bataan',
            'city_municipality' => collect(['Balanga City', 'Mariveles', 'Hermosa', 'Orani', 'Bagac'])->random(),
            'barangay' => 'Barangay ' . rand(1, 20),
            'house_no_street' => rand(100, 999) . ' ' . collect(['Rizal St', 'Magsaysay Ave', 'Del Pilar Rd', 'Bonifacio St', 'National Highway'])->random(),
            'customer_mobile' => '+63 917 ' . str_pad(rand(1000000, 9999999), 7, '0', STR_PAD_LEFT),
            'nearest_landmark' => collect(['Near SM Mall', 'Opposite Jollibee', 'Behind Gas Station', 'Near Church', 'Beside School', null])->random(),
            'special_instructions' => $this->getRandomInstructions(),
            'created_by' => rand(0, 1) ? $customer->id : $adminUser->id,
            'created_at' => $scheduledDate->copy()->subDays(rand(1, 3)),
            'updated_at' => $scheduledDate->copy()->addDays(rand(0, 2)),
        ];
    }

    private function createMultiDayBooking($index, $customers, $services, $technicians, $timeslots, $airconTypes, $adminUser, $period)
    {
        $customer = $customers->random();
        $technician = $technicians->random();
        $timeslot = $timeslots->random();
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
            
        $scheduledEndDate = $scheduledDate->copy()->addDays($estimatedDays - 1);
        
        $status = $period === 'recent'
            ? $this->getRecentBookingStatus($scheduledDate)
            : $this->getHistoricalBookingStatus();

        return [
            'booking_number' => 'KMT-' . str_pad($index, 6, '0', STR_PAD_LEFT),
            'customer_id' => $customer->id,
            'customer_name' => rand(0, 1) ? null : $customer->name . ' Company', // Some are commercial
            'service_id' => $service->id,
            'aircon_type_id' => $airconType->id,
            'number_of_units' => $numberOfUnits,
            'ac_brand' => rand(0, 1) ? $this->getRandomAcBrand() : 'Multiple Brands',
            'technician_id' => $technician->id,

            'scheduled_date' => $scheduledDate->format('Y-m-d'),
            'scheduled_end_date' => $scheduledEndDate->format('Y-m-d'),
            'timeslot_id' => $timeslot->id,
            'estimated_duration_minutes' => $durationData['total_minutes'],
            'estimated_days' => $estimatedDays,
            'status' => $status,
            'total_amount' => $pricingData['total_amount'],
            'payment_status' => $status === 'completed' ? 'paid' : ($status === 'cancelled' ? 'refunded' : 'pending'),
            'customer_address' => $customer->address ?? $customer->name . ' Commercial Building',
            'province' => 'Bataan',
            'city_municipality' => collect(['Balanga City', 'Mariveles', 'Hermosa', 'Orani', 'Bagac'])->random(),
            'barangay' => 'Barangay ' . rand(1, 20),
            'house_no_street' => rand(100, 999) . ' ' . collect(['Commercial Complex', 'Business Center', 'Industrial Zone', 'Corporate Building'])->random(),
            'customer_mobile' => '+63 917 ' . str_pad(rand(1000000, 9999999), 7, '0', STR_PAD_LEFT),
            'nearest_landmark' => collect(['Near Business District', 'Beside Factory', 'Main Commercial Area', 'Industrial Park', null])->random(),
            'special_instructions' => $this->getMultiDayInstructions(),
            'created_by' => rand(0, 1) ? $customer->id : $adminUser->id,
            'created_at' => $scheduledDate->copy()->subDays(rand(3, 7)),
            'updated_at' => $scheduledDate->copy()->addDays(rand(0, $estimatedDays)),
        ];
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
        
        // Multi-unit pricing with discounts
        $totalServicePrice = $basePrice; // First unit full price
        
        if ($numberOfUnits > 1) {
            $totalServicePrice += ($basePrice * 0.8); // Second unit 20% discount
        }
        
        if ($numberOfUnits > 2) {
            $additionalUnits = $numberOfUnits - 2;
            $totalServicePrice += ($basePrice * 0.7 * $additionalUnits); // 30% discount for 3rd+ units
        }
        
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
            'Unknown', 'Unknown', 'Not Sure' // Higher chance of unknown
        ];
        
        return $brands[array_rand($brands)];
    }

    private function getRecentBookingStatus($scheduledDate)
    {
        if ($scheduledDate->isFuture()) {
            return collect(['pending', 'confirmed'])->random();
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
            'completed', 'completed', 'cancelled', 'cancelled'
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
            null, null, null // Some bookings have no instructions
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
            null, null // Some have no special instructions
        ];

        return $instructions[array_rand($instructions)];
    }

    /**
     * Select technician based on service expertise (weighted selection)
     */
    private function selectTechnicianForService($serviceName, $technicians)
    {
        // Define technician expertise weights for each service
        $technicianWeights = [
            'AC Cleaning' => [
                1 => 45, // Pedro - Cleaning Expert
                2 => 15, // Maria  
                3 => 10, // Jose
                4 => 25, // Ana - Good at everything
                5 => 5,  // Carlos
            ],
            'AC Maintenance' => [
                1 => 40, // Pedro - Maintenance Expert
                2 => 15, // Maria
                3 => 15, // Jose
                4 => 25, // Ana
                5 => 5,  // Carlos
            ],
            'AC Installation' => [
                1 => 5,  // Pedro - Weak at installation
                2 => 50, // Maria - Installation Expert
                3 => 10, // Jose
                4 => 25, // Ana
                5 => 10, // Carlos
            ],
            'AC Relocation' => [
                1 => 5,  // Pedro
                2 => 45, // Maria - Installation/Relocation Expert  
                3 => 15, // Jose
                4 => 25, // Ana
                5 => 10, // Carlos
            ],
            'AC Repair' => [
                1 => 10, // Pedro
                2 => 15, // Maria
                3 => 45, // Jose - Repair Expert
                4 => 25, // Ana
                5 => 5,  // Carlos
            ],
            'Freon Charging' => [
                1 => 8,  // Pedro
                2 => 12, // Maria
                3 => 25, // Jose - Good with freon
                4 => 20, // Ana
                5 => 35, // Carlos - Freon Expert
            ],
            'AC Troubleshooting' => [
                1 => 15, // Pedro
                2 => 10, // Maria - Weak at diagnosis
                3 => 5,  // Jose - Weak at diagnosis
                4 => 60, // Ana - Diagnostic Expert
                5 => 10, // Carlos
            ],
            'Repiping Service' => [
                1 => 8,  // Pedro
                2 => 15, // Maria
                3 => 40, // Jose - Repiping Expert
                4 => 25, // Ana
                5 => 12, // Carlos
            ],
        ];

        // Get weights for this service or use balanced weights
        $weights = $technicianWeights[$serviceName] ?? [
            1 => 20, 2 => 20, 3 => 20, 4 => 20, 5 => 20
        ];

        // Convert technician collection to array indexed by ID
        $techniciansArray = $technicians->keyBy('id');
        
        // Weighted random selection
        $totalWeight = array_sum($weights);
        $random = rand(1, $totalWeight);
        
        foreach ($weights as $technicianId => $weight) {
            $random -= $weight;
            if ($random <= 0 && $techniciansArray->has($technicianId)) {
                return $techniciansArray->get($technicianId);
            }
        }
        
        // Fallback to random selection
        return $technicians->random();
    }
}
