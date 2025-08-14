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

        $technicianData = [
            [
                'email' => 'pedro@kamotech.com',
                'employee_id' => 'KMT-001',
                'hire_date' => '2023-01-15',
                'commission_rate' => 15.00,
                'rating_average' => 4.8,
                'total_jobs' => 124,
                'current_jobs' => 2,
                'max_daily_jobs' => 5,
            ],
            [
                'email' => 'maria@kamotech.com',
                'employee_id' => 'KMT-002',
                'hire_date' => '2023-03-20',
                'commission_rate' => 18.00,
                'rating_average' => 4.9,
                'total_jobs' => 156,
                'current_jobs' => 1,
                'max_daily_jobs' => 6,
            ],
            [
                'email' => 'jose@kamotech.com',
                'employee_id' => 'KMT-003',
                'hire_date' => '2023-06-10',
                'commission_rate' => 12.00,
                'rating_average' => 4.6,
                'total_jobs' => 89,
                'current_jobs' => 0,
                'max_daily_jobs' => 4,
            ],
            [
                'email' => 'ana@kamotech.com',
                'employee_id' => 'KMT-004',
                'hire_date' => '2023-09-05',
                'commission_rate' => 20.00,
                'rating_average' => 4.95,
                'total_jobs' => 203,
                'current_jobs' => 3,
                'max_daily_jobs' => 7,
            ],
            [
                'email' => 'carlos@kamotech.com',
                'employee_id' => 'KMT-005',
                'hire_date' => '2024-02-12',
                'commission_rate' => 16.00,
                'rating_average' => 4.7,
                'total_jobs' => 67,
                'current_jobs' => 1,
                'max_daily_jobs' => 5,
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
                    'rating_average' => $data['rating_average'],
                    'total_jobs' => $data['total_jobs'],
                    'current_jobs' => $data['current_jobs'],
                    'max_daily_jobs' => $data['max_daily_jobs'],
                ]);
            }
        }
    }
}
