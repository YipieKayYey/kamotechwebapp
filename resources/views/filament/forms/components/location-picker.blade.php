{{-- 
Google Maps Location Picker Component for Filament
Features interactive map with click-to-select, address autocomplete, and real-time coordinate updates
--}}

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div 
        x-data="locationPicker({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }},
            statePath: '{{ $getStatePath() }}',
            defaultLat: {{ $getDefaultCoordinates()['lat'] }},
            defaultLng: {{ $getDefaultCoordinates()['lng'] }},
            zoom: {{ $getZoom() }},
            apiKey: '{{ $getApiKey() }}',
            country: '{{ $getCountry() }}',
            showAddressInput: {{ $shouldShowAddressInput() ? 'true' : 'false' }},
            showCoordinateInputs: {{ $shouldShowCoordinateInputs() ? 'true' : 'false' }},
        })"
        x-init="initMap()"
        class="space-y-4"
    >
        <!-- Address Search Input -->
        <div x-show="showAddressInput" class="space-y-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ $getLabel() ?? 'Search Address' }}
            </label>
            <div class="relative">
                <input
                    x-model="addressInput"
                    x-on:input.debounce.500ms="searchAddress()"
                    type="text"
                    placeholder="{{ $getPlaceholder() ?? 'Type address or click on map...' }}"
                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                />
                
                <!-- Autocomplete Dropdown -->
                <div 
                    x-show="showSuggestions && suggestions.length > 0"
                    x-transition
                    class="absolute z-50 mt-1 w-full rounded-lg border border-gray-300 bg-white shadow-lg dark:border-gray-600 dark:bg-gray-700"
                >
                    <ul class="max-h-60 overflow-auto py-1">
                        <template x-for="(suggestion, index) in suggestions" :key="suggestion.place_id">
                            <li
                                x-on:click="selectSuggestion(suggestion)"
                                class="cursor-pointer px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-600"
                            >
                                <div class="font-medium text-gray-900 dark:text-white" x-text="suggestion.main_text"></div>
                                <div class="text-gray-500 dark:text-gray-400" x-text="suggestion.secondary_text"></div>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Google Maps Container -->
        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Location on Map
                </label>
                <button
                    x-on:click="getCurrentLocation()"
                    type="button"
                    class="inline-flex items-center gap-1 rounded-md bg-primary-600 px-3 py-1 text-xs text-white hover:bg-primary-700 focus:ring-2 focus:ring-primary-500"
                >
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                    </svg>
                    Use My Location
                </button>
            </div>
            
            <!-- Map Container -->
            <div 
                id="map-{{ $getStatePath() }}"
                class="w-full rounded-lg border border-gray-300 dark:border-gray-600"
                style="height: {{ $getHeight() }}"
            ></div>
            
            <!-- Loading State -->
            <div x-show="loading" class="flex items-center justify-center py-8">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Loading map...
                </div>
            </div>
        </div>

        <!-- Coordinate Inputs (Optional) -->
        <div x-show="showCoordinateInputs" class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Latitude
                </label>
                <input
                    x-model.number="state.lat"
                    x-on:change="updateMapFromCoordinates()"
                    type="number"
                    step="0.0000001"
                    min="-90"
                    max="90"
                    placeholder="14.6760"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Longitude
                </label>
                <input
                    x-model.number="state.lng"
                    x-on:change="updateMapFromCoordinates()"
                    type="number"
                    step="0.0000001"
                    min="-180"
                    max="180"
                    placeholder="120.9822"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                />
            </div>
        </div>

        <!-- Selected Address Display -->
        <div x-show="state.formatted_address" class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
            <div class="flex items-start gap-2">
                <svg class="mt-0.5 h-4 w-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                <div class="flex-1">
                    <div class="text-sm font-medium text-gray-900 dark:text-white">Selected Address:</div>
                    <div class="text-sm text-gray-600 dark:text-gray-300" x-text="state.formatted_address"></div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Coordinates: <span x-text="parseFloat(state.lat).toFixed(6)"></span>, <span x-text="parseFloat(state.lng).toFixed(6)"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>

{{-- Google Maps JavaScript --}}
@once
<script>
/**
 * Location Picker Alpine.js Component
 * Integrates with Google Maps JavaScript API for interactive location selection
 */
function locationPicker(config) {
    return {
        // Component state
        state: config.state || {
            lat: config.defaultLat,
            lng: config.defaultLng,
            address: '',
            formatted_address: '',
            place_id: ''
        },
        
        // Configuration
        statePath: config.statePath,
        defaultLat: config.defaultLat,
        defaultLng: config.defaultLng,
        zoom: config.zoom,
        apiKey: config.apiKey,
        country: config.country,
        showAddressInput: config.showAddressInput,
        showCoordinateInputs: config.showCoordinateInputs,
        
        // Component properties
        map: null,
        marker: null,
        autocompleteService: null,
        placesService: null,
        geocoder: null,
        loading: true,
        
        // Address search
        addressInput: '',
        suggestions: [],
        showSuggestions: false,
        
        /**
         * Initialize Google Maps
         */
        async initMap() {
            if (!this.apiKey) {
                console.error('Google Maps API key not configured');
                this.loading = false;
                return;
            }

            // Wait for Google Maps to load
            if (typeof google === 'undefined') {
                await this.loadGoogleMapsAPI();
            }

            // Initialize map
            const mapElement = document.getElementById(`map-${this.statePath}`);
            if (!mapElement) {
                console.error('Map container not found');
                this.loading = false;
                return;
            }

            // Create map
            this.map = new google.maps.Map(mapElement, {
                center: { lat: this.state.lat || this.defaultLat, lng: this.state.lng || this.defaultLng },
                zoom: this.zoom,
                mapTypeControl: true,
                streetViewControl: true,
                fullscreenControl: true,
                zoomControl: true,
            });

            // Initialize services
            this.autocompleteService = new google.maps.places.AutocompleteService();
            this.placesService = new google.maps.places.PlacesService(this.map);
            this.geocoder = new google.maps.Geocoder();

            // Create marker
            this.marker = new google.maps.Marker({
                position: { lat: this.state.lat || this.defaultLat, lng: this.state.lng || this.defaultLng },
                map: this.map,
                draggable: true,
                title: 'Selected Location'
            });

            // Map click handler
            this.map.addListener('click', (event) => {
                this.updateLocation(event.latLng.lat(), event.latLng.lng());
            });

            // Marker drag handler
            this.marker.addListener('dragend', (event) => {
                this.updateLocation(event.latLng.lat(), event.latLng.lng());
            });

            this.loading = false;
        },

        /**
         * Load Google Maps API dynamically
         */
        async loadGoogleMapsAPI() {
            return new Promise((resolve, reject) => {
                if (window.google && window.google.maps) {
                    resolve();
                    return;
                }

                const script = document.createElement('script');
                script.src = `https://maps.googleapis.com/maps/api/js?key=${this.apiKey}&libraries=places`;
                script.async = true;
                script.defer = true;
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            });
        },

        /**
         * Update location and reverse geocode
         */
        async updateLocation(lat, lng) {
            // Update coordinates
            this.state.lat = lat;
            this.state.lng = lng;

            // Update map and marker
            const position = { lat, lng };
            this.map.setCenter(position);
            this.marker.setPosition(position);

            // Reverse geocode to get address
            try {
                const results = await this.reverseGeocode(lat, lng);
                if (results && results.length > 0) {
                    this.state.formatted_address = results[0].formatted_address;
                    this.state.place_id = results[0].place_id;
                    this.addressInput = results[0].formatted_address;
                }
            } catch (error) {
                console.error('Reverse geocoding failed:', error);
            }

            // Trigger reactive update
            this.$dispatch('input', this.state);
        },

        /**
         * Reverse geocode coordinates to address
         */
        reverseGeocode(lat, lng) {
            return new Promise((resolve, reject) => {
                this.geocoder.geocode({ location: { lat, lng } }, (results, status) => {
                    if (status === 'OK') {
                        resolve(results);
                    } else {
                        reject(new Error(`Geocoding failed: ${status}`));
                    }
                });
            });
        },

        /**
         * Search address with autocomplete
         */
        async searchAddress() {
            if (!this.addressInput || this.addressInput.length < 3) {
                this.suggestions = [];
                this.showSuggestions = false;
                return;
            }

            if (!this.autocompleteService) {
                return;
            }

            try {
                const request = {
                    input: this.addressInput,
                    componentRestrictions: { country: this.country.toLowerCase() },
                };

                this.autocompleteService.getPlacePredictions(request, (predictions, status) => {
                    if (status === google.maps.places.PlacesServiceStatus.OK && predictions) {
                        this.suggestions = predictions.map(prediction => ({
                            place_id: prediction.place_id,
                            description: prediction.description,
                            main_text: prediction.structured_formatting.main_text,
                            secondary_text: prediction.structured_formatting.secondary_text || '',
                        }));
                        this.showSuggestions = true;
                    } else {
                        this.suggestions = [];
                        this.showSuggestions = false;
                    }
                });
            } catch (error) {
                console.error('Address search failed:', error);
                this.suggestions = [];
                this.showSuggestions = false;
            }
        },

        /**
         * Select autocomplete suggestion
         */
        async selectSuggestion(suggestion) {
            this.addressInput = suggestion.description;
            this.suggestions = [];
            this.showSuggestions = false;

            // Get place details
            try {
                const place = await this.getPlaceDetails(suggestion.place_id);
                if (place && place.geometry) {
                    const lat = place.geometry.location.lat();
                    const lng = place.geometry.location.lng();
                    await this.updateLocation(lat, lng);
                }
            } catch (error) {
                console.error('Failed to get place details:', error);
            }
        },

        /**
         * Get place details by place ID
         */
        getPlaceDetails(placeId) {
            return new Promise((resolve, reject) => {
                this.placesService.getDetails({
                    placeId: placeId,
                    fields: ['geometry', 'formatted_address', 'name']
                }, (place, status) => {
                    if (status === google.maps.places.PlacesServiceStatus.OK) {
                        resolve(place);
                    } else {
                        reject(new Error(`Place details failed: ${status}`));
                    }
                });
            });
        },

        /**
         * Update map when coordinates are manually changed
         */
        updateMapFromCoordinates() {
            if (this.map && this.marker && this.state.lat && this.state.lng) {
                const position = { lat: this.state.lat, lng: this.state.lng };
                this.map.setCenter(position);
                this.marker.setPosition(position);
                
                // Reverse geocode the new position
                this.reverseGeocode(this.state.lat, this.state.lng)
                    .then(results => {
                        if (results && results.length > 0) {
                            this.state.formatted_address = results[0].formatted_address;
                            this.state.place_id = results[0].place_id;
                            this.addressInput = results[0].formatted_address;
                        }
                    })
                    .catch(error => console.error('Reverse geocoding failed:', error));
            }
        },

        /**
         * Get current user location
         */
        getCurrentLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        this.updateLocation(lat, lng);
                    },
                    (error) => {
                        console.error('Geolocation failed:', error);
                        alert('Unable to get your current location. Please select manually on the map.');
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 60000
                    }
                );
            } else {
                alert('Geolocation is not supported by this browser.');
            }
        },

        /**
         * Handle clicks outside to close suggestions
         */
        init() {
            // Close suggestions when clicking outside
            document.addEventListener('click', (event) => {
                if (!this.$el.contains(event.target)) {
                    this.showSuggestions = false;
                }
            });
        }
    };
}
</script>
@endonce