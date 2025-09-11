<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Group Quote Request Received</title>
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
            background-color: #16a34a; 
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
        .reference { 
            background-color: #dcfce7; 
            padding: 15px; 
            border-radius: 6px; 
            margin: 20px 0;
            text-align: center;
            font-weight: bold;
            border: 2px solid #16a34a;
            font-size: 18px;
        }
        .summary-box {
            background-color: #e0f2fe;
            border: 1px solid #0891b2;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .next-steps {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .contact-info {
            background-color: white;
            border: 1px solid #d1d5db;
            padding: 20px;
            border-radius: 6px;
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
        .highlight {
            color: #16a34a;
            font-weight: bold;
        }
        .checkmark {
            color: #16a34a;
            font-weight: bold;
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚐 FiveStars Transport</h1>
        <h2>Group Quote Request Received</h2>
        <p style="margin: 10px 0 0 0; font-size: 16px; opacity: 0.9;">Thank you for choosing us for your group transportation needs!</p>
    </div>
    
    <div class="content">
        <div class="reference">
            Your Reference Number: {{ $referenceNumber }}
        </div>

        <p>Dear {{ $data['contact_name'] }},</p>
        
        <p>Thank you for submitting your group quote request! We've received all your details and our team is already working on preparing a customized quote for your group.</p>

        <div class="summary-box">
            <h3 style="margin-top: 0; color: #0891b2;">📋 Your Request Summary</h3>
            <p><strong>Group Size:</strong> {{ $data['group_size'] }} passengers</p>
            <p><strong>Event Date:</strong> {{ \Carbon\Carbon::parse($data['event_date'])->format('l, F j, Y') }}</p>
            <p><strong>Service Type:</strong> {{ $data['service_type'] }}</p>
            <p><strong>Contact Email:</strong> {{ $contact->email }}</p>
            @if($data['phone'])<p><strong>Phone:</strong> {{ $data['phone'] }}</p>@endif
        </div>

        <div class="next-steps">
            <h3 style="margin-top: 0; color: #d97706;">⏰ What Happens Next?</h3>
            <p><span class="checkmark">✓</span><strong>Within 4 hours:</strong> You'll receive an acknowledgment from our team</p>
            <p><span class="checkmark">✓</span><strong>Within 24 hours:</strong> We'll send you a detailed quote including:</p>
            <ul style="margin: 10px 0 10px 25px;">
                <li>Customized pricing for your group size</li>
                <li>Available vehicle options and specifications</li>
                <li>Pickup and drop-off logistics</li>
                <li>Additional services and amenities</li>
            </ul>
            <p><span class="checkmark">✓</span><strong>Personal consultation:</strong> Our team may call to discuss specific requirements</p>
        </div>

        <div class="contact-info">
            <h3 style="margin-top: 0; color: #374151;">📞 Need to Reach Us?</h3>
            <p>If you have any questions or need to make changes to your request, please don't hesitate to contact us:</p>
            <p><strong>Email:</strong> <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a></p>
            <p><strong>Reference your quote number:</strong> <span class="highlight">{{ $referenceNumber }}</span></p>
            <p><em>Including your reference number helps us locate your request quickly!</em></p>
        </div>

        <p>We appreciate your interest in FiveStars Transport and look forward to providing exceptional group transportation service for your event.</p>
        
        <p>Best regards,<br>
        <strong>The FiveStars Transport Team</strong></p>
    </div>

    <div class="footer">
        <p><strong>FiveStars Transport</strong> - Premium Group Transportation Services</p>
        <p style="margin-top: 10px;">
            <small>This is an automated confirmation. Please do not reply to this email directly.</small>
        </p>
    </div>
</body>
</html>