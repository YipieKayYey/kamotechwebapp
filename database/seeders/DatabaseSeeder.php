<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        echo "🚀 Starting KamoTech Database Seeding...\n";
        echo "=====================================\n\n";

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
            // EarningSeeder::class, // ❌ Removed - earnings auto-created by Booking model!
        ]);

        // Update technician job counts after all bookings are created
        $this->updateTechnicianStats();

        echo "\n🎉 KamoTech Database Seeding Complete!\n";
        echo "=====================================\n";
        $this->showFinalStats();
    }

    private function updateTechnicianStats(): void
    {
        echo "📊 Updating technician statistics...\n";

        $technicians = \App\Models\Technician::with('user')->get();

        foreach ($technicians as $technician) {
            // Count total jobs assigned to this technician
            $totalJobs = \App\Models\Booking::where('technician_id', $technician->id)->count();

            // Count current jobs (pending or in_progress)
            $currentJobs = \App\Models\Booking::where('technician_id', $technician->id)
                ->whereIn('status', ['pending', 'confirmed', 'in_progress'])
                ->count();

            // Calculate average rating from reviews
            $averageRating = \App\Models\RatingReview::where('technician_id', $technician->id)
                ->avg('overall_rating');

            // Update technician stats
            $technician->update([
                'total_jobs' => $totalJobs,
                'current_jobs' => $currentJobs,
                'rating_average' => $averageRating ? round($averageRating, 2) : $technician->rating_average,
            ]);

            echo "  ✅ {$technician->user->name}: {$totalJobs} total jobs, {$currentJobs} current jobs, {$technician->rating_average} avg rating\n";
        }

        echo "\n";
    }

    private function showFinalStats(): void
    {
        $userCount = \App\Models\User::count();
        $technicianCount = \App\Models\Technician::count();
        $bookingCount = \App\Models\Booking::count();
        $reviewCount = \App\Models\RatingReview::count();
        $earningCount = \App\Models\Earning::count();

        echo "📈 Final Statistics:\n";
        echo "  👥 Users: {$userCount}\n";
        echo "  🔧 Technicians: {$technicianCount}\n";
        echo "  📅 Bookings: {$bookingCount}\n";
        echo "  ⭐ Reviews: {$reviewCount}\n";
        echo "  💰 Earnings: {$earningCount}\n\n";

        echo "🧪 Ready for algorithm testing!\n";
        echo "💡 Try creating new bookings to test the greedy algorithm.\n\n";
    }
}
