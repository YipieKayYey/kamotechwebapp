<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'KAMOTECH Report')</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #1f2937;
            background-color: white;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            display: table;
            width: 100%;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 3px solid #3b82f6;
        }
        
        .logo-section {
            display: table-cell;
            width: 150px;
            vertical-align: middle;
        }
        
        .logo-img {
            max-width: 120px;
            height: auto;
        }
        
        .logo-text {
            font-size: 24px;
            font-weight: bold;
            color: #3b82f6;
            letter-spacing: 2px;
        }
        
        .header-content {
            display: table-cell;
            text-align: center;
            vertical-align: middle;
        }
        
        .header h1 {
            color: #3b82f6;
            font-size: 20px;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .header h2 {
            color: #64748b;
            font-size: 14px;
            font-weight: normal;
        }
        
        .header-date {
            font-size: 11px;
            color: #9ca3af;
            margin-top: 3px;
        }
        
        .stats-row {
            margin-bottom: 20px;
            display: table;
            width: 100%;
            background: #f0f9ff;
            border: 1px solid #bfdbfe;
            border-radius: 6px;
        }
        
        .stat-item {
            display: table-cell;
            text-align: center;
            padding: 12px 8px;
            border-right: 1px solid #bfdbfe;
        }
        
        .stat-item:last-child {
            border-right: none;
        }
        
        .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #3b82f6;
            margin-bottom: 4px;
        }
        
        .stat-label {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .content-grid {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }
        
        .content-col {
            display: table-cell;
            vertical-align: top;
            padding-right: 8px;
        }
        
        .content-col:last-child {
            padding-right: 0;
        }
        
        .section {
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #1f2937;
            padding: 5px 0;
            border-bottom: 2px solid #e5e7eb;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #f3f4f6;
            padding: 8px 10px;
            text-align: left;
            font-weight: 600;
            font-size: 10px;
            text-transform: uppercase;
            color: #6b7280;
            border-bottom: 1px solid #e5e7eb;
        }
        
        td {
            padding: 6px 10px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 11px;
        }
        
        tr:nth-child(even) {
            background-color: #fafafa;
        }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: 600;
        }
        
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        
        .mini-bar {
            display: inline-block;
            width: 60px;
            height: 6px;
            background: #f3f4f6;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .mini-bar-fill {
            height: 100%;
            background: #3b82f6;
        }
        
        .insights {
            background: #f8fafc;
            padding: 12px;
            border-radius: 5px;
            border: 1px solid #e2e8f0;
            font-size: 10px;
            margin-top: 20px;
        }
        
        .insights p {
            margin: 3px 0;
        }
        
        .footer {
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            text-align: center;
            font-size: 9px;
            color: #9ca3af;
        }
        
        @page {
            margin: 25px;
            size: A4;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-section">
            @if(file_exists(public_path('images/logo-main.png')))
                <img src="{{ public_path('images/logo-main.png') }}" alt="KAMOTECH" class="logo-img">
            @else
                <div class="logo-text">KAMOTECH</div>
            @endif
        </div>
        <div class="header-content">
            <h1>@yield('report-title')</h1>
            <p class="header-date">{{ $generatedAt }}</p>
        </div>
    </div>
    
    @yield('content')
    
    <div class="footer">
        © {{ date('Y') }} KAMOTECH Air Conditioning Services | Professional Report
    </div>
</body>
</html>
