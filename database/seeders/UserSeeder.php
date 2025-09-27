<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Admin Users
        User::create([
            'first_name' => 'Kamotech',
            'last_name' => 'Admin',
            'email' => 'admin@kamotech.com',
            'password' => Hash::make('password123'),
            'phone' => '+63 917 123 4567',
            'date_of_birth' => '1985-03-15',
            'house_no_street' => '123 Admin Building',
            'barangay' => 'Poblacion',
            'city_municipality' => 'Balanga City',
            'province' => 'Bataan',
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        User::create([
            'first_name' => 'Juan',
            'middle_initial' => 'P',
            'last_name' => 'Manager',
            'email' => 'manager@kamotech.com',
            'password' => Hash::make('password123'),
            'phone' => '+63 917 123 4568',
            'date_of_birth' => '1980-07-22',
            'house_no_street' => '456 Corporate Ave',
            'barangay' => 'Bel-Air',
            'city_municipality' => 'Balanga City',
            'province' => 'Bataan',
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Create Technician Users (5 total)
        $technicians = [
            [
                'first_name' => 'Pedro',
                'middle_initial' => 'M',
                'last_name' => 'Santos',
                'email' => 'pedro@kamotech.com',
                'phone' => '+63 917 111 2222',
                'date_of_birth' => '1990-05-10',
                'house_no_street' => '789 Technician St',
                'barangay' => 'Barangay 1',
                'city_municipality' => 'Hermosa',
                'province' => 'Bataan',
            ],
            [
                'first_name' => 'Jonathan',
                'middle_initial' => 'L',
                'last_name' => 'Rumbawa',
                'email' => 'jonathan@kamotech.com',
                'phone' => '+63 917 333 4444',
                'date_of_birth' => '1988-12-03',
                'house_no_street' => '321 Service Road',
                'barangay' => 'Barangay 2',
                'city_municipality' => 'Balanga City',
                'province' => 'Bagong Silang',
            ],
            [
                'first_name' => 'Jose',
                'middle_initial' => 'W',
                'last_name' => 'Cruz',
                'email' => 'jose@kamotech.com',
                'phone' => '+63 917 555 6666',
                'date_of_birth' => '1992-08-18',
                'house_no_street' => '654 Repair Ave',
                'barangay' => 'Barangay 3',
                'city_municipality' => 'Bagac',
                'province' => 'Bataan',
            ],
            [
                'first_name' => 'John Carl',
                'middle_initial' => 'V',
                'last_name' => 'Concha',
                'email' => 'johncarl@kamotech.com',
                'phone' => '+63 917 777 8888',
                'date_of_birth' => '1987-11-25',
                'house_no_street' => '987 Workshop Lane',
                'barangay' => 'Barangay 4',
                'city_municipality' => 'Orani',
                'province' => 'Bataan',
            ],
            [
                'first_name' => 'Carlos',
                'middle_initial' => 'D',
                'last_name' => 'Mendoza',
                'email' => 'carlos@kamotech.com',
                'phone' => '+63 917 999 0000',
                'date_of_birth' => '1991-02-14',
                'house_no_street' => '147 Technical Blvd',
                'barangay' => 'Barangay 5',
                'city_municipality' => 'Pilar',
                'province' => 'Bataan',
            ],
        ];

        foreach ($technicians as $tech) {
            User::create(array_merge($tech, [
                'password' => Hash::make('password123'),
                'role' => 'technician',
                'is_active' => true,
                'email_verified_at' => now(),
            ]));
        }

        // Create Customer Users
        $customers = [
            [
                'first_name' => 'John',
                'middle_initial' => 'D',
                'last_name' => 'Dela Cruz',
                'email' => 'john@customer.com',
                'phone' => '+63 917 100 2001',
                'date_of_birth' => '1995-04-12',
                'house_no_street' => '641 Upper, Bagong Silang',
                'barangay' => 'Bagong Silang',
                'city_municipality' => 'Balanga City',
                'province' => 'Bataan',
                'nearest_landmark' => 'Near Jollibee',
            ],
            [
                'first_name' => 'Sarah',
                'middle_initial' => 'G',
                'last_name' => 'Gonzales',
                'email' => 'sarah@customer.com',
                'phone' => '+63 917 100 2002',
                'date_of_birth' => '1993-09-08',
                'house_no_street' => '262 Del Pilar Rd',
                'barangay' => 'Tuyo',
                'city_municipality' => 'Balanga City',
                'province' => 'Bataan',
            ],
            [
                'first_name' => 'Michael',
                'last_name' => 'Torres',
                'email' => 'michael@customer.com',
                'phone' => '+63 917 100 2003',
                'date_of_birth' => '1989-01-30',
                'house_no_street' => '418 Del Pilar Rd',
                'barangay' => 'Poblacion',
                'city_municipality' => 'Mariveles',
                'province' => 'Bataan',
                'nearest_landmark' => 'Near City Hall',
            ],
            [
                'first_name' => 'Linda',
                'middle_initial' => 'S',
                'last_name' => 'Santos',
                'email' => 'linda@customer.com',
                'phone' => '+63 917 100 2004',
                'date_of_birth' => '1996-06-19',
                'house_no_street' => '736 Commercial Complex',
                'barangay' => 'Arellano',
                'city_municipality' => 'Orion',
                'province' => 'Bataan',
            ],
            [
                'first_name' => 'Roberto',
                'middle_initial' => 'V',
                'last_name' => 'Villanueva',
                'email' => 'roberto@customer.com',
                'phone' => '+63 917 100 2005',
                'date_of_birth' => '1994-10-05',
                'house_no_street' => '738 Bonifacio St',
                'barangay' => 'Bagumbayan',
                'city_municipality' => 'Pilar',
                'province' => 'Bataan',
                'nearest_landmark' => 'Near Public Market',
            ],
        ];

        foreach ($customers as $customer) {
            User::create(array_merge($customer, [
                'password' => Hash::make('password123'),
                'role' => 'customer',
                'is_active' => true,
                'email_verified_at' => now(),
            ]));
        }
    }
}
