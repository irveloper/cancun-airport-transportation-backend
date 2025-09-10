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
        try {
            // Get admin email from config
            $adminEmail = config('services.sendgrid.from_email'); // Use SendGrid email for now
            
            // Send notification to admin (queued)
            if ($adminEmail) {
                Mail::to($adminEmail)->send(new \App\Mail\ContactFormSubmitted($contact));
                
                Log::info('Admin notification email queued', [
                    'contact_id' => $contact->id,
                    'admin_email' => $adminEmail
                ]);
            }
            
            // Send auto-reply to customer (queued)
            Mail::to($contact->email)->send(new \App\Mail\ContactAutoReply($contact));
            
            Log::info('Customer auto-reply email queued', [
                'contact_id' => $contact->id,
                'customer_email' => $contact->email
            ]);
            
        } catch (\Exception $e) {
            Log::warning('Failed to send contact notifications', [
                'contact_id' => $contact->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
