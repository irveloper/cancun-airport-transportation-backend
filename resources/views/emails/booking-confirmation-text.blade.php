FiveStars Transport - Booking Confirmed!

Dear {{ $customer->first_name ?? $customer->name }},

Great news! Your transportation booking has been confirmed.

BOOKING DETAILS
================
Booking Number: {{ $booking->booking_number }}
Status: {{ ucfirst($booking->status) }}

Service Type: {{ $booking->service_name }} ({{ ucfirst(str_replace('-', ' ', $booking->trip_type)) }})
Vehicle: {{ $vehicle->name ?? 'Standard Vehicle' }}

Pickup Location: {{ $booking->pickup_location }}
Dropoff Location: {{ $booking->dropoff_location }}
Pickup Date & Time: {{ $booking->pickup_date_time->format('M j, Y \a\t g:i A') }}

Passengers: {{ $booking->passengers }} {{ Str::plural('person', $booking->passengers) }}
@if($booking->child_seats > 0)Child Seats: {{ $booking->child_seats }}
@endif
@if($booking->wheelchair_accessible)Accessibility: Wheelchair Accessible
@endif

TOTAL AMOUNT: {{ $booking->currency }} ${{ number_format($booking->total_price, 2) }}

@if($booking->payment_status !== 'paid')
PAYMENT INFORMATION
===================
Payment Status: {{ ucfirst($booking->payment_status) }}
@if($booking->payment_status === 'pending')Your payment is being processed. You will receive a confirmation once it's completed.
@endif

@endif
@if($booking->arrival_flight_info || $booking->departure_flight_info)
FLIGHT INFORMATION
==================
@if($booking->arrival_flight_info)Arrival Flight: {{ $booking->arrival_flight_info['airline'] ?? '' }} {{ $booking->arrival_flight_info['flightNumber'] ?? '' }} on {{ $booking->arrival_flight_info['date'] ?? '' }} at {{ $booking->arrival_flight_info['time'] ?? '' }}
@endif
@if($booking->departure_flight_info)Departure Flight: {{ $booking->departure_flight_info['airline'] ?? '' }} {{ $booking->departure_flight_info['flightNumber'] ?? '' }} on {{ $booking->departure_flight_info['date'] ?? '' }} at {{ $booking->departure_flight_info['time'] ?? '' }}
@endif

@endif
@if($booking->special_requests)
SPECIAL REQUESTS
================
{{ $booking->special_requests }}

@endif
IMPORTANT INFORMATION
=====================
- Booking Reference: {{ $booking->booking_number }}
- Contact Email: {{ config('services.sendgrid.from_email') }}
- Pickup Time: Please be ready 15 minutes before scheduled time
- Driver Contact: Our driver will contact you 30 minutes before pickup

Thank you for choosing FiveStars Transport! We look forward to providing you with excellent service.

Safe travels,
The FiveStars Transport Team

Questions about your booking? Reply to this email or contact us directly.
© {{ date('Y') }} FiveStars Transport. Your trusted transportation partner in Cancun.