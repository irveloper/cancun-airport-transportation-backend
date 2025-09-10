<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Thank you for contacting FiveStars Transport</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            line-height: 1.6; 
            color: #333; 
            max-width: 600px; 
            margin: 0 auto; 
            padding: 20px;
        }
        .header { 
            background-color: #2563eb; 
            color: white; 
            padding: 30px; 
            text-align: center; 
            border-radius: 8px 8px 0 0;
        }
        .content { 
            background-color: #f8fafc; 
            padding: 30px; 
            border-radius: 0 0 8px 8px;
        }
        .highlight { 
            background-color: #dbeafe; 
            padding: 15px; 
            border-radius: 4px; 
            margin: 20px 0;
            text-align: center;
        }
        .services { 
            background-color: white; 
            padding: 20px; 
            border-radius: 4px; 
            margin: 20px 0;
        }
        .service-item { 
            padding: 10px 0; 
            border-bottom: 1px solid #e2e8f0;
        }
        .service-item:last-child { 
            border-bottom: none;
        }
        .contact-info { 
            background-color: #ecfdf5; 
            padding: 20px; 
            border-radius: 4px; 
            margin: 20px 0;
        }
        .footer { 
            margin-top: 30px; 
            padding-top: 20px; 
            border-top: 2px solid #e2e8f0; 
            font-size: 14px; 
            color: #6b7280; 
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>🚐 FiveStars Transport</h2>
        <h3>Thank you for contacting us!</h3>
    </div>
    
    <div class="content">
        <p>Dear {{ $contact->name }},</p>
        
        <p>Thank you for reaching out to FiveStars Transport. We have received your message regarding "<strong>{{ $contact->subject }}</strong>" and our team will respond within 24 hours.</p>

        <div class="highlight">
            <strong>Your Reference Number: {{ $referenceNumber }}</strong><br>
            <small>Please keep this for your records</small>
        </div>

        <div class="services">
            <h4>🌟 Our Services Include:</h4>
            <div class="service-item">✈️ <strong>Airport Transfers</strong> - Reliable transportation to/from Cancun Airport</div>
            <div class="service-item">🏨 <strong>Hotel Transfers</strong> - Comfortable rides between hotels and destinations</div>
            <div class="service-item">🚐 <strong>Group Transportation</strong> - Perfect for families and larger groups</div>
            <div class="service-item">⭐ <strong>VIP Services</strong> - Premium experience with luxury vehicles</div>
            <div class="service-item">🔄 <strong>Round Trip Options</strong> - Complete transportation solutions</div>
        </div>

        <div class="contact-info">
            <h4>📞 Need Immediate Assistance?</h4>
            <p>For urgent bookings or questions, you can also contact us directly:</p>
            <ul>
                <li><strong>Email:</strong> {{ config('services.sendgrid.from_email') }}</li>
                <li><strong>Website:</strong> Book online for instant confirmation</li>
                <li><strong>Response Time:</strong> Within 24 hours (usually much faster!)</li>
            </ul>
        </div>

        <p>We appreciate your interest in FiveStars Transport and look forward to providing you with exceptional transportation services in beautiful Cancun!</p>

        <p>Best regards,<br>
        <strong>The FiveStars Transport Team</strong></p>
    </div>

    <div class="footer">
        <p>This is an automated response to confirm we received your message.</p>
        <p><small>© {{ date('Y') }} FiveStars Transport. Professional transportation services in Cancun.</small></p>
    </div>
</body>
</html>