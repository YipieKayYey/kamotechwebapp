<x-filament-panels::page>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Profile Form -->
        <div>
            <x-filament::section>
                <x-slot name="heading">
                    Profile Information
                </x-slot>
                
                <form wire:submit="updateProfile">
                    {{ $this->profileForm }}
                    
                    <div class="mt-6">
                        <x-filament::button type="submit" color="primary">
                            Update Profile
                        </x-filament::button>
                    </div>
                </form>
            </x-filament::section>
        </div>

        <!-- Password Form -->
        <div>
            <x-filament::section>
                <x-slot name="heading">
                    Security Settings
                </x-slot>
                
                <form wire:submit="changePassword">
                    {{ $this->passwordForm }}
                    
                    <div class="mt-6">
                        <x-filament::button type="submit" color="warning">
                            Change Password
                        </x-filament::button>
                    </div>
                </form>
            </x-filament::section>
        </div>
    </div>

    <!-- Account Information -->
    <x-filament::section class="mt-6">
        <x-slot name="heading">
            Account Information
        </x-slot>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <div class="text-2xl font-bold text-green-600">
                    {{ \App\Models\Booking::where('technician_id', auth()->user()->technician?->id ?? 0)->where('status', 'completed')->count() }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Jobs Completed</div>
            </div>
            
            <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <div class="text-2xl font-bold text-blue-600">
                    {{ number_format(\App\Models\RatingReview::whereHas('booking', fn($q) => $q->where('technician_id', auth()->user()->technician?->id))->avg('overall_rating') ?? 0, 1) }}/5
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Average Rating</div>
            </div>
            
            <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <div class="text-2xl font-bold text-purple-600">
                    {{ auth()->user()->created_at->format('M Y') }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Member Since</div>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
