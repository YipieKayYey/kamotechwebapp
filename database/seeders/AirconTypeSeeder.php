<?php

namespace Database\Seeders;

use App\Models\AirconType;
use Illuminate\Database\Seeder;

class AirconTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $airconTypes = [
            [
                'name' => 'Window Type',
                'description' => 'Self-contained unit installed in a window or wall opening. Most common for small rooms.',
                'is_active' => true,
            ],
            [
                'name' => 'Split Type',
                'description' => 'Separate indoor and outdoor units connected by refrigerant lines. Popular for homes and offices.',
                'is_active' => true,
            ],
            [
                'name' => 'Floor Standing',
                'description' => 'Floor-mounted unit positioned near floor level. Ideal for spaces with limited wall mounting options.',
                'is_active' => true,
            ],
        ];

        foreach ($airconTypes as $type) {
            AirconType::create($type);
        }
    }
}