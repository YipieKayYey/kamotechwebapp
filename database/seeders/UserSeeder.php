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
            'name' => 'Kamotech Admin',
            'email' => 'admin@kamotech.com',
            'password' => Hash::make('password123'),
            'phone' => '+63 917 123 4567',
            'address' => 'Balanga City, Bataan',
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Manager Juan',
            'email' => 'manager@kamotech.com',
            'password' => Hash::make('password123'),
            'phone' => '+63 917 123 4568',
            'address' => 'Mariveles, Bataan',
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Create Technician Users (5 total)
        $technicians = [
            [
                'name' => 'Pedro Santos',
                'email' => 'pedro@kamotech.com',
                'phone' => '+63 917 111 2222',
                'address' => 'Hermosa, Bataan',
            ],
            [
                'name' => 'Maria Garcia',
                'email' => 'maria@kamotech.com',
                'phone' => '+63 917 333 4444',
                'address' => 'Orani, Bataan',
            ],
            [
                'name' => 'Jose Cruz',
                'email' => 'jose@kamotech.com',
                'phone' => '+63 917 555 6666',
                'address' => 'Balanga City, Bataan',
            ],
            [
                'name' => 'Ana Reyes',
                'email' => 'ana@kamotech.com',
                'phone' => '+63 917 777 8888',
                'address' => 'Bagac, Bataan',
            ],
            [
                'name' => 'Carlos Mendoza',
                'email' => 'carlos@kamotech.com',
                'phone' => '+63 917 999 0000',
                'address' => 'Dinalupihan, Bataan',
            ],
        ];

        foreach ($technicians as $tech) {
            User::create([
                'name' => $tech['name'],
                'email' => $tech['email'],
                'password' => Hash::make('password123'),
                'phone' => $tech['phone'],
                'address' => $tech['address'],
                'role' => 'technician',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        }

        // Create Customer Users
        $customers = [
            [
                'name' => 'John Dela Cruz',
                'email' => 'john@customer.com',
                'phone' => '+63 917 100 2001',
                'address' => '123 Rizal Street, Balanga City, Bataan',
            ],
            [
                'name' => 'Sarah Gonzales',
                'email' => 'sarah@customer.com',
                'phone' => '+63 917 100 2002',
                'address' => '456 Magsaysay Ave, Mariveles, Bataan',
            ],
            [
                'name' => 'Michael Torres',
                'email' => 'michael@customer.com',
                'phone' => '+63 917 100 2003',
                'address' => '789 Del Pilar Road, Hermosa, Bataan',
            ],
            [
                'name' => 'Linda Santos',
                'email' => 'linda@customer.com',
                'phone' => '+63 917 100 2004',
                'address' => '321 Bonifacio St, Orani, Bataan',
            ],
            [
                'name' => 'Roberto Villanueva',
                'email' => 'roberto@customer.com',
                'phone' => '+63 917 100 2005',
                'address' => '654 National Highway, Bagac, Bataan',
            ],
        ];

        foreach ($customers as $customer) {
            User::create([
                'name' => $customer['name'],
                'email' => $customer['email'],
                'password' => Hash::make('password123'),
                'phone' => $customer['phone'],
                'address' => $customer['address'],
                'role' => 'customer',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        }
    }
}
