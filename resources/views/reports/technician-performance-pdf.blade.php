@extends('reports.pdf-layout')

@section('title', 'Technician Performance Report')
@section('report-title', 'TECHNICIAN PERFORMANCE REPORT')

@section('content')
    {{-- Key Metrics Summary --}}
    <div class="stats-row">
        <div class="stat-item">
            <div class="stat-value">{{ $totalBookings }}</div>
            <div class="stat-label">Total Jobs</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">PHP {{ number_format($totalRevenue, 0) }}</div>
            <div class="stat-label">Total Revenue</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ number_format($averageRating, 2) }}/5.0</div>
            <div class="stat-label">Average Rating</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ number_format($averageCompletionRate, 0) }}%</div>
            <div class="stat-label">Completion Rate</div>
        </div>
    </div>

    {{-- Performance Table --}}
    <div class="section">
        <h3 class="section-title">Technician Performance Details</h3>
        <table>
            <thead>
                <tr>
                    <th width="20%">Technician</th>
                    <th width="10%" class="text-center">ID</th>
                    <th width="8%" class="text-center">Jobs</th>
                    <th width="10%" class="text-center">Completed</th>
                    <th width="12%" class="text-right">Earnings</th>
                    <th width="10%" class="text-center">Rating</th>
                    <th width="10%" class="text-center">Rate</th>
                    <th width="20%">Performance</th>
                </tr>
            </thead>
            <tbody>
                @foreach(array_slice($performanceData, 0, 15) as $tech)
                    <tr>
                        <td><strong>{{ $tech['name'] }}</strong></td>
                        <td class="text-center">{{ $tech['id'] }}</td>
                        <td class="text-center">{{ $tech['total_jobs'] }}</td>
                        <td class="text-center">{{ $tech['completed'] }}</td>
                        <td class="text-right">PHP {{ number_format($tech['earnings'], 0) }}</td>
                        <td class="text-center">
                            @if($tech['rating'] >= 4.5)
                                <span class="badge badge-success">{{ number_format($tech['rating'], 1) }}</span>
                            @elseif($tech['rating'] >= 3.5)
                                <span class="badge badge-warning">{{ number_format($tech['rating'], 1) }}</span>
                            @else
                                <span class="badge badge-danger">{{ number_format($tech['rating'], 1) }}</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $tech['completion_rate'] }}%</td>
                        <td>
                            <div class="mini-bar">
                                <div class="mini-bar-fill" style="width: {{ $tech['completion_rate'] }}%"></div>
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
            {{-- Top Performers --}}
            <div class="section">
                <h3 class="section-title">Top Performers by Rating</h3>
                <table>
                    <thead>
                        <tr>
                            <th width="60%">Technician</th>
                            <th width="20%" class="text-center">Rating</th>
                            <th width="20%" class="text-center">Jobs</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(array_slice($topPerformers, 0, 5) as $index => $performer)
                            <tr>
                                <td>{{ $index + 1 }}. {{ $performer['name'] }}</td>
                                <td class="text-center">
                                    <span class="badge badge-success">{{ number_format($performer['rating'], 1) }}</span>
                                </td>
                                <td class="text-center">{{ $performer['jobs'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="content-col" style="width: 50%;">
        </div>
    </div>

@endsection
