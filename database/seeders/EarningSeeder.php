<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Earning;
use App\Models\Technician;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EarningSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get ALL bookings that have technicians (pending, in_progress, completed)
        $bookingsWithTechnicians = Booking::whereNotNull('technician_id')
            ->whereIn('status', ['pending', 'confirmed', 'in_progress', 'completed'])
            ->with(['technician.user'])
            ->get();

        if ($bookingsWithTechnicians->isEmpty()) {
            echo "⚠️  No bookings with technicians found. Earnings will be empty.\n";
            echo "💡 Make sure BookingSeeder creates bookings with assigned technicians.\n\n";

            return;
        }

        echo "💰 Creating earnings for {$bookingsWithTechnicians->count()} bookings with technicians...\n";

        $earningsCreated = 0;
        $totalEarningsAmount = 0;

        foreach ($bookingsWithTechnicians as $booking) {
            $technician = $booking->technician;

            if ($technician) {
                // Calculate commission based on technician's commission rate
                $totalAmount = $booking->total_amount;
                $commissionRate = $technician->commission_rate / 100; // Convert percentage to decimal
                $commissionAmount = round($totalAmount * $commissionRate, 2);

                // Add occasional bonuses for high-rated technicians
                $bonusAmount = 0;
                if ($technician->rating_average >= 4.8 && rand(1, 4) == 1) {
                    $bonusAmount = round($commissionAmount * 0.1, 2); // 10% bonus
                }

                $finalAmount = $commissionAmount + $bonusAmount;

                // Create earning record
                Earning::create([
                    'technician_id' => $booking->technician_id,
                    'booking_id' => $booking->id,
                    'base_amount' => $totalAmount,
                    'commission_rate' => $technician->commission_rate,
                    'commission_amount' => $commissionAmount,
                    'bonus_amount' => $bonusAmount,
                    'total_amount' => $finalAmount,
                    'payment_status' => $this->getPaymentStatus($booking),
                    'paid_at' => $this->getPaidAt($booking),
                    'created_at' => $booking->updated_at->addHours(1),
                    'updated_at' => $booking->updated_at->addHours(1),
                ]);

                $earningsCreated++;
                $totalEarningsAmount += $finalAmount;

                $bonusText = $bonusAmount > 0 ? " (+ ₱{$bonusAmount} bonus)" : '';
                echo "  💵 {$technician->user->name}: ₱{$finalAmount}{$bonusText}\n";
            } else {
                echo "  ⚠️  Booking {$booking->booking_number} has no technician assigned\n";
            }
        }

        echo "\n✅ Created {$earningsCreated} earnings records\n";
        echo '💰 Total earnings: ₱'.number_format($totalEarningsAmount, 2)."\n";
        echo '📊 Average earning: ₱'.number_format($earningsCreated > 0 ? $totalEarningsAmount / $earningsCreated : 0, 2)."\n\n";
    }

    private function getPaymentStatus($booking): string
    {
        // Simple logic: commission status follows booking status
        return match($booking->status) {
            'completed' => 'paid',           // Job done = commission paid
            'cancelled' => 'unpaid',         // Job cancelled = no commission
            'pending', 'in_progress', 'confirmed' => 'pending',  // Job not finished = commission pending
            default => 'pending'
        };
    }

    private function getPaidAt($booking): ?string
    {
        $paymentStatus = $this->getPaymentStatus($booking);

        if ($paymentStatus === 'paid') {
            // For completed jobs, payment happens immediately when marked complete
            return $booking->completed_at ?? $booking->updated_at;
        }

        return null;
    }
}
