<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Booking Confirmed - {{ $booking->booking_number }}</title>
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
            background-color: #059669; 
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
        .booking-details { 
            background-color: white; 
            padding: 25px; 
            border-radius: 8px; 
            margin: 20px 0;
            border: 2px solid #059669;
        }
        .detail-row { 
            display: flex; 
            justify-content: space-between; 
            padding: 8px 0; 
            border-bottom: 1px solid #e2e8f0;
        }
        .detail-row:last-child { 
            border-bottom: none;
        }
        .detail-label { 
            font-weight: bold; 
            color: #374151;
            flex: 1;
        }
        .detail-value { 
            flex: 2; 
            text-align: right;
        }
        .status-badge { 
            background-color: #d1fae5; 
            color: #065f46; 
            padding: 8px 16px; 
            border-radius: 20px; 
            font-weight: bold; 
            display: inline-block;
            margin: 10px 0;
        }
        .payment-info { 
            background-color: #fef3c7; 
            padding: 20px; 
            border-radius: 4px; 
            margin: 20px 0;
            border-left: 4px solid #f59e0b;
        }
        .contact-info { 
            background-color: #e0f2fe; 
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
        .flight-info { 
            background-color: #ede9fe; 
            padding: 15px; 
            border-radius: 4px; 
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>🚐 FiveStars Transport</h2>
        <h3>✅ Booking Confirmed!</h3>
    </div>
    
    <div class="content">
        <p>Dear {{ $customer->first_name ?? $customer->name }},</p>
        
        <p>Great news! Your transportation booking has been confirmed. Here are your booking details:</p>

        <div class="booking-details">
            <div style="text-align: center; margin-bottom: 20px;">
                <h3 style="color: #059669; margin: 0;">{{ $booking->booking_number }}</h3>
                <span class="status-badge">{{ ucfirst($booking->status) }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Service Type:</span>
                <span class="detail-value">{{ $booking->service_name }} ({{ ucfirst(str_replace('-', ' ', $booking->trip_type)) }})</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Vehicle:</span>
                <span class="detail-value">{{ $vehicle->name ?? 'Standard Vehicle' }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Pickup Location:</span>
                <span class="detail-value">{{ $booking->pickup_location }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Dropoff Location:</span>
                <span class="detail-value">{{ $booking->dropoff_location }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Pickup Date & Time:</span>
                <span class="detail-value">{{ $booking->pickup_date_time->format('M j, Y \a\t g:i A') }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Passengers:</span>
                <span class="detail-value">{{ $booking->passengers }} {{ Str::plural('person', $booking->passengers) }}</span>
            </div>

            @if($booking->child_seats > 0)
            <div class="detail-row">
                <span class="detail-label">Child Seats:</span>
                <span class="detail-value">{{ $booking->child_seats }}</span>
            </div>
            @endif

            @if($booking->wheelchair_accessible)
            <div class="detail-row">
                <span class="detail-label">Accessibility:</span>
                <span class="detail-value">Wheelchair Accessible</span>
            </div>
            @endif

            <div class="detail-row" style="border-top: 2px solid #059669; margin-top: 15px; padding-top: 15px; font-size: 18px;">
                <span class="detail-label">Total Amount:</span>
                <span class="detail-value"><strong>{{ $booking->currency }} ${{ number_format($booking->total_price, 2) }}</strong></span>
            </div>
        </div>

        @if($booking->payment_status !== 'paid')
        <div class="payment-info">
            <h4>💳 Payment Information</h4>
            <p><strong>Payment Status:</strong> {{ ucfirst($booking->payment_status) }}</p>
            @if($booking->payment_status === 'pending')
            <p>Your payment is being processed. You will receive a confirmation once it's completed.</p>
            @endif
        </div>
        @endif

        @if($booking->arrival_flight_info || $booking->departure_flight_info)
        <div class="flight-info">
            <h4>✈️ Flight Information</h4>
            @if($booking->arrival_flight_info)
            <p><strong>Arrival Flight:</strong> {{ $booking->arrival_flight_info['airline'] ?? '' }} {{ $booking->arrival_flight_info['flightNumber'] ?? '' }} 
            on {{ $booking->arrival_flight_info['date'] ?? '' }} at {{ $booking->arrival_flight_info['time'] ?? '' }}</p>
            @endif
            @if($booking->departure_flight_info)
            <p><strong>Departure Flight:</strong> {{ $booking->departure_flight_info['airline'] ?? '' }} {{ $booking->departure_flight_info['flightNumber'] ?? '' }} 
            on {{ $booking->departure_flight_info['date'] ?? '' }} at {{ $booking->departure_flight_info['time'] ?? '' }}</p>
            @endif
        </div>
        @endif

        @if($booking->special_requests)
        <div class="contact-info">
            <h4>📝 Special Requests</h4>
            <p>{{ $booking->special_requests }}</p>
        </div>
        @endif

        <div class="contact-info">
            <h4>📞 Important Information</h4>
            <ul>
                <li><strong>Booking Reference:</strong> {{ $booking->booking_number }}</li>
                <li><strong>Contact Email:</strong> {{ config('services.sendgrid.from_email') }}</li>
                <li><strong>Pickup Time:</strong> Please be ready 15 minutes before scheduled time</li>
                <li><strong>Driver Contact:</strong> Our driver will contact you 30 minutes before pickup</li>
            </ul>
        </div>

        <p>Thank you for choosing FiveStars Transport! We look forward to providing you with excellent service.</p>

        <p>Safe travels,<br>
        <strong>The FiveStars Transport Team</strong></p>
    </div>

    <div class="footer">
        <p>Questions about your booking? Reply to this email or contact us directly.</p>
        <p><small>© {{ date('Y') }} FiveStars Transport. Your trusted transportation partner in Cancun.</small></p>
    </div>
</body>
</html>