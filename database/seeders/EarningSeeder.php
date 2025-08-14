<?php

namespace Database\Seeders;

use App\Models\Earning;
use App\Models\Booking;
use App\Models\Technician;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class EarningSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all completed bookings with technician data
        $completedBookings = Booking::where('status', 'completed')
            ->where('payment_status', 'paid')
            ->with(['technician'])
            ->get();

        foreach ($completedBookings as $booking) {
            $technician = $booking->technician;
            
            if ($technician) {
                // Calculate commission based on technician's commission rate
                $totalAmount = $booking->total_amount;
                $commissionRate = $technician->commission_rate / 100; // Convert percentage to decimal
                $commissionAmount = $totalAmount * $commissionRate;
                
                // Create earning record
                Earning::create([
                    'technician_id' => $booking->technician_id,
                    'booking_id' => $booking->id,
                    'base_amount' => $totalAmount,
                    'commission_rate' => $technician->commission_rate,
                    'commission_amount' => $commissionAmount,
                    'bonus_amount' => 0, // No bonus for now
                    'total_amount' => $commissionAmount, // Total earning is the commission
                    'payment_status' => $this->getPaymentStatus($booking),
                    'paid_at' => $this->getPaidAt($booking),
                    'created_at' => $booking->updated_at->addHours(1),
                    'updated_at' => $booking->updated_at->addHours(1),
                ]);
            }
        }
    }

    private function getPaymentStatus($booking): string
    {
        // Most earnings are paid within a week of job completion
        $daysSinceCompletion = Carbon::now()->diffInDays($booking->updated_at);
        
        if ($daysSinceCompletion > 7) {
            return 'paid';
        } elseif ($daysSinceCompletion > 3) {
            return rand(0, 1) ? 'paid' : 'pending';
        } else {
            return 'pending';
        }
    }

    private function getPaidAt($booking): ?string
    {
        $paymentStatus = $this->getPaymentStatus($booking);
        
        if ($paymentStatus === 'paid') {
            // Payment typically happens 3-10 days after job completion
            return $booking->updated_at
                ->addDays(rand(3, 10))
                ->format('Y-m-d H:i:s');
        }
        
        return null;
    }
}
