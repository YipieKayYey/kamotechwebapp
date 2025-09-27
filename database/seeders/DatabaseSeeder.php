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
            LocationSeeder::class,
            BataanBarangaySeeder::class,
            UserSeeder::class,
            ServiceSeeder::class,
            PromotionSeeder::class,
            TechnicianSeeder::class,
            // TechnicianAvailabilitySeeder removed - using simple is_available toggle
            AirconTypeSeeder::class,
            ServicePricingSeeder::class,  // Add before BookingSeeder so pricing is available
            GuestCustomerSeeder::class,   // Add guest customers before bookings
            BookingSeeder::class,
            GuestCustomerBookingSeeder::class, // Add guest customer bookings
            ReviewCategorySeeder::class,  // Create review categories first
            RatingReviewSeeder::class,
            // EarningSeeder::class, // ❌ Removed - earnings auto-created by Booking model!
        ]);

        // Ensure all technicians have the standardized 10% commission after seeding
        \App\Models\Technician::query()->update(['commission_rate' => 10.00]);

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
        $guestCustomerCount = \App\Models\GuestCustomer::count();
        $guestBookingCount = \App\Models\Booking::whereNotNull('guest_customer_id')->count();

        echo "📈 Final Statistics:\n";
        echo "  👥 Users: {$userCount}\n";
        echo "  🏃 Guest Customers: {$guestCustomerCount}\n";
        echo "  🔧 Technicians: {$technicianCount}\n";
        echo "  📅 Total Bookings: {$bookingCount}\n";
        echo "  └─ Guest Bookings: {$guestBookingCount}\n";
        echo "  ⭐ Reviews: {$reviewCount}\n";
        echo "  💰 Earnings: {$earningCount}\n\n";

        echo "🧪 Ready for algorithm testing!\n";
        echo "💡 Try creating new bookings to test the greedy algorithm.\n";
        echo "✨ Guest customer system is ready for use!\n\n";
    }
}
