<?php

namespace Database\Seeders;

use App\Models\Technician;
use App\Models\TechnicianAvailability;
use Illuminate\Database\Seeder;

class TechnicianAvailabilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "Setting up technician availability schedules...\n";
        
        // Clear existing availability
        TechnicianAvailability::truncate();
        
        $technicians = Technician::all();
        
        foreach ($technicians as $technician) {
            // Most technicians work Monday-Saturday (1-6), closed Sunday (0)
            $workDays = [1, 2, 3, 4, 5, 6]; // Monday to Saturday
            
            // Some technicians might work different schedules
            if ($technician->id <= 2) {
                // First 2 technicians work full week including Sunday
                $workDays = [0, 1, 2, 3, 4, 5, 6];
            } elseif ($technician->id == 3) {
                // One technician works only weekdays
                $workDays = [1, 2, 3, 4, 5];
            }
            
            foreach ($workDays as $dayOfWeek) {
                // Create availability window matching timeslots (6 AM to 6 PM - covers all 4 timeslots)
                TechnicianAvailability::create([
                    'technician_id' => $technician->id,
                    'day_of_week' => $dayOfWeek,
                    'start_time' => '06:00:00', // Match first timeslot start 
                    'end_time' => '18:00:00',   // Match last timeslot end
                    'is_available' => true,
                ]);
            }
        }
        
        $totalAvailability = TechnicianAvailability::count();
        echo "✅ Created {$totalAvailability} availability schedules for " . $technicians->count() . " technicians!\n";
        
        // Show summary
        echo "\n📅 Availability Summary:\n";
        foreach ($technicians as $technician) {
            $workDays = TechnicianAvailability::where('technician_id', $technician->id)
                ->distinct('day_of_week')
                ->count();
            echo "   • {$technician->user->name}: {$workDays} days per week\n";
        }
        
        echo "\n💡 Day of week mapping: 0=Sunday, 1=Monday, 2=Tuesday, ..., 6=Saturday\n";
    }
}