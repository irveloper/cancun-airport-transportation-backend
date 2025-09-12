FiveStars Transport - Group Quote Request Received

Your Reference Number: {{ $referenceNumber }}

Dear {{ $data['contact_name'] }},

Thank you for submitting your group quote request! We've received all your details and our team is already working on preparing a customized quote for your group.

YOUR REQUEST SUMMARY:
- Group Size: {{ $data['group_size'] }} passengers
- Event Date: {{ \Carbon\Carbon::parse($data['event_date'])->format('l, F j, Y') }}
- Service Type: {{ $data['service_type'] }}
- Contact Email: {{ $contact->email }}
@if($data['phone'])- Phone: {{ $data['phone'] }}@endif

WHAT HAPPENS NEXT?
✓ Within 4 hours: You'll receive an acknowledgment from our team
✓ Within 24 hours: We'll send you a detailed quote including:
  • Customized pricing for your group size
  • Available vehicle options and specifications
  • Pickup and drop-off logistics
  • Additional services and amenities
✓ Personal consultation: Our team may call to discuss specific requirements

NEED TO REACH US?
If you have any questions or need to make changes to your request, please don't hesitate to contact us:

Email: {{ config('mail.from.address') }}
Reference your quote number: {{ $referenceNumber }}

Including your reference number helps us locate your request quickly!

We appreciate your interest in FiveStars Transport and look forward to providing exceptional group transportation service for your event.

Best regards,
The FiveStars Transport Team

---
FiveStars Transport - Premium Group Transportation Services
This is an automated confirmation. Please do not reply to this email directly.