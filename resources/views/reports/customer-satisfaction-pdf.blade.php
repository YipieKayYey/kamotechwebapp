@extends('reports.pdf-layout')

@section('title', 'Customer Satisfaction Report')
@section('report-title', 'CUSTOMER SATISFACTION REPORT')

@section('content')
    {{-- Key Metrics Summary --}}
    <div class="stats-row">
        <div class="stat-item">
            <div class="stat-value">{{ number_format($overallSatisfaction, 1) }}/5.0</div>
            <div class="stat-label">Overall Rating</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ $totalReviews }}</div>
            <div class="stat-label">Total Reviews</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ $responseRate }}%</div>
            <div class="stat-label">Response Rate</div>
        </div>
        <div class="stat-item">
            @php
                $satisfied = collect($ratingDistribution)->where('rating', '>=', 4)->sum('count');
                $satisfactionRate = $totalReviews > 0 ? round(($satisfied / $totalReviews) * 100, 0) : 0;
            @endphp
            <div class="stat-value">{{ $satisfactionRate }}%</div>
            <div class="stat-label">Satisfaction</div>
        </div>
    </div>

    {{-- Two Column Layout --}}
    <div class="content-grid">
        <div class="content-col" style="width: 40%;">
            {{-- Rating Distribution --}}
            <div class="section">
                <h3 class="section-title">Rating Distribution</h3>
                <table>
                    <thead>
                        <tr>
                            <th width="20%">Stars</th>
                            <th width="30%" class="text-center">Count</th>
                            <th width="50%">Distribution</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ratingDistribution as $rating)
                            <tr>
                                <td><strong>{{ $rating['rating'] }}</strong> Star{{ $rating['rating'] > 1 ? 's' : '' }}</td>
                                <td class="text-center">{{ $rating['count'] }}</td>
                                <td>
                                    <div class="mini-bar">
                                        <div class="mini-bar-fill" style="width: {{ $rating['percentage'] }}%; background: {{ $rating['rating'] >= 4 ? '#10b981' : ($rating['rating'] == 3 ? '#f59e0b' : '#ef4444') }}"></div>
                                    </div>
                                    <span style="font-size: 7px; margin-left: 2px;">{{ $rating['percentage'] }}%</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Category Ratings --}}
            <div class="section">
                <h3 class="section-title">Category Ratings</h3>
                <table>
                    <thead>
                        <tr>
                            <th width="50%">Category</th>
                            <th width="50%" class="text-center">Average</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categoryRatings as $category)
                            <tr>
                                <td>{{ $category['category'] }}</td>
                                <td class="text-center">
                                    <span class="badge badge-{{ $category['rating'] >= 4.0 ? 'success' : ($category['rating'] >= 3.0 ? 'warning' : 'danger') }}">
                                        {{ number_format($category['rating'], 1) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="content-col" style="width: 60%;">
            {{-- Top Rated Technicians --}}
            <div class="section">
                <h3 class="section-title">Top Rated Technicians</h3>
                <table>
                    <thead>
                        <tr>
                            <th width="50%">Technician</th>
                            <th width="25%" class="text-center">Rating</th>
                            <th width="25%" class="text-center">Reviews</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(array_slice($topRatedTechnicians, 0, 5) as $index => $tech)
                            <tr>
                                <td>{{ $index + 1 }}. {{ Str::limit($tech['technician'], 20) }}</td>
                                <td class="text-center">
                                    <span class="badge badge-success">{{ number_format($tech['rating'], 1) }}</span>
                                </td>
                                <td class="text-center">{{ $tech['reviews'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Service Satisfaction --}}
            <div class="section">
                <h3 class="section-title">Service Satisfaction</h3>
                <table>
                    <thead>
                        <tr>
                            <th width="50%">Service</th>
                            <th width="25%" class="text-center">Rating</th>
                            <th width="25%" class="text-center">Reviews</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(array_slice($serviceSatisfaction, 0, 5) as $service)
                            <tr>
                                <td>{{ Str::limit($service['service'], 20) }}</td>
                                <td class="text-center">
                                    <span class="badge badge-{{ $service['rating'] >= 4.0 ? 'success' : 'warning' }}">
                                        {{ number_format($service['rating'], 1) }}
                                    </span>
                                </td>
                                <td class="text-center">{{ $service['reviews'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>


@endsection
