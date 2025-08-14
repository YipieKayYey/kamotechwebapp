<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Summary Cards --}}
        @if($this->showReport && $this->startDate && $this->endDate)
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Bookings</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                                {{ $this->getTotalBookings() }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Revenue</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                                ₱{{ number_format($this->getTotalRevenue(), 2) }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-yellow-100 dark:bg-yellow-900 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Average Rating</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                                {{ number_format($this->getAverageRating(), 2) }}/5.0
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Report Table --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    @if($this->showReport && $this->reportType && $this->startDate && $this->endDate)
                        {{ ucfirst($this->reportType) }} Report 
                        @if($this->selectedTechnician)
                            for {{ \App\Models\Technician::find($this->selectedTechnician)?->user?->name ?? 'Selected Technician' }}
                        @endif
                        ({{ $this->startDate }} to {{ $this->endDate }})
                    @else
                        Technician Performance Reports
                    @endif
                </h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    @if($this->showReport)
                        Detailed performance metrics for technicians in the selected period
                    @else
                        Generate a report to view comprehensive technician performance data
                    @endif
                </p>
            </div>
            
            <div class="p-6">
                {{ $this->table }}
            </div>
        </div>

        {{-- Additional Info --}}
        @if($this->showReport)
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">Performance Metrics Explanation</h3>
                        <div class="mt-2 text-sm text-blue-700 dark:text-blue-300 space-y-1">
                            <p><strong>Excellent:</strong> 95%+ completion rate with 4.5+ rating</p>
                            <p><strong>Good:</strong> 85%+ completion rate with 4.0+ rating</p>
                            <p><strong>Average:</strong> 70%+ completion rate with 3.5+ rating</p>
                            <p><strong>Needs Improvement:</strong> Below average thresholds</p>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-8 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Ready to Generate Reports</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Click the "Generate Report" button above to create comprehensive technician performance reports.
                </p>
                <div class="mt-4 grid grid-cols-2 gap-4 text-xs text-gray-600 dark:text-gray-400">
                    <div class="bg-white dark:bg-gray-700 p-3 rounded">
                        <strong>Available Reports:</strong><br>
                        • Weekly Performance<br>
                        • Monthly Analysis<br>
                        • Yearly Overview<br>
                        • Custom Date Range
                    </div>
                    <div class="bg-white dark:bg-gray-700 p-3 rounded">
                        <strong>Metrics Tracked:</strong><br>
                        • Job Completion Rates<br>
                        • Customer Ratings<br>
                        • Commission Earnings<br>
                        • Performance Badges
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>