<?php

namespace Database\Seeders;

use App\Models\Timeslot;
use Illuminate\Database\Seeder;

class TimeslotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $timeslots = [
            [
                'start_time' => '06:00:00',
                'end_time' => '09:00:00',
                'display_time' => '6:00 AM - 9:00 AM',
                'is_active' => true,
            ],
            [
                'start_time' => '09:00:00',
                'end_time' => '12:00:00',
                'display_time' => '9:00 AM - 12:00 PM',
                'is_active' => true,
            ],
            [
                'start_time' => '12:00:00',
                'end_time' => '15:00:00',
                'display_time' => '12:00 PM - 3:00 PM',
                'is_active' => true,
            ],
            [
                'start_time' => '15:00:00',
                'end_time' => '18:00:00',
                'display_time' => '3:00 PM - 6:00 PM',
                'is_active' => true,
            ],
        ];

        foreach ($timeslots as $timeslot) {
            Timeslot::create($timeslot);
        }
    }
}