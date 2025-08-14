<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ServicePricing;
use App\Models\Service;
use App\Models\AirconType;

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

        echo "Creating pricing matrix for {$services->count()} services × {$airconTypes->count()} aircon types...\n";

        // Define pricing multipliers for each aircon type
        $airconTypeMultipliers = [
            'Window Type' => 0.8,      // Cheapest - easier to access
            'Split Type (Wall Mount)' => 1.0,  // Base price
            'Split Type (Floor Ceiling)' => 1.2, // Slightly more expensive
            'Cassette Type' => 1.4,    // More complex installation
            'VRF/VRV System' => 1.8,   // Most expensive - commercial grade
            'Ducted Split' => 1.6,     // Complex ductwork
            'Portable AC' => 0.6,      // Easiest to service
        ];

        $pricingData = [];

        foreach ($services as $service) {
            foreach ($airconTypes as $airconType) {
                $multiplier = $airconTypeMultipliers[$airconType->name] ?? 1.0;
                $basePrice = $service->base_price;
                $adjustedPrice = round($basePrice * $multiplier, 2);

                // Add some realistic notes based on service and aircon type
                $notes = $this->generatePricingNotes($service->name, $airconType->name, $multiplier);

                $pricingData[] = [
                    'service_id' => $service->id,
                    'aircon_type_id' => $airconType->id,
                    'price' => $adjustedPrice,
                    'is_active' => true,
                    'notes' => $notes,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Insert all pricing data
        ServicePricing::insert($pricingData);

        echo "✅ Created " . count($pricingData) . " pricing combinations!\n";
        echo "📊 Price range: ₱" . number_format(min(array_column($pricingData, 'price')), 2) . 
             " - ₱" . number_format(max(array_column($pricingData, 'price')), 2) . "\n\n";

        // Show sample pricing for first service
        $firstService = $services->first();
        echo "💡 Sample pricing for '{$firstService->name}':\n";
        foreach ($airconTypes as $airconType) {
            $pricing = ServicePricing::where('service_id', $firstService->id)
                ->where('aircon_type_id', $airconType->id)
                ->first();
            echo "   • {$airconType->name}: ₱" . number_format((float) $pricing->price, 2) . "\n";
        }
        echo "\n";
    }

    private function generatePricingNotes($serviceName, $airconTypeName, $multiplier): ?string
    {
        $notes = [];

        // Service-specific notes
        if (str_contains(strtolower($serviceName), 'installation')) {
            $notes[] = 'Includes mounting hardware and basic electrical work';
        } elseif (str_contains(strtolower($serviceName), 'cleaning')) {
            $notes[] = 'Deep cleaning with eco-friendly chemicals';
        } elseif (str_contains(strtolower($serviceName), 'repair')) {
            $notes[] = 'Diagnosis and parts replacement if needed';
        }

        // Aircon type-specific notes
        if ($multiplier > 1.5) {
            $notes[] = 'Complex system requiring specialized expertise';
        } elseif ($multiplier > 1.2) {
            $notes[] = 'Requires additional time and equipment';
        } elseif ($multiplier < 0.8) {
            $notes[] = 'Quick and straightforward service';
        }

        if (str_contains($airconTypeName, 'Commercial') || str_contains($airconTypeName, 'VRF')) {
            $notes[] = 'Commercial-grade equipment and procedures';
        }

        return empty($notes) ? null : implode('. ', $notes) . '.';
    }
}
