<?php

namespace Database\Seeders;

use App\Models\Technician;
use App\Models\User;
use Illuminate\Database\Seeder;

class TechnicianSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get technician users
        $technicianUsers = User::where('role', 'technician')->get();

        if ($technicianUsers->isEmpty()) {
            throw new \Exception('No technician users found. Make sure UserSeeder runs first.');
        }

        echo "Creating technician profiles for {$technicianUsers->count()} users...\n";

        $technicianData = [
            [
                'email' => 'pedro@kamotech.com',
                'employee_id' => 'KMT-001',
                'hire_date' => '2023-01-15',
                'commission_rate' => 15.00,
                'base_rating' => 4.8,
                'max_daily_jobs' => 5,
                'specialization' => 'Cleaning & Maintenance', // Pedro's specialty
            ],
            [
                'email' => 'maria@kamotech.com',
                'employee_id' => 'KMT-002',
                'hire_date' => '2023-03-20',
                'commission_rate' => 18.00,
                'base_rating' => 4.9,
                'max_daily_jobs' => 6,
                'specialization' => 'Installation & Setup', // Maria's specialty
            ],
            [
                'email' => 'jose@kamotech.com',
                'employee_id' => 'KMT-003',
                'hire_date' => '2023-06-10',
                'commission_rate' => 12.00,
                'base_rating' => 4.6,
                'max_daily_jobs' => 4,
                'specialization' => 'Basic Repair', // Jose's specialty
            ],
            [
                'email' => 'ana@kamotech.com',
                'employee_id' => 'KMT-004',
                'hire_date' => '2023-09-05',
                'commission_rate' => 20.00,
                'base_rating' => 4.95,
                'max_daily_jobs' => 7,
                'specialization' => 'All Services Expert', // Ana is good at everything
            ],
            [
                'email' => 'carlos@kamotech.com',
                'employee_id' => 'KMT-005',
                'hire_date' => '2024-02-12',
                'commission_rate' => 16.00,
                'base_rating' => 4.7,
                'max_daily_jobs' => 5,
                'specialization' => 'Troubleshooting', // Carlos's specialty
            ],
        ];

        foreach ($technicianData as $data) {
            $user = $technicianUsers->where('email', $data['email'])->first();

            if ($user) {
                Technician::create([
                    'user_id' => $user->id,
                    'employee_id' => $data['employee_id'],
                    'hire_date' => $data['hire_date'],
                    'commission_rate' => $data['commission_rate'],
                    'is_available' => true,
                    'rating_average' => $data['base_rating'], // Will be updated after reviews
                    'total_jobs' => 0, // Will be calculated after bookings
                    'current_jobs' => 0, // Will be calculated after bookings
                    'max_daily_jobs' => $data['max_daily_jobs'],
                ]);

                echo "✅ Created technician: {$user->name} ({$data['specialization']})\n";
            } else {
                echo "⚠️  Technician user not found: {$data['email']}\n";
            }
        }

        echo "\n🎯 Technician profiles created with specializations for algorithm testing!\n\n";
    }
}
