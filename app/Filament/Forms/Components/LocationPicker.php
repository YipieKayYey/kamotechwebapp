<?php

namespace App\Filament\Forms\Components;

use App\Services\FreeMapsService;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Concerns\HasPlaceholder;

/**
 * FREE Maps Location Picker for Filament Forms
 * 
 * Features:
 * - Interactive MapTiler maps with click-to-select location
 * - Address autocomplete using OpenCage API
 * - Automatic geocoding (address ↔ coordinates)
 * - Real-time coordinate display
 * - Philippines-focused with customizable region
 * - 100% FREE - No billing verification required!
 * 
 * Usage:
 * LocationPicker::make('location')
 *     ->label('Service Address')
 *     ->placeholder('Click on map or search for address...')
 *     ->defaultLatLng(14.6760, 120.5348) // Balanga City default
 *     ->zoom(12)
 *     ->height('400px')
 *     ->reactive()
 */
class LocationPicker extends Field
{
    use HasPlaceholder;

    protected string $view = 'filament.forms.components.location-picker';

    protected float $defaultLat = 14.6760; // Manila, Philippines
    protected float $defaultLng = 120.9822;
    protected int $zoom = 12;
    protected string $height = '400px';
    protected bool $showAddressInput = true;
    protected bool $showCoordinateInputs = true;
    protected string $country = 'PH';
    protected array $addressComponents = [];

    /**
     * Set default map center coordinates
     */
    public function defaultLatLng(float $lat, float $lng): static
    {
        $this->defaultLat = $lat;
        $this->defaultLng = $lng;
        return $this;
    }

    /**
     * Set map zoom level (1-20)
     */
    public function zoom(int $zoom): static
    {
        $this->zoom = max(1, min(20, $zoom));
        return $this;
    }

    /**
     * Set map container height
     */
    public function height(string $height): static
    {
        $this->height = $height;
        return $this;
    }

    /**
     * Show/hide address search input
     */
    public function showAddressInput(bool $show = true): static
    {
        $this->showAddressInput = $show;
        return $this;
    }

    /**
     * Show/hide coordinate input fields
     */
    public function showCoordinateInputs(bool $show = true): static
    {
        $this->showCoordinateInputs = $show;
        return $this;
    }

    /**
     * Set country bias for address search
     */
    public function country(string $countryCode): static
    {
        $this->country = strtoupper($countryCode);
        return $this;
    }

    /**
     * Get default coordinates
     */
    public function getDefaultCoordinates(): array
    {
        return [
            'lat' => $this->defaultLat,
            'lng' => $this->defaultLng,
        ];
    }

    /**
     * Get map zoom level
     */
    public function getZoom(): int
    {
        return $this->zoom;
    }

    /**
     * Get map height
     */
    public function getHeight(): string
    {
        return $this->height;
    }

    /**
     * Should show address input
     */
    public function shouldShowAddressInput(): bool
    {
        return $this->showAddressInput;
    }

    /**
     * Should show coordinate inputs
     */
    public function shouldShowCoordinateInputs(): bool
    {
        return $this->showCoordinateInputs;
    }

    /**
     * Get country code
     */
    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * Get MapTiler API key for JavaScript maps
     */
    public function getApiKey(): ?string
    {
        return app(FreeMapsService::class)->getJavaScriptApiKey();
    }

    /**
     * Get current state value or default
     */
    public function getLocationData(): array
    {
        $state = $this->getState();
        
        if (is_array($state) && isset($state['lat'], $state['lng'])) {
            return $state;
        }

        return [
            'lat' => $this->defaultLat,
            'lng' => $this->defaultLng,
            'address' => '',
            'formatted_address' => '',
            'place_id' => '',
        ];
    }

    /**
     * Set up the component state structure
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->default([
            'lat' => $this->defaultLat,
            'lng' => $this->defaultLng,
            'address' => '',
            'formatted_address' => '',
            'place_id' => '',
        ]);

        $this->afterStateUpdated(function ($component, $state) {
            // Trigger reactive updates when location changes
            if (is_array($state) && isset($state['lat'], $state['lng'])) {
                $component->getContainer()->getComponent($component->getStatePath())->getState();
            }
        });
    }

    /**
     * Dehydrate state for form submission
     */
    public function dehydrateState(array &$state, bool $isDehydrated = true): void
    {
        // This component is dehydrated false, so no state modification needed
        // The actual location data is handled by the hidden fields
    }

    /**
     * Validate that coordinates are within valid ranges
     */
    public function validate(): void
    {
        $state = $this->getState();

        if (is_array($state)) {
            $lat = $state['lat'] ?? null;
            $lng = $state['lng'] ?? null;

            if ($lat !== null && ($lat < -90 || $lat > 90)) {
                $this->getContainer()->addError($this->getStatePath(), 'Latitude must be between -90 and 90 degrees.');
            }

            if ($lng !== null && ($lng < -180 || $lng > 180)) {
                $this->getContainer()->addError($this->getStatePath(), 'Longitude must be between -180 and 180 degrees.');
            }
        }
    }
}