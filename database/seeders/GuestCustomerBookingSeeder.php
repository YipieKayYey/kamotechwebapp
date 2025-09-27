<?php

namespace Database\Seeders;

use App\Models\AirconType;
use App\Models\Booking;
use App\Models\GuestCustomer;
use App\Models\Service;
use App\Models\Technician;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class GuestCustomerBookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get required data
        $adminUser = User::where('role', 'admin')->first();
        if (! $adminUser) {
            $this->command->error('No admin user found. Please run UserSeeder first.');

            return;
        }

        $guestCustomers = GuestCustomer::all();
        if ($guestCustomers->isEmpty()) {
            $this->command->error('No guest customers found. Please run GuestCustomerSeeder first.');

            return;
        }

        $services = Service::where('is_active', true)->get();
        $airconTypes = AirconType::all();
        $technicians = Technician::with('user')->get();

        // Create bookings for guest customers
        $bookingData = [
            // Roberto Garcia - Regular maintenance customer
            [
                'guest' => 'Roberto',
                'service' => 'AC Maintenance',
                'aircon_type' => 'Window Type',
                'units' => 2,
                'status' => 'completed',
                'days_ago' => 15,
            ],
            [
                'guest' => 'Roberto',
                'service' => 'AC Cleaning',
                'aircon_type' => 'Window Type',
                'units' => 2,
                'status' => 'pending',
                'days_ago' => 0, // Today
            ],

            // Michael Cruz - Multiple units
            [
                'guest' => 'Michael',
                'service' => 'AC Repair',
                'aircon_type' => 'Split Type',
                'units' => 1,
                'status' => 'completed',
                'days_ago' => 30,
            ],
            [
                'guest' => 'Michael',
                'service' => 'AC Maintenance',
                'aircon_type' => 'Window Type',
                'units' => 2,
                'status' => 'in_progress',
                'days_ago' => 1,
            ],

            // Marissa Reyes - Quarterly maintenance
            [
                'guest' => 'Marissa',
                'service' => 'AC Cleaning',
                'aircon_type' => 'Split Type',
                'units' => 3,
                'status' => 'completed',
                'days_ago' => 90,
            ],
            [
                'guest' => 'Marissa',
                'service' => 'AC Maintenance',
                'aircon_type' => 'Split Type',
                'units' => 3,
                'status' => 'confirmed',
                'days_ago' => -1, // Tomorrow
            ],

            // Grace Villanueva - Business customer
            [
                'guest' => 'Grace',
                'service' => 'AC Installation',
                'aircon_type' => 'Cassette Type',
                'units' => 2,
                'status' => 'completed',
                'days_ago' => 60,
            ],

            // Carmen Dela Cruz - Phone booking customer
            [
                'guest' => 'Carmen',
                'service' => 'AC Troubleshooting',
                'aircon_type' => 'Window Type',
                'units' => 1,
                'status' => 'pending',
                'days_ago' => 2,
            ],
        ];

        foreach ($bookingData as $data) {
            $guest = $guestCustomers->firstWhere('first_name', $data['guest']);
            if (! $guest) {
                continue;
            }

            $service = $services->firstWhere('name', $data['service']);
            $airconType = $airconTypes->firstWhere('name', $data['aircon_type']);
            $technician = $technicians->random();

            if (! $service || ! $airconType) {
                continue;
            }

            // Calculate dates
            $scheduledDate = Carbon::now()->addDays(-$data['days_ago'])->setHour(9)->setMinute(0);
            $endDate = $scheduledDate->copy()->addHours(3);

            $booking = Booking::create([
                'guest_customer_id' => $guest->id,
                'customer_id' => null, // Guest booking
                'service_id' => $service->id,
                'aircon_type_id' => $airconType->id,
                'number_of_units' => $data['units'],
                'ac_brand' => 'Various',
                'technician_id' => $technician->id,
                'scheduled_start_at' => $scheduledDate,
                'scheduled_end_at' => $endDate,
                'estimated_duration_minutes' => 180,
                'status' => $data['status'],
                'total_amount' => $service->base_price * $data['units'],
                'payment_status' => $data['status'] === 'completed' ? 'paid' : 'pending',
                'use_custom_address' => false,
                'customer_address' => $guest->full_address,
                'province' => $guest->province,
                'city_municipality' => $guest->city_municipality,
                'barangay' => $guest->barangay,
                'house_no_street' => $guest->house_no_street,
                'customer_mobile' => $guest->phone,
                'nearest_landmark' => $guest->nearest_landmark,
                'created_by' => $adminUser->id,
                'created_at' => $scheduledDate->copy()->subDays(rand(1, 5)),
                'updated_at' => $scheduledDate->copy()->subDays(rand(0, 1)),
            ]);

            // Update status timestamps
            if ($data['status'] === 'confirmed') {
                $booking->update([
                    'confirmed_at' => $scheduledDate->copy()->subDays(1),
                    'confirmed_by' => $adminUser->id,
                ]);
            } elseif ($data['status'] === 'completed') {
                $booking->update([
                    'confirmed_at' => $scheduledDate->copy()->subDays(2),
                    'confirmed_by' => $adminUser->id,
                    'completed_at' => $scheduledDate->copy()->addHours(3),
                ]);
            }
        }

        $this->command->info('Guest customer bookings seeded successfully!');
    }
}
