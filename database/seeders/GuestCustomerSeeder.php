<?php

namespace Database\Seeders;

use App\Models\GuestCustomer;
use App\Models\User;
use Illuminate\Database\Seeder;

class GuestCustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get an admin user for created_by
        $adminUser = User::where('role', 'admin')->first();
        if (! $adminUser) {
            $this->command->error('No admin user found. Please run UserSeeder first.');

            return;
        }

        $guestCustomers = [
            [
                'first_name' => 'Roberto',
                'middle_initial' => 'M',
                'last_name' => 'Garcia',
                'phone' => '09171234567',
                'email' => 'roberto.garcia@email.com',
                'house_no_street' => '456 Mabini St.',
                'barangay' => 'Camacho',
                'city_municipality' => 'Balanga City',
                'province' => 'Bataan',
                'nearest_landmark' => 'Near Mercury Drug',
                'notes' => 'Prefers morning appointments. Has 2 window-type AC units.',
                'total_bookings' => 3,
                'last_booking_date' => now()->subDays(15),
            ],
            [
                'first_name' => 'Elena',
                'middle_initial' => null,
                'last_name' => 'Santos',
                'phone' => '09281112222',
                'email' => null, // No email provided
                'house_no_street' => '789 Rizal Ave.',
                'barangay' => 'Bagumbayan',
                'city_municipality' => 'Orion',
                'province' => 'Bataan',
                'nearest_landmark' => 'Across from Orion Public Market',
                'notes' => 'Walk-in customer. Always pays cash.',
                'total_bookings' => 1,
                'last_booking_date' => now()->subDays(30),
            ],
            [
                'first_name' => 'Michael',
                'middle_initial' => 'J',
                'last_name' => 'Cruz',
                'phone' => '09335556666',
                'email' => 'mjcruz@gmail.com',
                'house_no_street' => 'Unit 12B, Sunrise Apartments',
                'barangay' => 'Tuyo',
                'city_municipality' => 'Balanga City',
                'province' => 'Bataan',
                'nearest_landmark' => 'Behind Balanga Elementary School',
                'notes' => 'Has both split-type and window-type units. Requires invoice.',
                'total_bookings' => 5,
                'last_booking_date' => now()->subDays(7),
            ],
            [
                'first_name' => 'Marissa',
                'middle_initial' => 'L',
                'last_name' => 'Reyes',
                'phone' => '09457778888',
                'email' => 'marissa.reyes@yahoo.com',
                'house_no_street' => '321 Sampaguita St., Villa Subdivision',
                'barangay' => 'Cupang Proper',
                'city_municipality' => 'Balanga City',
                'province' => 'Bataan',
                'nearest_landmark' => 'Near Cupang Barangay Hall',
                'notes' => 'Regular customer. Schedules quarterly maintenance.',
                'total_bookings' => 8,
                'last_booking_date' => now()->subDays(3),
            ],
            [
                'first_name' => 'Antonio',
                'middle_initial' => null,
                'last_name' => 'Mendoza',
                'phone' => '09562223333',
                'email' => null,
                'house_no_street' => '88 Mahogany Street',
                'barangay' => 'Almacen',
                'city_municipality' => 'Hermosa',
                'province' => 'Bataan',
                'nearest_landmark' => 'Near Hermosa Public Cemetery',
                'notes' => 'Elderly customer. Son usually handles the booking.',
                'total_bookings' => 2,
                'last_booking_date' => now()->subDays(45),
            ],
            [
                'first_name' => 'Grace',
                'middle_initial' => 'P',
                'last_name' => 'Villanueva',
                'phone' => '09673334444',
                'email' => 'grace.v@gmail.com',
                'house_no_street' => '15 Narra Drive',
                'barangay' => 'Bantan',
                'city_municipality' => 'Orion',
                'province' => 'Bataan',
                'nearest_landmark' => 'In front of Daan Pare Chapel',
                'notes' => 'Business owner. Has multiple AC units in her store.',
                'total_bookings' => 6,
                'last_booking_date' => now()->subDays(10),
            ],
            [
                'first_name' => 'Ricardo',
                'middle_initial' => 'S',
                'last_name' => 'Bautista',
                'phone' => '09784445555',
                'email' => 'ricardo.bautista@hotmail.com',
                'house_no_street' => 'Lot 5, Block 3, Greenview Subdivision',
                'barangay' => 'Bangal',
                'city_municipality' => 'Dinalupihan',
                'province' => 'Bataan',
                'nearest_landmark' => 'Near Dinalupihan District Hospital',
                'notes' => 'New customer referred by another client.',
                'total_bookings' => 1,
                'last_booking_date' => now()->subDays(5),
            ],
            [
                'first_name' => 'Carmen',
                'middle_initial' => null,
                'last_name' => 'Dela Cruz',
                'phone' => '09895556667',
                'email' => null,
                'house_no_street' => '234 Bonifacio Street',
                'barangay' => 'Bangkal',
                'city_municipality' => 'Abucay',
                'province' => 'Bataan',
                'nearest_landmark' => 'Behind Abucay Church',
                'notes' => 'Phone booking only. No email access.',
                'total_bookings' => 4,
                'last_booking_date' => now()->subDays(20),
            ],
        ];

        foreach ($guestCustomers as $guestData) {
            GuestCustomer::create(array_merge($guestData, [
                'created_by' => $adminUser->id,
            ]));
        }

        $this->command->info('Guest customers seeded successfully!');
    }
}
