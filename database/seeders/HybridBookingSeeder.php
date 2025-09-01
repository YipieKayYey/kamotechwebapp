<?php

namespace Database\Seeders;

use App\Models\AirconType;
use App\Models\Booking;
use App\Models\Service;
use App\Models\Technician;
use App\Models\Timeslot;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class HybridBookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create some sample hybrid bookings with customer_name (guest bookings)
        $services = Service::all();

        $technicians = Technician::all();
        $timeslots = Timeslot::all();
        $airconTypes = AirconType::all();
        $adminUser = User::where('role', 'admin')->first();

        $guestBookings = [
            [
                'customer_name' => 'Maria Santos',
                'customer_address' => '456 Roxas Boulevard, Balanga City, Bataan',
                'nearest_landmark' => 'Near Bataan Capitol Building',

                'phone_number' => '+63 917 555 1234',
            ],
            [
                'customer_name' => 'Roberto Cruz',
                'customer_address' => '123 MacArthur Highway, Mariveles, Bataan',
                'nearest_landmark' => 'Opposite Shell Gas Station',

                'phone_number' => '+63 917 555 5678',
            ],
            [
                'customer_name' => 'Elena Reyes',
                'customer_address' => '789 Circumferential Road, Hermosa, Bataan',
                'nearest_landmark' => 'Behind Jollibee Hermosa',

                'phone_number' => '+63 917 555 9012',
            ],
            [
                'customer_name' => 'Carlos Mendoza',
                'customer_address' => '321 National Highway, Orani, Bataan',
                'nearest_landmark' => 'Near Orani Public Market',

                'phone_number' => '+63 917 555 3456',
            ],
            [
                'customer_name' => 'Lisa Garcia',
                'customer_address' => '654 Barangay Road, Bagac, Bataan',
                'nearest_landmark' => 'Near Bagac Church',

                'phone_number' => '+63 917 555 7890',
            ],
        ];

        echo "Creating hybrid bookings (guest customers)...\n";

        $bookingsCreated = 0;
        $technicianAssignments = [];

        foreach ($guestBookings as $index => $guestData) {
            $service = $services->random();

            // ALWAYS assign technician based on service expertise (weighted selection)
            $technician = $this->selectTechnicianForService($service->name, $technicians);

            // Track technician assignments for reporting
            $technicianName = $technician->user->name;
            if (! isset($technicianAssignments[$technicianName])) {
                $technicianAssignments[$technicianName] = 0;
            }
            $technicianAssignments[$technicianName]++;

            $timeslot = $timeslots->random();
            $airconType = $airconTypes->random();

            // Random number of units (1-3 for guests, typically smaller jobs)
            $numberOfUnits = collect([1, 1, 1, 2, 2, 3])->random(); // Weighted towards 1

            // Random date in the next 2 weeks
            $scheduledDate = Carbon::now()->addDays(rand(1, 14));

            // Calculate pricing (using Booking model for consistency)
            $booking = new Booking([
                'service_id' => $service->id,
                'aircon_type_id' => $airconType->id,
                'number_of_units' => $numberOfUnits,
            ]);
            $totalAmount = $booking->calculateTotalAmount();

            Booking::create([
                'booking_number' => 'KMT-'.str_pad(66 + $index, 6, '0', STR_PAD_LEFT), // Continue from BookingSeeder (65 + 1)
                'customer_id' => null, // No customer ID for guest bookings
                'customer_name' => $guestData['customer_name'],
                'service_id' => $service->id,
                'aircon_type_id' => $airconType->id,
                'technician_id' => $technician->id, // ALWAYS assign technician
                'number_of_units' => $numberOfUnits,

                'scheduled_date' => $scheduledDate->format('Y-m-d'),
                'timeslot_id' => $timeslot->id,
                'status' => 'pending',
                'total_amount' => $totalAmount,
                'payment_status' => 'pending',
                'customer_address' => $guestData['customer_address'],
                'nearest_landmark' => $guestData['nearest_landmark'],

                'province' => 'Bataan',
                'city_municipality' => collect(['Balanga City', 'Mariveles', 'Hermosa', 'Orani', 'Bagac'])->random(),
                'barangay' => 'Barangay '.rand(1, 20),
                'house_no_street' => rand(100, 999).' Guest Address',
                'customer_mobile' => $guestData['phone_number'],
                'special_instructions' => $this->getRandomInstructions(),
                'created_by' => $adminUser->id,
                'created_at' => Carbon::now()->subMinutes(rand(30, 1440)), // Created within last 24 hours
                'updated_at' => Carbon::now()->subMinutes(rand(0, 30)),
            ]);

            $bookingsCreated++;
            echo "  📋 {$guestData['customer_name']} → {$service->name} → {$technicianName}\n";
        }

        echo "\n✅ Created {$bookingsCreated} hybrid bookings with guest customers!\n";
        echo "📞 Features: Customer names, landmarks, phone numbers for walk-in bookings\n";
        echo "🎯 Technician assignments based on service expertise:\n";
        foreach ($technicianAssignments as $name => $count) {
            echo "  • {$name}: {$count} jobs\n";
        }
        echo "\n";
    }

    private function getRandomInstructions(): string
    {
        $instructions = [
            'Please bring ladder for ceiling unit access',
            'Customer prefers morning schedule',
            'Call before arrival, gate is locked',
            'Parking available in front of house',
            'Please wear shoe covers inside',
            'Multiple units need cleaning',
            'Customer will be present during service',
            'Access through side entrance',
            'Bring extension cord if needed',
            'Dog in the house, please be careful',
            null, // Sometimes no instructions
        ];

        return $instructions[array_rand($instructions)] ?? '';
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
            1 => 20, 2 => 20, 3 => 20, 4 => 20, 5 => 20,
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
