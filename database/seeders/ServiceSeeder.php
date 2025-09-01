<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            [
                'name' => 'AC Cleaning',
                'description' => 'Complete air conditioning unit cleaning including filters, coils, and drainage.',
                'base_price' => 800.00,
                'duration_minutes' => 90,
                'category' => 'maintenance',
            ],
            [
                'name' => 'AC Repair',
                'description' => 'General AC repair services for common issues and malfunctions.',
                'base_price' => 1500.00,
                'duration_minutes' => 120,
                'category' => 'repair',
            ],
            [
                'name' => 'AC Installation',
                'description' => 'Professional installation of new air conditioning units.',
                'base_price' => 2500.00,
                'duration_minutes' => 180,
                'category' => 'installation',
            ],
            [
                'name' => 'AC Maintenance',
                'description' => 'Regular maintenance service to keep your AC running efficiently.',
                'base_price' => 1000.00,
                'duration_minutes' => 120,
                'category' => 'maintenance',
            ],
            [
                'name' => 'Freon Charging',
                'description' => 'Refrigerant refill and system pressure check.',
                'base_price' => 1200.00,
                'duration_minutes' => 60,
                'category' => 'repair',
            ],
            [
                'name' => 'AC Troubleshooting',
                'description' => 'Diagnostic service to identify AC problems and issues.',
                'base_price' => 600.00,
                'duration_minutes' => 60,
                'category' => 'diagnosis',
            ],
            [
                'name' => 'AC Relocation',
                'description' => 'Safe removal and reinstallation of AC units to new locations.',
                'base_price' => 2000.00,
                'duration_minutes' => 240,
                'category' => 'installation',
            ],
            [
                'name' => 'Repiping Service',
                'description' => 'Replacement of refrigerant pipes and connections.',
                'base_price' => 1800.00,
                'duration_minutes' => 180,
                'category' => 'repair',
            ],
        ];

        foreach ($services as $service) {
            Service::create($service);
        }
    }
}
