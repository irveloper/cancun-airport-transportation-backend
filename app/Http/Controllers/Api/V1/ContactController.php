<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class ContactController extends BaseApiController
{
    /**
     * Submit a group quote request
     */
    public function storeGroupQuote(Request $request): JsonResponse
    {
        // Rate limiting: max 5 group quote requests per hour per IP
        $key = 'group-quote:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return $this->errorResponse('Too many group quote requests. Please try again later.', 429);
        }

        // Validation
        $validator = Validator::make($request->all(), [
            'contact_name' => 'required|string|max:100|min:2',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'group_size' => 'required|integer|min:1|max:500',
            'event_date' => 'required|date|after:today',
            'service_type' => 'required|string|max:100',
            'event_details' => 'required|string|max:2000|min:10',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $data = $validator->validated();

            // Add metadata
            $metadata = [
                'user_agent' => $request->userAgent(),
                'referrer' => $request->header('referer'),
                'timestamp' => now()->toISOString(),
                'request_type' => 'group_quote'
            ];

            // Create contact record with group-specific data
            $contact = Contact::create([
                'name' => $data['contact_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'subject' => 'Group Quote Request - ' . $data['service_type'],
                'message' => $this->formatGroupQuoteMessage($data),
                'source' => 'website_group_quote',
                'metadata' => $metadata,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => 'new',
            ]);

            // Increment rate limiter
            RateLimiter::hit($key, 3600); // 1 hour

            Log::info('Group quote request submitted', [
                'contact_id' => $contact->id,
                'email' => $contact->email,
                'group_size' => $data['group_size'],
                'event_date' => $data['event_date'],
                'service_type' => $data['service_type'],
                'ip' => $request->ip()
            ]);

            // Send email notifications
            $this->sendGroupQuoteNotifications($contact, $data);

            return $this->successResponse([
                'message' => 'Thank you for your group quote request! We\'ll get back to you within 24 hours with a detailed quote.',
                'contact_id' => $contact->id,
                'reference_number' => 'GQ' . str_pad($contact->id, 6, '0', STR_PAD_LEFT)
            ], 'Group quote request submitted successfully', 201);

        } catch (\Exception $e) {
            Log::error('Group quote request submission failed', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
                'ip' => $request->ip()
            ]);

            return $this->errorResponse('Failed to submit group quote request. Please try again.', 500);
        }
    }

    /**
     * Submit a new contact form
     */
    public function store(Request $request): JsonResponse
    {
        // Rate limiting: max 3 submissions per hour per IP
        $key = 'contact-form:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return $this->errorResponse('Too many contact attempts. Please try again later.', 429);
        }

        // Validation
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|min:2',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'subject' => 'required|string|max:200',
            'message' => 'required|string|max:2000|min:10',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $data = $validator->validated();

            // Add metadata
            $metadata = [
                'user_agent' => $request->userAgent(),
                'referrer' => $request->header('referer'),
                'timestamp' => now()->toISOString(),
            ];

            // Create contact record
            $contact = Contact::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'subject' => $data['subject'],
                'message' => $data['message'],
                'source' => 'website',
                'metadata' => $metadata,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => 'new',
            ]);

            // Increment rate limiter
            RateLimiter::hit($key, 3600); // 1 hour

            Log::info('Contact form submitted', [
                'contact_id' => $contact->id,
                'email' => $contact->email,
                'subject' => $contact->subject,
                'ip' => $request->ip()
            ]);

            // Send email notifications
            $this->sendNotifications($contact);

            return $this->successResponse([
                'message' => 'Thank you for your message! We\'ll get back to you soon.',
                'contact_id' => $contact->id,
                'reference_number' => 'CT' . str_pad($contact->id, 6, '0', STR_PAD_LEFT)
            ], 'Contact form submitted successfully', 201);

        } catch (\Exception $e) {
            Log::error('Contact form submission failed', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
                'ip' => $request->ip()
            ]);

            return $this->errorResponse('Failed to submit contact form. Please try again.', 500);
        }
    }

    /**
     * Get all contacts (Admin only)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Contact::query()->orderBy('created_at', 'desc');

            // Filter by status
            if ($request->has('status')) {
                $query->byStatus($request->status);
            }

            // Filter by date range
            if ($request->has('from_date')) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }

            if ($request->has('to_date')) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            // Search by name or email
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('subject', 'like', "%{$search}%");
                });
            }

            $contacts = $query->paginate($request->get('per_page', 15));

            return $this->successResponse([
                'contacts' => $contacts->map(fn($contact) => $contact->toAdminResponse()),
                'meta' => [
                    'current_page' => $contacts->currentPage(),
                    'total' => $contacts->total(),
                    'per_page' => $contacts->perPage(),
                    'last_page' => $contacts->lastPage(),
                ],
                'summary' => [
                    'new' => Contact::byStatus('new')->count(),
                    'read' => Contact::byStatus('read')->count(),
                    'replied' => Contact::byStatus('replied')->count(),
                    'resolved' => Contact::byStatus('resolved')->count(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve contacts', [
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse('Failed to retrieve contacts', 500);
        }
    }

    /**
     * Get specific contact (Admin only)
     */
    public function show($id): JsonResponse
    {
        try {
            $contact = Contact::findOrFail($id);
            
            // Mark as read
            $contact->markAsRead();

            return $this->successResponse([
                'contact' => $contact->toAdminResponse()
            ]);

        } catch (\Exception $e) {
            return $this->notFoundResponse('Contact');
        }
    }

    /**
     * Update contact status (Admin only)
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:new,read,in_progress,replied,resolved',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $contact = Contact::findOrFail($id);
            
            $contact->update([
                'status' => $request->status
            ]);

            if ($request->status === 'replied') {
                $contact->markAsReplied();
            }

            Log::info('Contact status updated', [
                'contact_id' => $contact->id,
                'old_status' => $contact->getOriginal('status'),
                'new_status' => $request->status
            ]);

            return $this->successResponse([
                'contact' => $contact->toAdminResponse()
            ], 'Contact status updated successfully');

        } catch (\Exception $e) {
            return $this->notFoundResponse('Contact');
        }
    }

    /**
     * Get contact form statistics (Admin only)
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = [
                'total' => Contact::count(),
                'new' => Contact::byStatus('new')->count(),
                'in_progress' => Contact::byStatus('in_progress')->count(),
                'replied' => Contact::byStatus('replied')->count(),
                'resolved' => Contact::byStatus('resolved')->count(),
                'this_week' => Contact::recent(7)->count(),
                'this_month' => Contact::where('created_at', '>=', now()->subMonth())->count(),
                'by_subject' => Contact::selectRaw('subject, count(*) as count')
                    ->groupBy('subject')
                    ->orderBy('count', 'desc')
                    ->limit(10)
                    ->get(),
            ];

            return $this->successResponse($stats);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve contact stats', [
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse('Failed to retrieve statistics', 500);
        }
    }

    /**
     * Send email notifications (private method)
     */
    private function sendNotifications(Contact $contact): void
    {
        $adminEmailSent = false;
        $customerEmailSent = false;
        
        try {
            // Get admin email from environment variable
            $adminEmail = env('ADMIN_EMAIL', config('services.sendgrid.from_email'));
            
            // Send notification to admin
            if ($adminEmail) {
                try {
                    Mail::to($adminEmail)->send(new \App\Mail\ContactFormSubmitted($contact));
                    $adminEmailSent = true;
                    
                    Log::info('✅ Contact form admin notification email sent successfully', [
                        'contact_id' => $contact->id,
                        'admin_email' => $adminEmail,
                        'subject' => $contact->subject,
                        'reference_number' => 'CT' . str_pad($contact->id, 6, '0', STR_PAD_LEFT),
                        'status' => 'sent'
                    ]);
                } catch (\Exception $e) {
                    Log::error('❌ Failed to send contact form admin notification email', [
                        'contact_id' => $contact->id,
                        'admin_email' => $adminEmail,
                        'error' => $e->getMessage(),
                        'status' => 'failed'
                    ]);
                }
            }
            
            // Send auto-reply to customer
            try {
                Mail::to($contact->email)->send(new \App\Mail\ContactAutoReply($contact));
                $customerEmailSent = true;
                
                Log::info('✅ Contact form customer auto-reply email sent successfully', [
                    'contact_id' => $contact->id,
                    'customer_email' => $contact->email,
                    'subject' => $contact->subject,
                    'reference_number' => 'CT' . str_pad($contact->id, 6, '0', STR_PAD_LEFT),
                    'status' => 'sent'
                ]);
            } catch (\Exception $e) {
                Log::error('❌ Failed to send contact form customer auto-reply email', [
                    'contact_id' => $contact->id,
                    'customer_email' => $contact->email,
                    'error' => $e->getMessage(),
                    'status' => 'failed'
                ]);
            }
            
            // Summary log
            Log::info('📧 Contact form email notification summary', [
                'contact_id' => $contact->id,
                'reference_number' => 'CT' . str_pad($contact->id, 6, '0', STR_PAD_LEFT),
                'admin_email_sent' => $adminEmailSent,
                'customer_email_sent' => $customerEmailSent,
                'total_emails_sent' => ($adminEmailSent ? 1 : 0) + ($customerEmailSent ? 1 : 0),
                'subject' => $contact->subject
            ]);
            
        } catch (\Exception $e) {
            Log::error('💥 Critical failure in contact form notification system', [
                'contact_id' => $contact->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'admin_email_sent' => $adminEmailSent,
                'customer_email_sent' => $customerEmailSent
            ]);
        }
    }

    /**
     * Format group quote data into a readable message
     */
    private function formatGroupQuoteMessage(array $data): string
    {
        return sprintf(
            "GROUP QUOTE REQUEST\n\n" .
            "Contact: %s\n" .
            "Email: %s\n" .
            "Phone: %s\n" .
            "Group Size: %d passengers\n" .
            "Event Date: %s\n" .
            "Service Type: %s\n\n" .
            "Event Details:\n%s",
            $data['contact_name'],
            $data['email'],
            $data['phone'] ?? 'Not provided',
            $data['group_size'],
            $data['event_date'],
            $data['service_type'],
            $data['event_details']
        );
    }

    /**
     * Send email notifications for group quote requests
     */
    private function sendGroupQuoteNotifications(Contact $contact, array $data): void
    {
        $adminEmailSent = false;
        $customerEmailSent = false;
        
        try {
            // Get admin email from environment variable
            $adminEmail = env('ADMIN_EMAIL', config('services.sendgrid.from_email'));
            
            // Send notification to admin
            if ($adminEmail) {
                try {
                    Mail::to($adminEmail)->send(new \App\Mail\GroupQuoteSubmitted($contact, $data));
                    $adminEmailSent = true;
                    
                    Log::info('✅ Group quote admin notification email sent successfully', [
                        'contact_id' => $contact->id,
                        'admin_email' => $adminEmail,
                        'group_size' => $data['group_size'],
                        'event_date' => $data['event_date'],
                        'reference_number' => 'GQ' . str_pad($contact->id, 6, '0', STR_PAD_LEFT),
                        'status' => 'sent'
                    ]);
                } catch (\Exception $e) {
                    Log::error('❌ Failed to send group quote admin notification email', [
                        'contact_id' => $contact->id,
                        'admin_email' => $adminEmail,
                        'error' => $e->getMessage(),
                        'status' => 'failed'
                    ]);
                }
            }
            
            // Send auto-reply to customer
            try {
                Mail::to($contact->email)->send(new \App\Mail\GroupQuoteAutoReply($contact, $data));
                $customerEmailSent = true;
                
                Log::info('✅ Group quote customer auto-reply email sent successfully', [
                    'contact_id' => $contact->id,
                    'customer_email' => $contact->email,
                    'group_size' => $data['group_size'],
                    'reference_number' => 'GQ' . str_pad($contact->id, 6, '0', STR_PAD_LEFT),
                    'status' => 'sent'
                ]);
            } catch (\Exception $e) {
                Log::error('❌ Failed to send group quote customer auto-reply email', [
                    'contact_id' => $contact->id,
                    'customer_email' => $contact->email,
                    'error' => $e->getMessage(),
                    'status' => 'failed'
                ]);
            }
            
            // Summary log
            Log::info('📧 Group quote email notification summary', [
                'contact_id' => $contact->id,
                'reference_number' => 'GQ' . str_pad($contact->id, 6, '0', STR_PAD_LEFT),
                'admin_email_sent' => $adminEmailSent,
                'customer_email_sent' => $customerEmailSent,
                'total_emails_sent' => ($adminEmailSent ? 1 : 0) + ($customerEmailSent ? 1 : 0),
                'group_size' => $data['group_size'],
                'service_type' => $data['service_type']
            ]);
            
        } catch (\Exception $e) {
            Log::error('💥 Critical failure in group quote notification system', [
                'contact_id' => $contact->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'admin_email_sent' => $adminEmailSent,
                'customer_email_sent' => $customerEmailSent
            ]);
        }
    }
}
