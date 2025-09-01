<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $bataan_cities = [
            'Balanga City', 'Bagac', 'Dinalupihan', 'Hermosa', 'Limay',
            'Mariveles', 'Morong', 'Orani', 'Orion', 'Pilar', 'Samal', 'Abucay',
        ];

        $barangays = [
            // Balanga City barangays
            'Bagong Silang', 'Bagumbayan', 'Cabog-Cabog', 'Central', 'Cupang North',
            'Cupang Proper', 'Cupang West', 'Dangcol', 'Doña Francisca', 'Ibayo',
            'Malainin', 'Poblacion', 'San Jose', 'Sibacan', 'Talisay', 'Tortugas',
            // Common barangay names in other towns
            'Bayan', 'Centro', 'Kaakbayan', 'Lucanin', 'Pag-asa', 'San Antonio',
            'San Isidro', 'San Juan', 'San Miguel', 'San Nicolas', 'San Pedro',
            'San Rafael', 'Santa Cruz', 'Santa Rita', 'Santo Niño', 'Villa Angeles',
        ];

        $streets = [
            'Don Manuel Banzon Ave', 'Capitol Drive', 'Jose P. Laurel Highway',
            'MacArthur Highway', 'Roman Highway', 'Teotimo Street', 'Garcia Street',
            'Roxas Street', 'Rizal Street', 'Quezon Avenue', 'Bonifacio Street',
            'Luna Street', 'Mabini Street', 'Del Pilar Street', 'Aguinaldo Avenue',
            'National Road', 'Provincial Road', 'Barangay Road',
        ];

        $city = fake()->randomElement($bataan_cities);
        $useStructuredAddress = fake()->boolean(80); // 80% chance of having structured address

        $structured = $useStructuredAddress ? [
            'province' => 'Bataan',
            'city_municipality' => $city,
            'barangay' => fake()->randomElement($barangays),
            'house_no_street' => fake()->buildingNumber().' '.fake()->randomElement($streets),
        ] : [
            'province' => null,
            'city_municipality' => null,
            'barangay' => null,
            'house_no_street' => null,
        ];

        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'phone' => fake()->mobileNumber(), // Use mobileNumber for Philippine format
            'address' => $useStructuredAddress ? null : fake()->address(), // Legacy address for some users
            'province' => $structured['province'],
            'city_municipality' => $structured['city_municipality'],
            'barangay' => $structured['barangay'],
            'house_no_street' => $structured['house_no_street'],
            'role' => fake()->randomElement(['customer', 'technician']),
            'is_active' => fake()->boolean(90), // 90% chance of being active
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create a customer user.
     */
    public function customer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'customer',
        ]);
    }

    /**
     * Create a technician user.
     */
    public function technician(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'technician',
        ]);
    }

    /**
     * Create an admin user.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }
}
