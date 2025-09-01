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
        // Create Admin Users with structured addresses
        User::create([
            'name' => 'Kamotech Admin',
            'email' => 'admin@kamotech.com',
            'password' => Hash::make('password123'),
            'phone' => '+63 917 123 4567',
            'province' => 'Bataan',
            'city_municipality' => 'Balanga City',
            'barangay' => 'Central',
            'house_no_street' => '123 Capitol Drive',
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Manager Juan',
            'email' => 'manager@kamotech.com',
            'password' => Hash::make('password123'),
            'phone' => '+63 917 123 4568',
            'province' => 'Bataan',
            'city_municipality' => 'Mariveles',
            'barangay' => 'Poblacion',
            'house_no_street' => '456 National Highway',
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Create Technician Users (5 total) with structured addresses
        $technicians = [
            [
                'name' => 'Pedro Santos',
                'email' => 'pedro@kamotech.com',
                'phone' => '+63 917 111 2222',
                'province' => 'Bataan',
                'city_municipality' => 'Hermosa',
                'barangay' => 'Tipo',
                'house_no_street' => '789 Roxas Street',
            ],
            [
                'name' => 'Maria Garcia',
                'email' => 'maria@kamotech.com',
                'phone' => '+63 917 333 4444',
                'province' => 'Bataan',
                'city_municipality' => 'Orani',
                'barangay' => 'Centro',
                'house_no_street' => '321 Luna Street',
            ],
            [
                'name' => 'Jose Cruz',
                'email' => 'jose@kamotech.com',
                'phone' => '+63 917 555 6666',
                'province' => 'Bataan',
                'city_municipality' => 'Balanga City',
                'barangay' => 'Poblacion',
                'house_no_street' => '654 Rizal Avenue',
            ],
            [
                'name' => 'Ana Reyes',
                'email' => 'ana@kamotech.com',
                'phone' => '+63 917 777 8888',
                'province' => 'Bataan',
                'city_municipality' => 'Bagac',
                'barangay' => 'Bagong Silang',
                'house_no_street' => '987 MacArthur Highway',
            ],
            [
                'name' => 'Carlos Mendoza',
                'email' => 'carlos@kamotech.com',
                'phone' => '+63 917 999 0000',
                'province' => 'Bataan',
                'city_municipality' => 'Dinalupihan',
                'barangay' => 'San Ramon',
                'house_no_street' => '147 Garcia Street',
            ],
        ];

        foreach ($technicians as $tech) {
            User::create([
                'name' => $tech['name'],
                'email' => $tech['email'],
                'password' => Hash::make('password123'),
                'phone' => $tech['phone'],
                'province' => $tech['province'],
                'city_municipality' => $tech['city_municipality'],
                'barangay' => $tech['barangay'],
                'house_no_street' => $tech['house_no_street'],
                'role' => 'technician',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        }

        // Create Customer Users with mixed address types
        $customers = [
            [
                'name' => 'John Dela Cruz',
                'email' => 'john@customer.com',
                'phone' => '+63 917 100 2001',
                'province' => 'Bataan',
                'city_municipality' => 'Balanga City',
                'barangay' => 'Sibacan',
                'house_no_street' => '123 Rizal Street',
            ],
            [
                'name' => 'Sarah Gonzales',
                'email' => 'sarah@customer.com',
                'phone' => '+63 917 100 2002',
                'province' => 'Bataan',
                'city_municipality' => 'Mariveles',
                'barangay' => 'Camaya',
                'house_no_street' => '456 Magsaysay Ave',
            ],
            [
                'name' => 'Michael Torres',
                'email' => 'michael@customer.com',
                'phone' => '+63 917 100 2003',
                // Legacy address format (some customers have old format)
                'address' => '789 Del Pilar Road, Hermosa, Bataan',
            ],
            [
                'name' => 'Linda Santos',
                'email' => 'linda@customer.com',
                'phone' => '+63 917 100 2004',
                'province' => 'Bataan',
                'city_municipality' => 'Orani',
                'barangay' => 'Tuyo',
                'house_no_street' => '321 Bonifacio St',
            ],
            [
                'name' => 'Roberto Villanueva',
                'email' => 'roberto@customer.com',
                'phone' => '+63 917 100 2005',
                // Legacy address format
                'address' => '654 National Highway, Bagac, Bataan',
            ],
        ];

        foreach ($customers as $customer) {
            User::create([
                'name' => $customer['name'],
                'email' => $customer['email'],
                'password' => Hash::make('password123'),
                'phone' => $customer['phone'],
                'address' => $customer['address'] ?? null,
                'province' => $customer['province'] ?? null,
                'city_municipality' => $customer['city_municipality'] ?? null,
                'barangay' => $customer['barangay'] ?? null,
                'house_no_street' => $customer['house_no_street'] ?? null,
                'role' => 'customer',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        }
    }
}
