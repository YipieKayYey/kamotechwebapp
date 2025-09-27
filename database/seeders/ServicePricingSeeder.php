<?php

namespace Database\Seeders;

use App\Models\AirconType;
use App\Models\Service;
use App\Models\ServicePricing;
use Illuminate\Database\Seeder;

class ServicePricingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = Service::all();
        $airconTypes = AirconType::all();

        if ($services->isEmpty() || $airconTypes->isEmpty()) {
            throw new \Exception('Services and AirconTypes must be seeded first.');
        }

        // Clear existing pricing data to avoid duplicates
        echo "Clearing existing service pricing data...\n";
        ServicePricing::truncate();

        echo "Creating updated pricing matrix for {$services->count()} services × {$airconTypes->count()} aircon types...\n";

        // Define exact pricing for each service-aircon type combination
        $exactPricing = [
            // AC Cleaning
            'AC Cleaning' => [
                'Window Type' => 450.00,
                'Split Type' => 1500.00,
                'Floor Standing' => 2000.00,
            ],
            // AC Installation
            'AC Installation' => [
                'Window Type' => 2500.00,
                'Split Type' => 6500.00,
                'Floor Standing' => 8000.00,
            ],
            // AC Repiping
            'AC Repiping' => [
                'Window Type' => 2500.00,
                'Split Type' => 4500.00,
                'Floor Standing' => 5000.00,
            ],
            // AC Relocation
            'AC Relocation' => [
                'Window Type' => 2500.00,
                'Split Type' => 5500.00,
                'Floor Standing' => 7500.00,
            ],
            // Freon Charging
            'Freon Charging' => [
                'Window Type' => 3500.00,
                'Split Type' => 3500.00,
                'Floor Standing' => 3500.00,
            ],
            // AC Repair
            'AC Repair' => [
                'Window Type' => 2500.00,
                'Split Type' => 3000.00,
                'Floor Standing' => 3500.00,
            ],
            // AC Troubleshooting
            'AC Troubleshooting' => [
                'Window Type' => 350.00,
                'Split Type' => 350.00,
                'Floor Standing' => 350.00,
            ],
        ];

        $pricingData = [];

        foreach ($services as $service) {
            foreach ($airconTypes as $airconType) {
                // Get exact price from our pricing matrix
                $price = $exactPricing[$service->name][$airconType->name] ?? $service->base_price;

                // Generate appropriate notes for this service-aircon type combination
                $notes = $this->generatePricingNotes($service->name, $airconType->name, $price);

                $pricingData[] = [
                    'service_id' => $service->id,
                    'aircon_type_id' => $airconType->id,
                    'price' => $price,
                    'is_active' => true,
                    'notes' => $notes,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Insert all pricing data
        ServicePricing::insert($pricingData);

        echo '✅ Created '.count($pricingData)." pricing combinations!\n";
        echo '📊 Price range: ₱'.number_format(min(array_column($pricingData, 'price')), 2).
             ' - ₱'.number_format(max(array_column($pricingData, 'price')), 2)."\n\n";

        // Show complete pricing matrix
        echo "💡 Complete Pricing Matrix:\n";
        echo '='.str_repeat('=', 65)."\n";
        foreach ($services as $service) {
            echo "🔧 {$service->name}:\n";
            foreach ($airconTypes as $airconType) {
                $pricing = collect($pricingData)
                    ->where('service_id', $service->id)
                    ->where('aircon_type_id', $airconType->id)
                    ->first();
                echo "   • {$airconType->name}: ₱".number_format($pricing['price'], 2)."\n";
            }
            echo "\n";
        }
    }

    private function generatePricingNotes($serviceName, $airconTypeName, $price): ?string
    {
        $notes = [];

        // Service-specific notes
        if (str_contains(strtolower($serviceName), 'installation')) {
            $notes[] = 'Includes mounting hardware and basic electrical work';
            if ($airconTypeName === 'Split Type') {
                $notes[] = 'Complex installation with indoor and outdoor units';
            } elseif ($airconTypeName === 'Floor Standing') {
                $notes[] = 'Premium floor-standing installation with advanced setup';
            }
        } elseif (str_contains(strtolower($serviceName), 'cleaning')) {
            $notes[] = 'Deep cleaning with eco-friendly chemicals';
            if ($airconTypeName === 'Floor Standing') {
                $notes[] = 'Comprehensive cleaning of floor unit components';
            } elseif ($airconTypeName === 'Split Type') {
                $notes[] = 'Indoor and outdoor unit cleaning';
            }
        } elseif (str_contains(strtolower($serviceName), 'repair')) {
            $notes[] = 'Diagnosis and parts replacement if needed';
        } elseif (str_contains(strtolower($serviceName), 'relocation')) {
            $notes[] = 'Safe removal and reinstallation to new location';
            if ($airconTypeName === 'Floor Standing') {
                $notes[] = 'Premium relocation service for floor units';
            }
        } elseif (str_contains(strtolower($serviceName), 'freon')) {
            $notes[] = 'Professional refrigerant refill with system pressure check';
            $notes[] = 'Consistent pricing across all AC types';
        } elseif (str_contains(strtolower($serviceName), 'repiping')) {
            $notes[] = 'Quality pipe replacement with leak testing';
        } elseif (str_contains(strtolower($serviceName), 'troubleshooting')) {
            $notes[] = 'Comprehensive diagnostic service';
            $notes[] = 'Flat rate for all AC types';
        }

        // Aircon type-specific pricing notes
        if ($price >= 5000) {
            $notes[] = 'Premium service pricing for complex systems';
        } elseif ($price >= 2000) {
            $notes[] = 'Standard professional service rate';
        } elseif ($price <= 500) {
            $notes[] = 'Affordable basic service pricing';
        }

        // Special pricing notes
        if ($airconTypeName === 'Floor Standing' && $price >= 7000) {
            $notes[] = 'Specialized equipment and expertise required';
        }

        return empty($notes) ? null : implode('. ', $notes).'.';
    }
}
