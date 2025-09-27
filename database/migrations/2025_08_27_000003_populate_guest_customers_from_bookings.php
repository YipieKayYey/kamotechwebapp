<?php

use App\Models\Booking;
use App\Models\GuestCustomer;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get admin user for created_by field
        $adminUser = User::where('role', 'admin')->first();
        if (! $adminUser) {
            return; // Skip if no admin exists
        }

        // Find all bookings with customer_name but no customer_id (guest bookings)
        $guestBookings = Booking::whereNull('customer_id')
            ->whereNotNull('customer_name')
            ->get();

        $processedPhones = [];

        foreach ($guestBookings as $booking) {
            // Skip if no phone number
            if (empty($booking->customer_mobile)) {
                continue;
            }

            // Check if we already created a guest customer with this phone
            if (isset($processedPhones[$booking->customer_mobile])) {
                // Link to existing guest customer
                $booking->update(['guest_customer_id' => $processedPhones[$booking->customer_mobile]]);

                // Update booking count
                $guestCustomer = GuestCustomer::find($processedPhones[$booking->customer_mobile]);
                if ($guestCustomer) {
                    $guestCustomer->increment('total_bookings');
                    $guestCustomer->update(['last_booking_date' => $booking->created_at]);
                }

                continue;
            }

            // Parse name (simple split by space)
            $nameParts = explode(' ', trim($booking->customer_name));
            $firstName = $nameParts[0] ?? 'Guest';
            $lastName = isset($nameParts[1]) ? implode(' ', array_slice($nameParts, 1)) : 'Customer';

            // Create new guest customer
            $guestCustomer = GuestCustomer::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $booking->customer_mobile,
                'house_no_street' => $booking->house_no_street,
                'barangay' => $booking->barangay,
                'city_municipality' => $booking->city_municipality,
                'province' => $booking->province,
                'nearest_landmark' => $booking->nearest_landmark,
                'total_bookings' => 1,
                'last_booking_date' => $booking->created_at,
                'created_by' => $adminUser->id,
            ]);

            // Link booking to guest customer
            $booking->update(['guest_customer_id' => $guestCustomer->id]);

            // Track this phone number
            $processedPhones[$booking->customer_mobile] = $guestCustomer->id;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove guest_customer_id from bookings
        Booking::whereNotNull('guest_customer_id')->update(['guest_customer_id' => null]);

        // Delete all guest customers
        GuestCustomer::truncate();
    }
};
