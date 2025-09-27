<?php

namespace Database\Seeders;

use App\Models\Promotion;
use Illuminate\Database\Seeder;

class PromotionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $promotions = [
            [
                'welcome_text' => 'Kamotech Aircon Services',
                'title' => 'PRICE STARTS AT 450 PESOS!',
                'subtitle' => 'Find the affordable, Find your satisfaction!',
                'background_image' => '/images/slide/1.jpg',
                'primary_button_text' => 'BOOK NOW',
                'primary_button_link' => '/booking',
                'secondary_button_text' => 'SIGN UP',
                'secondary_button_link' => '/register',
                'discount_type' => null,
                'discount_value' => null,
                'display_order' => 1,
                'is_active' => true,
                'show_on_slider' => true,
                'start_date' => now(),
                'end_date' => now()->addMonths(6),
            ],
            [
                'welcome_text' => 'Reliable Aircon Services Anytime, Anywhere',
                'title' => 'FREE SURVEY & FREE CHECKUP!',
                'subtitle' => 'Cleaning • Repair • Freon Charging • Installation • Relocation & More',
                'background_image' => '/images/slide/2.jpg',
                'primary_button_text' => 'GET QUOTE',
                'primary_button_link' => '/booking',
                'secondary_button_text' => 'LEARN MORE',
                'secondary_button_link' => '#services',
                'discount_type' => 'free_service',
                'discount_value' => 0,
                'promo_code' => 'FREECHECK',
                'display_order' => 2,
                'is_active' => true,
                'show_on_slider' => true,
                'start_date' => now(),
                'end_date' => now()->addMonths(3),
            ],
            [
                'welcome_text' => 'Celebrate the Start of the Ber Months',
                'title' => 'BER MONTHS SPECIAL 15% off on All Services',
                'subtitle' => 'Aircon Cleaning Starts at ₱450 • Free Survey & Checkup',
                'background_image' => '/images/slide/3.jpg',
                'primary_button_text' => 'VIEW SERVICES',
                'primary_button_link' => '#services',
                'secondary_button_text' => 'CONTACT US',
                'secondary_button_link' => '#contact',
                'discount_type' => 'percentage',
                'discount_value' => 15,
                'promo_code' => 'BER2025',
                'display_order' => 3,
                'is_active' => true,
                'show_on_slider' => true,
                'start_date' => now(),
                'end_date' => now()->addDays(60),
            ],
            [
                'welcome_text' => 'Customers Get More Savings',
                'title' => 'WEBSITE LAUNCH PROMO 10% 0ff on All Services',
                'subtitle' => 'Book today and experience professional, affordable aircon care.',
                'background_image' => '/images/slide/4.jpg',
                'primary_button_text' => 'SCHEDULE NOW',
                'primary_button_link' => '/booking',
                'secondary_button_text' => 'LEARN MORE',
                'secondary_button_link' => '/contact',
                'discount_type' => 'percentage',
                'discount_value' => 10,
                'promo_code' => 'NEW10',
                'display_order' => 4,
                'is_active' => true,
                'show_on_slider' => true,
                'start_date' => now(),
                'end_date' => now()->addYear(),
            ],
        ];

        foreach ($promotions as $promotion) {
            Promotion::create($promotion);
        }

        echo '✅ Created '.count($promotions)." promotions for hero slider!\n";
    }
}
