<x-filament-panels::page>
    <div class="space-y-6">
        @if($this->showReport)
            {{-- Satisfaction KPIs --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Overall Satisfaction --}}
                <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <div class="rounded-lg bg-warning-50 p-3 dark:bg-warning-500/20">
                                <x-heroicon-o-star class="h-6 w-6 text-warning-600 dark:text-warning-400" />
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Overall Satisfaction</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                {{ $this->getOverallSatisfaction() }}/5.0
                            </p>
                        </div>
                    </div>
                </div>
                
                {{-- Total Reviews --}}
                <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <div class="rounded-lg bg-primary-50 p-3 dark:bg-primary-500/20">
                                <x-heroicon-o-chat-bubble-bottom-center-text class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Reviews</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                {{ $this->getTotalReviews() }}
                            </p>
                        </div>
                    </div>
                </div>
                
                {{-- Response Rate --}}
                <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <div class="rounded-lg bg-info-50 p-3 dark:bg-info-500/20">
                                <x-heroicon-o-chart-bar class="h-6 w-6 text-info-600 dark:text-info-400" />
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Response Rate</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                {{ $this->getResponseRate() }}
                            </p>
                        </div>
                    </div>
                </div>
                
                {{-- 5-Star Reviews --}}
                <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <div class="rounded-lg bg-success-50 p-3 dark:bg-success-500/20">
                                <x-heroicon-o-sparkles class="h-6 w-6 text-success-600 dark:text-success-400" />
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">5-Star Reviews</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                {{ collect($this->getRatingDistribution())->firstWhere('rating', 5)['count'] ?? 0 }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Rating Distribution --}}
                <x-filament::section>
                    <x-slot name="heading">
                        Rating Distribution
                    </x-slot>
                    
                    @php
                        $distribution = $this->getRatingDistribution();
                        $totalReviews = collect($distribution)->sum('count');
                    @endphp
                    
                    <div class="space-y-3">
                        @foreach($distribution as $rating)
                            @php
                                $percentage = $totalReviews > 0 ? ($rating['count'] / $totalReviews) * 100 : 0;
                            @endphp
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <div class="flex items-center space-x-1">
                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $rating['rating'] }}</span>
                                        <x-heroicon-s-star class="h-4 w-4 text-warning-500" />
                                    </div>
                                    <span class="text-sm">{{ $rating['count'] }} ({{ number_format($percentage, 1) }}%)</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                                    <div class="bg-warning-500 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>

                {{-- Category Ratings --}}
                <x-filament::section>
                    <x-slot name="heading">
                        Category-wise Ratings
                    </x-slot>
                    
                    <div class="space-y-3">
                        @foreach($this->getCategoryRatings() as $category)
                            <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <div class="flex items-center">
                                    <x-dynamic-component :component="'heroicon-o-' . ($category['icon'] ?? 'star')" class="h-5 w-5 text-gray-500 mr-2" />
                                    <span class="text-sm font-medium">{{ $category['category'] }}</span>
                                </div>
                                <div class="flex items-center">
                                    <span class="text-lg font-semibold mr-1">{{ $category['rating'] }}</span>
                                    <x-heroicon-s-star class="h-4 w-4 text-warning-500" />
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            </div>

            {{-- Service Satisfaction --}}
            <x-filament::section>
                <x-slot name="heading">
                    Service-wise Customer Satisfaction
                </x-slot>
                
                <x-filament-tables::container>
                    <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Average Rating</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Reviews</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($this->getServiceSatisfaction() as $service)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium">{{ $service['service'] }}</td>
                                    <td class="px-4 py-3 text-sm text-center font-semibold">{{ $service['rating'] }}/5.0</td>
                                    <td class="px-4 py-3 text-sm text-center">{{ $service['reviews'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-filament-tables::container>
            </x-filament::section>

            {{-- Recent Reviews --}}
            <x-filament::section>
                <x-slot name="heading">
                    Recent Customer Reviews
                </x-slot>
                
                <div class="space-y-4">
                    @forelse($this->getRecentReviews() as $review)
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <p class="font-medium">{{ $review['customer'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $review['service'] }} - {{ $review['technician'] }}</p>
                                </div>
                                <div class="flex items-center">
                                    @for($i = 1; $i <= 5; $i++)
                                        <x-heroicon-s-star class="h-4 w-4 {{ $i <= $review['rating'] ? 'text-warning-500' : 'text-gray-300' }}" />
                                    @endfor
                                    <span class="ml-2 text-sm text-gray-500">{{ $review['date'] }}</span>
                                </div>
                            </div>
                            @if($review['review'])
                                <p class="text-sm text-gray-600 dark:text-gray-300">{{ $review['review'] }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-center text-gray-500">No reviews available for the selected period.</p>
                    @endforelse
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
