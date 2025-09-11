<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New Group Quote Request</title>
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
            padding: 20px; 
            text-align: center; 
            border-radius: 8px 8px 0 0;
        }
        .content { 
            background-color: #f8fafc; 
            padding: 30px; 
            border-radius: 0 0 8px 8px;
        }
        .field-group { 
            margin-bottom: 20px; 
            border-bottom: 1px solid #e2e8f0; 
            padding-bottom: 15px;
        }
        .field-label { 
            font-weight: bold; 
            color: #374151; 
            margin-bottom: 5px;
        }
        .field-value { 
            background-color: white; 
            padding: 10px; 
            border-radius: 4px; 
            border: 1px solid #d1d5db;
        }
        .message-content { 
            white-space: pre-wrap; 
            max-height: 200px; 
            overflow-y: auto;
        }
        .footer { 
            margin-top: 30px; 
            padding-top: 20px; 
            border-top: 2px solid #e2e8f0; 
            font-size: 14px; 
            color: #6b7280;
        }
        .reference { 
            background-color: #dcfce7; 
            padding: 10px; 
            border-radius: 4px; 
            margin: 20px 0;
            text-align: center;
            font-weight: bold;
            border: 1px solid #16a34a;
        }
        .priority-info {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .group-details {
            background-color: #e0f2fe;
            border-left: 4px solid #0891b2;
            padding: 15px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>🚐 FiveStars Transport</h2>
        <h3>New Group Quote Request</h3>
    </div>
    
    <div class="content">
        <div class="reference">
            Reference #{{ $referenceNumber }}
        </div>

        <div class="priority-info">
            <strong>⚡ Priority Request:</strong> Group booking for {{ $data['group_size'] }} passengers on {{ \Carbon\Carbon::parse($data['event_date'])->format('M j, Y') }}
        </div>

        <div class="group-details">
            <h4 style="margin-top: 0; color: #0891b2;">📊 Group Information</h4>
            <p><strong>Group Size:</strong> {{ $data['group_size'] }} passengers</p>
            <p><strong>Event Date:</strong> {{ \Carbon\Carbon::parse($data['event_date'])->format('l, M j, Y') }}</p>
            <p><strong>Service Type:</strong> {{ $data['service_type'] }}</p>
        </div>

        <div class="field-group">
            <div class="field-label">Contact Name:</div>
            <div class="field-value">{{ $data['contact_name'] }}</div>
        </div>

        <div class="field-group">
            <div class="field-label">Email:</div>
            <div class="field-value">
                <a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a>
            </div>
        </div>

        @if($data['phone'])
        <div class="field-group">
            <div class="field-label">Phone:</div>
            <div class="field-value">
                <a href="tel:{{ $data['phone'] }}">{{ $data['phone'] }}</a>
            </div>
        </div>
        @endif

        <div class="field-group">
            <div class="field-label">Event Details:</div>
            <div class="field-value message-content">{{ $data['event_details'] }}</div>
        </div>

        <div class="field-group">
            <div class="field-label">Submitted:</div>
            <div class="field-value">{{ $contact->created_at->format('M j, Y \a\t g:i A') }}</div>
        </div>

        @if($contact->ip_address)
        <div class="field-group">
            <div class="field-label">IP Address:</div>
            <div class="field-value">{{ $contact->ip_address }}</div>
        </div>
        @endif
    </div>

    <div class="footer">
        <p><strong>⏰ Next Steps for Group Quote:</strong></p>
        <ul>
            <li><strong>Within 4 hours:</strong> Send acknowledgment email to customer</li>
            <li><strong>Within 24 hours:</strong> Prepare detailed quote with vehicle options</li>
            <li><strong>Include:</strong> Pricing breakdown, vehicle specifications, pickup/drop-off details</li>
            <li><strong>Follow up:</strong> Call customer to discuss specific requirements</li>
        </ul>
        
        <div style="background-color: #fee2e2; padding: 10px; border-radius: 4px; margin: 15px 0;">
            <strong>📞 Recommended Action:</strong> Call customer within 2 hours for group bookings over 20 passengers
        </div>
        
        <p style="margin-top: 20px;">
            <small>This email was automatically generated by the FiveStars Transport group quote system.</small>
        </p>
    </div>
</body>
</html>