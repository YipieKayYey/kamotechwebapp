<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            ServiceSeeder::class,

            TechnicianSeeder::class,
            TechnicianAvailabilitySeeder::class, // Set work schedules after creating technicians
            TimeslotSeeder::class,
            AirconTypeSeeder::class,
            ServicePricingSeeder::class,  // Add before BookingSeeder so pricing is available
            BookingSeeder::class,
            HybridBookingSeeder::class,   // Add sample hybrid bookings
            ReviewCategorySeeder::class,  // Create review categories first
            RatingReviewSeeder::class,
            EarningSeeder::class,
        ]);
    }
}
