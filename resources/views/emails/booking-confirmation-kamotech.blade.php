@php($userName = $user->first_name ?? (explode(' ', $user->name ?? 'Valued Customer')[0]))
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation - Kamotech</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 0; }
        .header { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); padding: 30px; text-align: center; }
        .logo { color: white; font-size: 24px; font-weight: bold; margin: 0; }
        .content { padding: 40px 30px; }
        .title { font-size: 24px; color: #1e3a8a; margin-bottom: 10px; font-weight: bold; text-align: center; }
        .message { font-size: 16px; margin-bottom: 24px; text-align: center; color: #334155; }
        .summary { background: #f8fafc; border: 2px dashed #3b82f6; border-radius: 12px; padding: 20px; margin: 24px 0; }
        .row { display: flex; justify-content: space-between; gap: 10px; margin: 8px 0; }
        .label { font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 1px; }
        .val { font-weight: bold; color: #0f172a; }
        .promotions { background: #f0f9ff; border-left: 4px solid #3b82f6; padding: 20px; margin: 24px 0; }
        .promotions h3 { margin: 0 0 8px; color: #1e3a8a; font-size: 16px; }
        .promo-pill { display: inline-block; background: #e0f2fe; color: #1e3a8a; border: 1px solid #bfdbfe; border-radius: 999px; padding: 8px 12px; font-weight: 700; font-size: 12px; margin: 6px 6px 0 0; }
        .cta { text-align: center; margin-top: 24px; }
        .btn { display: inline-block; background: #3b82f6; color: #fff; text-decoration: none; padding: 12px 18px; border-radius: 10px; font-weight: bold; }
        .footer { background: #f8fafc; padding: 30px; text-align: center; border-top: 1px solid #e2e8f0; }
        .footer p { margin: 5px 0; font-size: 14px; color: #64748b; }
        .company-info { margin-top: 20px; font-size: 12px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="logo">KAMOTECH</h1>
            <p style="color: #e2e8f0; margin: 5px 0 0 0; font-size: 14px;">Air Conditioning Services</p>
        </div>

        <div class="content">
            <h2 class="title">Thank you for booking, {{ $userName }}! 🎉</h2>
            <p class="message">Your booking has been received. You’ll also get an SMS once everything is confirmed.</p>

            <div class="summary">
                <div class="row"><div class="label">Service</div><div class="val">{{ $summary['service'] ?? '—' }}</div></div>
                <div class="row"><div class="label">AC Type</div><div class="val">{{ $summary['aircon'] ?? '—' }}</div></div>
                <div class="row"><div class="label">Units</div><div class="val">{{ $summary['units'] ?? '—' }}</div></div>
                <div class="row"><div class="label">Schedule</div><div class="val">{{ $summary['start'] ?? '—' }} — {{ $summary['end'] ?? '—' }}</div></div>
                <div class="row"><div class="label">Location</div><div class="val">{{ $summary['address'] ?? '—' }}</div></div>
                <div class="row"><div class="label">Estimated Total</div><div class="val">₱{{ number_format($summary['total'] ?? 0, 2) }}</div></div>
            </div>

            @php($discountPromos = array_values(array_filter(($promotions ?? []), function ($p) {
                $text = $p['formatted_discount'] ?? '';
                return is_string($text) && preg_match('/%|off/i', $text);
            })))
            @if(!empty($discountPromos))
                <div class="promotions">
                    <h3>Exclusive Discount Offers</h3>
                    <p style="margin:0 0 10px; color:#334155;">Here are the exclusive and time-limited discounts we offer.</p>
                    @foreach($discountPromos as $promo)
                        <span class="promo-pill">{{ $promo['title'] }} — {{ $promo['formatted_discount'] }}</span>
                    @endforeach
                </div>
            @endif

            <div class="cta">
                <a href="{{ url('/customer-dashboard') }}" class="btn">View Booking</a>
            </div>
        </div>

        <div class="footer">
            <p><strong>KAMOTECH</strong></p>
            <p>Professional Air Conditioning Services</p>
            <p>Bataan, Philippines</p>
            <div class="company-info">
                <p>This email was sent to {{ $user->email }}</p>
                <p>&copy; {{ date('Y') }} Kamotech. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
