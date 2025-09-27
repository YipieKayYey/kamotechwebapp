<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email - Kamotech</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #083860 0%, #0c538f 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .content {
            padding: 40px 30px;
        }
        .verification-box {
            background-color: #f8f9fa;
            border: 2px solid #0c538f;
            border-radius: 8px;
            padding: 25px;
            text-align: center;
            margin: 30px 0;
        }
        .verify-button {
            display: inline-block;
            background: linear-gradient(135deg, #0c538f 0%, #083860 100%);
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            margin: 15px 0;
            transition: transform 0.2s;
        }
        .verify-button:hover {
            transform: translateY(-2px);
        }
        .message {
            margin-bottom: 20px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            font-size: 14px;
            color: #666;
        }
        .warning {
            color: #dc3545;
            font-size: 14px;
            margin-top: 20px;
        }
        .url-fallback {
            background-color: #fff;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            word-break: break-all;
            font-size: 12px;
            color: #666;
        }
        @media (max-width: 600px) {
            .container {
                margin: 0;
                border-radius: 0;
            }
            .content {
                padding: 20px;
            }
            .header h1 {
                font-size: 24px;
            }
            .verify-button {
                padding: 12px 24px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Kamotech</h1>
            <p style="margin: 5px 0 0 0; opacity: 0.9;">Air-Conditioning Services</p>
        </div>
        
        <div class="content">
            <p class="message">Hello {{ $userName }},</p>
            
            <p class="message">
                Welcome to Kamotech! We're excited to have you join our community of satisfied customers.
                To complete your account setup and start booking our professional air-conditioning services, 
                please verify your email address.
            </p>
            
            <div class="verification-box">
                <h3 style="margin: 0 0 15px 0; color: #0c538f;">Verify Your Email Address</h3>
                <p style="margin: 0 0 20px 0; color: #666; font-size: 14px;">
                    Click the button below to confirm your email and activate your account
                </p>
                
                <a href="{{ $verificationUrl }}" class="verify-button">
                    ✓ Verify Email Address
                </a>
                
                <p style="margin: 15px 0 0 0; color: #666; font-size: 12px;">
                    This verification link will expire in 60 minutes
                </p>
            </div>
            
            <p class="message">
                Once verified, you'll be able to:
            </p>
            
            <ul style="margin: 0 0 20px 20px; color: #555;">
                <li>Book air-conditioning services online</li>
                <li>Track your service appointments</li>
                <li>Rate and review our technicians</li>
                <li>Access exclusive promotions and discounts</li>
            </ul>
            
            <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; padding: 15px; margin: 20px 0;">
                <p style="margin: 0; color: #856404; font-size: 14px;">
                    <strong>Having trouble clicking the button?</strong><br>
                    Copy and paste this URL into your web browser:
                </p>
                <div class="url-fallback">
                    {{ $verificationUrl }}
                </div>
            </div>
            
            <p class="warning">
                <strong>Security Notice:</strong> If you didn't create an account with Kamotech, 
                please ignore this email. This verification link is only valid for your email address 
                and will expire automatically.
            </p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} Kamotech Air-Conditioning Services. All rights reserved.</p>
            <p style="margin: 5px 0;">"Your Comfort, Our Priority"</p>
            <p style="margin: 10px 0 0 0; font-size: 12px;">
                Professional air-conditioning services in Bataan, Philippines
            </p>
        </div>
    </div>
</body>
</html>
