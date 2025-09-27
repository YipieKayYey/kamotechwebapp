<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use Illuminate\Support\Collection;

class PromotionController extends Controller
{
    /**
     * Get active promotions for the hero slider
     */
    public function getSliderPromotions(): Collection
    {
        $promotions = Promotion::forSlider()->get();

        return $promotions->map(function ($promotion) {
            return [
                'id' => $promotion->id,
                'welcome_text' => $promotion->welcome_text,
                'title' => $promotion->title,
                'subtitle' => $promotion->subtitle,
                'primary_button_text' => $promotion->primary_button_text,
                'primary_button_link' => $promotion->primary_button_link,
                'secondary_button_text' => $promotion->secondary_button_text,
                'secondary_button_link' => $promotion->secondary_button_link,
                'background_image' => $promotion->background_image_url,
                'discount' => $promotion->formatted_discount,
                'promo_code' => $promotion->promo_code,
            ];
        });
    }

    /**
     * Get fallback slides using all 4 public slide images
     */
    public function getFallbackSlides(): Collection
    {
        $fallbackSlides = collect([
            [
                'id' => 1,
                'welcome_text' => 'Kamotech Aircon Services',
                'title' => 'PRICE STARTS AT 450 PESOS!',
                'subtitle' => 'Find the affordable, Find your satisfaction!',
                'primary_button_text' => 'BOOK NOW',
                'primary_button_link' => '/booking',
                'secondary_button_text' => 'SIGN UP',
                'secondary_button_link' => '/register',
                'background_image' => '/images/slide/1.jpg',
                'discount' => null,
                'promo_code' => null,
            ],
            [
                'id' => 2,
                'welcome_text' => 'Professional AC Services',
                'title' => 'FREE SURVEY & FREE CHECKUP!',
                'subtitle' => 'Cleaning • Repair • Freon Charging • Installation • Relocation & More',
                'primary_button_text' => 'GET QUOTE',
                'primary_button_link' => '/booking',
                'secondary_button_text' => 'LEARN MORE',
                'secondary_button_link' => '#services',
                'background_image' => '/images/slide/2.jpg',
                'discount' => 'FREE SERVICE',
                'promo_code' => 'FREECHECK',
            ],
            [
                'id' => 3,
                'welcome_text' => 'Quality & Reliability',
                'title' => 'EXPERT TECHNICIANS',
                'subtitle' => 'Licensed professionals with years of experience in AC maintenance and repair',
                'primary_button_text' => 'BOOK SERVICE',
                'primary_button_link' => '/booking',
                'secondary_button_text' => 'VIEW SERVICES',
                'secondary_button_link' => '/services',
                'background_image' => '/images/slide/3.jpg',
                'discount' => null,
                'promo_code' => null,
            ],
            [
                'id' => 4,
                'welcome_text' => 'Fast & Efficient',
                'title' => '24/7 EMERGENCY SERVICE',
                'subtitle' => 'Quick response time for urgent AC repairs and maintenance needs',
                'primary_button_text' => 'EMERGENCY CALL',
                'primary_button_link' => '/booking?urgent=1',
                'secondary_button_text' => 'CONTACT US',
                'secondary_button_link' => '/contact',
                'background_image' => '/images/slide/4.jpg',
                'discount' => null,
                'promo_code' => null,
            ],
        ]);

        return $fallbackSlides;
    }

    /**
     * Get slides for display (database promotions or fallbacks)
     */
    public function getSlidesForDisplay(): Collection
    {
        $promotions = $this->getSliderPromotions();

        // If we have active promotions, use them, otherwise use fallbacks
        return $promotions->isNotEmpty() ? $promotions : $this->getFallbackSlides();
    }

    /**
     * Create sample promotions using the public slide images
     */
    public function createSamplePromotions(): void
    {
        $samplePromotions = [
            [
                'welcome_text' => 'Kamotech Aircon Services',
                'title' => 'PRICE STARTS AT 450 PESOS!',
                'subtitle' => 'Find the affordable, Find your satisfaction!',
                'background_image' => '/images/slide/1.jpg',
                'primary_button_text' => 'BOOK NOW',
                'primary_button_link' => '/booking',
                'secondary_button_text' => 'SIGN UP',
                'secondary_button_link' => '/register',
                'display_order' => 1,
                'is_active' => true,
                'show_on_slider' => true,
                'start_date' => now(),
                'end_date' => now()->addMonths(6),
            ],
            [
                'welcome_text' => 'Professional AC Services',
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
                'welcome_text' => 'Quality & Reliability',
                'title' => 'EXPERT TECHNICIANS',
                'subtitle' => 'Licensed professionals with years of experience in AC maintenance and repair',
                'background_image' => '/images/slide/3.jpg',
                'primary_button_text' => 'BOOK SERVICE',
                'primary_button_link' => '/booking',
                'secondary_button_text' => 'VIEW SERVICES',
                'secondary_button_link' => '/services',
                'display_order' => 3,
                'is_active' => true,
                'show_on_slider' => true,
                'start_date' => now(),
                'end_date' => now()->addMonths(12),
            ],
            [
                'welcome_text' => 'Fast & Efficient',
                'title' => '24/7 EMERGENCY SERVICE',
                'subtitle' => 'Quick response time for urgent AC repairs and maintenance needs',
                'background_image' => '/images/slide/4.jpg',
                'primary_button_text' => 'EMERGENCY CALL',
                'primary_button_link' => '/booking?urgent=1',
                'secondary_button_text' => 'CONTACT US',
                'secondary_button_link' => '/contact',
                'display_order' => 4,
                'is_active' => true,
                'show_on_slider' => true,
                'start_date' => now(),
                'end_date' => now()->addMonths(12),
            ],
        ];

        foreach ($samplePromotions as $promotion) {
            Promotion::updateOrCreate(
                ['title' => $promotion['title']],
                $promotion
            );
        }
    }

    /**
     * Get promo code discount for booking
     */
    public function validatePromoCode(string $promoCode): ?array
    {
        $promotion = Promotion::where('promo_code', $promoCode)
            ->active()
            ->first();

        if (! $promotion) {
            return null;
        }

        return [
            'id' => $promotion->id,
            'title' => $promotion->title,
            'discount_type' => $promotion->discount_type,
            'discount_value' => $promotion->discount_value,
            'promo_code' => $promotion->promo_code,
            'formatted_discount' => $promotion->formatted_discount,
            'applicable_services' => $promotion->applicable_services,
            'applicable_aircon_types' => $promotion->applicable_aircon_types,
        ];
    }
}
