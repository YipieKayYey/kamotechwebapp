<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Summary Cards --}}
        @if($this->showReport && $this->startDate && $this->endDate)
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                {{-- Total Bookings Card --}}
                <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <div class="rounded-lg bg-primary-50 p-3 dark:bg-primary-500/20">
                                <x-heroicon-o-calendar-days class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Bookings</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                {{ $this->getTotalBookings() }}
                            </p>
                        </div>
                    </div>
                </div>
                
                {{-- Total Revenue Card --}}
                <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <div class="rounded-lg bg-success-50 p-3 dark:bg-success-500/20">
                                <x-heroicon-o-currency-dollar class="h-6 w-6 text-success-600 dark:text-success-400" />
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Revenue</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                ₱{{ number_format($this->getTotalRevenue(), 2) }}
                            </p>
                        </div>
                    </div>
                </div>
                
                {{-- Average Rating Card --}}
                <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <div class="rounded-lg bg-warning-50 p-3 dark:bg-warning-500/20">
                                <x-heroicon-o-star class="h-6 w-6 text-warning-600 dark:text-warning-400" />
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Average Rating</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                {{ number_format($this->getAverageRating(), 2) }}/5.0
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Report Table --}}
        <x-filament-tables::container>
            {{ $this->table }}
        </x-filament-tables::container>

        {{-- Additional Info --}}
        @if($this->showReport)
            <x-filament::section
                icon="heroicon-o-information-circle"
                icon-color="primary"
            >
                <x-slot name="heading">
                    Performance Metrics Explanation
                </x-slot>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="font-semibold text-success-600 dark:text-success-400">• Excellent: 95%+ completion rate with 4.5+ rating</p>
                        <p class="font-semibold text-primary-600 dark:text-primary-400">• Good: 85%+ completion rate with 4.0+ rating</p>
                    </div>
                    <div>
                        <p class="font-semibold text-warning-600 dark:text-warning-400">• Average: 70%+ completion rate with 3.5+ rating</p>
                        <p class="font-semibold text-danger-600 dark:text-danger-400">• Needs Improvement: Below average thresholds</p>
                    </div>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
