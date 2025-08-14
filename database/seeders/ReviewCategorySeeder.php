<?php

namespace Database\Seeders;

use App\Models\ReviewCategory;
use Illuminate\Database\Seeder;

class ReviewCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Work Quality',
                'description' => 'How good was the repair/service?',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Punctuality',
                'description' => 'Did they arrive on time?',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Cleanliness',
                'description' => 'Did they keep the area clean?',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Attitude',
                'description' => 'Were they professional & friendly?',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Tools',
                'description' => 'Did they use proper equipment?',
                'is_active' => true,
                'sort_order' => 5,
            ],
        ];

        foreach ($categories as $category) {
            ReviewCategory::create($category);
        }
        
        echo "✅ Created " . count($categories) . " review categories\n";
    }
}