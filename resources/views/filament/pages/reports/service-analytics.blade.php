<x-filament-panels::page>
    <div class="space-y-6">
        @if($this->showReport)
            {{-- KPI Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Total Bookings --}}
                <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <div class="rounded-lg bg-primary-50 p-3 dark:bg-primary-500/20">
                                <x-heroicon-o-calendar-days class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Bookings</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $this->getTotalBookings() }}</p>
                        </div>
                    </div>
                </div>
                
                {{-- Completed Bookings --}}
                <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <div class="rounded-lg bg-success-50 p-3 dark:bg-success-500/20">
                                <x-heroicon-o-check-circle class="h-6 w-6 text-success-600 dark:text-success-400" />
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Completed Bookings</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $this->getCompletedBookings() }}</p>
                        </div>
                    </div>
                </div>
                
                {{-- Completion Rate --}}
                <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <div class="rounded-lg bg-info-50 p-3 dark:bg-info-500/20">
                                <x-heroicon-o-chart-pie class="h-6 w-6 text-info-600 dark:text-info-400" />
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Completion Rate</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $this->getCompletionRate() }}</p>
                        </div>
                    </div>
                </div>
                
                {{-- Average Units per Booking --}}
                <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <div class="rounded-lg bg-warning-50 p-3 dark:bg-warning-500/20">
                                <x-heroicon-o-squares-2x2 class="h-6 w-6 text-warning-600 dark:text-warning-400" />
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Avg Units/Booking</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $this->getAverageUnitsPerBooking() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Service Distribution Section --}}
            <x-filament::section>
                <x-slot name="heading">
                    Service Distribution
                </x-slot>
                
                @php
                    $serviceData = $this->getServiceDistribution();
                    $total = collect($serviceData)->sum('count');
                @endphp
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    @foreach($serviceData as $service)
                        @php
                            $percentage = $total > 0 ? ($service['count'] / $total) * 100 : 0;
                        @endphp
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="font-medium">{{ $service['service'] }}</span>
                                <span class="font-semibold">{{ $service['count'] }} ({{ number_format($percentage, 1) }}%)</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                                <div class="bg-primary-600 h-2.5 rounded-full" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
            </div>

            {{-- Service Performance Table --}}
            <x-filament::section>
                <x-slot name="heading">
                    Service Performance Details
                </x-slot>
                
                <x-filament-tables::container>
                    <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Total Bookings</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Completed</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Completion Rate</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Duration (hrs)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($this->getServicePerformanceData() as $service)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium">{{ $service['service'] }}</td>
                                    <td class="px-4 py-3 text-sm text-center">{{ $service['total_bookings'] }}</td>
                                    <td class="px-4 py-3 text-sm text-center">{{ $service['completed'] }}</td>
                                    <td class="px-4 py-3 text-sm text-center">
                                        <x-filament::badge color="{{ $service['completion_rate'] >= 90 ? 'success' : ($service['completion_rate'] >= 70 ? 'warning' : 'danger') }}">
                                            {{ $service['completion_rate'] }}%
                                        </x-filament::badge>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right">₱{{ number_format($service['revenue'], 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-center">{{ $service['avg_duration'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-filament-tables::container>
            </x-filament::section>
            
            {{-- Insights --}}
            <x-filament::section
                icon="heroicon-o-light-bulb"
                icon-color="warning"
            >
                <x-slot name="heading">
                    Key Insights
                </x-slot>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="font-semibold mb-2">Most Popular Service:</p>
                        <p class="text-primary-600 dark:text-primary-400">{{ $this->getMostPopularService() }}</p>
                    </div>
                    <div>
                        <p class="font-semibold mb-2">Service Mix:</p>
                        <p>{{ count($this->getServiceDistribution()) }} different services utilized</p>
                    </div>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
