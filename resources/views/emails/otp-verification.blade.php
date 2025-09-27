<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - Kamotech</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 0;
        }
        .header {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            padding: 30px;
            text-align: center;
        }
        .logo {
            color: white;
            font-size: 24px;
            font-weight: bold;
            margin: 0;
        }
        .content {
            padding: 40px 30px;
            text-align: center;
        }
        .welcome {
            font-size: 24px;
            color: #1e3a8a;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .message {
            font-size: 16px;
            margin-bottom: 30px;
            line-height: 1.8;
        }
        .otp-container {
            background: #f8fafc;
            border: 2px dashed #3b82f6;
            border-radius: 12px;
            padding: 30px;
            margin: 30px 0;
            text-align: center;
        }
        .otp-label {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .otp-code {
            font-size: 36px;
            font-weight: bold;
            color: #1e3a8a;
            letter-spacing: 8px;
            font-family: monospace;
            margin: 10px 0;
        }
        .otp-note {
            font-size: 12px;
            color: #64748b;
            margin-top: 15px;
        }
        .instructions {
            background: #fff7ed;
            border-left: 4px solid #f59e0b;
            padding: 20px;
            margin: 30px 0;
            text-align: left;
        }
        .instructions h3 {
            margin-top: 0;
            color: #92400e;
            font-size: 16px;
        }
        .instructions ol {
            margin: 10px 0;
            padding-left: 20px;
        }
        .instructions li {
            margin: 8px 0;
            font-size: 14px;
        }
        .security-note {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            font-size: 14px;
            color: #991b1b;
        }
        .footer {
            background: #f8fafc;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        .footer p {
            margin: 5px 0;
            font-size: 14px;
            color: #64748b;
        }
        .company-info {
            margin-top: 20px;
            font-size: 12px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1 class="logo">KAMOTECH</h1>
            <p style="color: #e2e8f0; margin: 5px 0 0 0; font-size: 14px;">Air Conditioning Services</p>
        </div>

        <!-- Content -->
        <div class="content">
            <h2 class="welcome">Hello {{ $userName }}!</h2>
            
            <p class="message">
                Welcome to Kamotech! To complete your registration and secure your account, 
                please use the verification code below:
            </p>

            <!-- OTP Code -->
            <div class="otp-container">
                <div class="otp-label">Your Verification Code</div>
                <div class="otp-code">{{ $otp }}</div>
                <div class="otp-note">This code expires in 10 minutes</div>
            </div>

            <!-- Instructions -->
            <div class="instructions">
                <h3>How to verify your email:</h3>
                <ol>
                    <li>Return to the Kamotech verification page</li>
                    <li>Enter the 6-digit code above</li>
                    <li>Click "Verify Email" to complete registration</li>
                </ol>
            </div>

            <!-- Security Note -->
            <div class="security-note">
                <strong>Security Notice:</strong> If you didn't create a Kamotech account, 
                please ignore this email. This code will expire automatically.
            </div>

            <p style="margin-top: 30px; font-size: 14px; color: #64748b;">
                Need help? Contact our support team and we'll be happy to assist you!
            </p>
        </div>

        <!-- Footer -->
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
