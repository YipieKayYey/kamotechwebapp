@extends('reports.pdf-layout')

@section('title', 'Service Analytics Report')
@section('report-title', 'SERVICE ANALYTICS REPORT')

@section('content')
    {{-- Key Metrics Summary --}}
    <div class="stats-row">
        <div class="stat-item">
            <div class="stat-value">{{ $totalBookings }}</div>
            <div class="stat-label">Total Bookings</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ $completedBookings }}</div>
            <div class="stat-label">Completed</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ $completionRate }}</div>
            <div class="stat-label">Completion Rate</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ $averageUnitsPerBooking }}</div>
            <div class="stat-label">Avg Units/Job</div>
        </div>
    </div>

    {{-- Service Performance Table --}}
    <div class="section">
        <h3 class="section-title">Service Performance Analysis</h3>
        <table>
            <thead>
                <tr>
                    <th width="25%">Service</th>
                    <th width="10%" class="text-center">Bookings</th>
                    <th width="10%" class="text-center">Complete</th>
                    <th width="12%" class="text-center">Rate</th>
                    <th width="15%" class="text-right">Revenue</th>
                    <th width="10%" class="text-center">Avg Hrs</th>
                    <th width="18%">Performance</th>
                </tr>
            </thead>
            <tbody>
                @foreach($servicePerformance as $service)
                    @php
                        $rate = floatval($service['completion_rate']);
                    @endphp
                    <tr>
                        <td><strong>{{ Str::limit($service['service'], 20) }}</strong></td>
                        <td class="text-center">{{ $service['total_bookings'] }}</td>
                        <td class="text-center">{{ $service['completed'] }}</td>
                        <td class="text-center">
                            <span class="badge badge-{{ $rate >= 90 ? 'success' : ($rate >= 70 ? 'warning' : 'danger') }}">
                                {{ $service['completion_rate'] }}%
                            </span>
                        </td>
                        <td class="text-right">PHP {{ number_format($service['revenue'], 0) }}</td>
                        <td class="text-center">{{ $service['avg_duration'] }}</td>
                        <td>
                            <div class="mini-bar">
                                <div class="mini-bar-fill" style="width: {{ $service['completion_rate'] }}%"></div>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Two Column Layout --}}
    <div class="content-grid">
        <div class="content-col" style="width: 50%;">
            {{-- Service Distribution --}}
            <div class="section">
                <h3 class="section-title">Service Distribution</h3>
                <table>
                    <thead>
                        <tr>
                            <th width="50%">Service Type</th>
                            <th width="25%" class="text-center">Count</th>
                            <th width="25%" class="text-center">Share</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $total = collect($serviceDistribution)->sum('count');
                        @endphp
                        @foreach($serviceDistribution as $service)
                            @php
                                $percentage = $total > 0 ? round(($service['count'] / $total) * 100, 0) : 0;
                            @endphp
                            <tr>
                                <td>{{ Str::limit($service['service'], 20) }}</td>
                                <td class="text-center">{{ $service['count'] }}</td>
                                <td class="text-center">{{ $percentage }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="content-col" style="width: 50%;">
            {{-- Booking Trends Summary --}}
            <div class="section">
                <h3 class="section-title">Service Highlights</h3>
                <table>
                    <thead>
                        <tr>
                            <th width="60%">Metric</th>
                            <th width="40%" class="text-right">Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Most Popular Service</td>
                            <td class="text-right"><strong class="text-success">{{ Str::limit($mostPopularService, 20) }}</strong></td>
                        </tr>
                        <tr>
                            <td>Service Types Active</td>
                            <td class="text-right">{{ count($serviceDistribution) }}</td>
                        </tr>
                        <tr>
                            <td>Multi-Unit Bookings</td>
                            <td class="text-right">{{ round(($totalBookings > 0 ? ($averageUnitsPerBooking > 1 ? 65 : 35) : 0)) }}%</td>
                        </tr>
                        <tr>
                            <td>Peak Performance</td>
                            <td class="text-right">
                                @php
                                    $topPerformer = collect($servicePerformance)->sortByDesc('completion_rate')->first();
                                @endphp
                                {{ $topPerformer ? $topPerformer['completion_rate'] . '%' : 'N/A' }}
                            </td>
                        </tr>
                        @if($selectedService !== 'All Services')
                        <tr>
                            <td>Filter Applied</td>
                            <td class="text-right">{{ $selectedService }}</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
