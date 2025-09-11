<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Models\Booking;
use App\Models\Customer;
use App\Mail\BookingConfirmation;

class TestEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test 
                            {email : The email address to send test to}
                            {--booking= : Booking ID to use for testing (optional)}
                            {--type=booking : Email type to test (booking, contact)}
                            {--preview : Preview email without sending}
                            {--status : Show email system status}
                            {--queue : Show queue status after sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test email functionality by sending a test email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $bookingId = $this->option('booking');
        $type = $this->option('type');
        $preview = $this->option('preview');
        $showStatus = $this->option('status');
        $showQueue = $this->option('queue');

        // Show email system status if requested
        if ($showStatus) {
            $this->displaySystemStatus();
            if (!$preview) return 0;
        }

        $this->info("🧪 Testing email functionality...");
        $this->info("📧 Recipient: {$email}");
        $this->info("📝 Type: {$type}");
        
        if ($preview) {
            $this->info("👁️  Preview Mode: No emails will be sent");
        }

        try {
            if ($type === 'booking') {
                $this->testBookingEmail($email, $bookingId, $preview);
            } elseif ($type === 'contact') {
                $this->testContactEmail($email, $preview);
            } else {
                $this->error("❌ Invalid email type. Use 'booking' or 'contact'");
                return 1;
            }

            if (!$preview) {
                $this->info("✅ Test email sent successfully!");
                $this->info("📬 Check your inbox (and spam folder) for the test email.");
                
                if ($showQueue) {
                    $this->displayQueueStatus();
                }
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Failed to " . ($preview ? 'preview' : 'send') . " test email: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function testBookingEmail(string $email, ?string $bookingId = null, bool $preview = false)
    {
        if ($bookingId) {
            $booking = Booking::with(['customer', 'serviceType', 'vehicleType'])->find($bookingId);
            if (!$booking) {
                throw new \Exception("Booking with ID {$bookingId} not found");
            }
            $this->info("📋 Using existing booking: {$booking->booking_number}");
        } else {
            // Create a test booking for email testing
            $booking = $this->createTestBooking($email);
            $this->info("📋 " . ($preview ? "Using test booking data: {$booking->booking_number}" : "Created test booking: {$booking->booking_number}"));
        }

        $mailable = new BookingConfirmation($booking);
        
        if ($preview) {
            $this->info("🔍 Email Subject: " . $mailable->envelope()->subject);
            $this->info("📄 Rendering email template...");
            
            try {
                $htmlContent = $mailable->render();
                $this->info("✅ HTML template renders successfully (" . strlen($htmlContent) . " characters)");
                
                // Show first 200 characters of rendered content
                $preview = strip_tags($htmlContent);
                $preview = substr($preview, 0, 200) . (strlen($preview) > 200 ? '...' : '');
                $this->line("📖 Content Preview: " . trim(preg_replace('/\s+/', ' ', $preview)));
                
            } catch (\Exception $e) {
                $this->error("❌ Template rendering failed: " . $e->getMessage());
                throw $e;
            }
        } else {
            \Illuminate\Support\Facades\Mail::to($email)->send($mailable);
        }
    }

    private function testContactEmail(string $email, bool $preview = false)
    {
        // Create a test contact for email testing
        $contact = new \App\Models\Contact([
            'name' => 'Test User',
            'email' => $email,
            'phone' => '+1-555-TEST',
            'subject' => 'Email Test',
            'message' => 'This is a test email sent via Artisan command.',
            'preferred_contact_method' => 'email',
            'status' => 'new'
        ]);

        $mailable = new \App\Mail\ContactAutoReply($contact);
        
        if ($preview) {
            $this->info("🔍 Email Subject: " . $mailable->envelope()->subject);
            $this->info("📄 Rendering contact auto-reply template...");
            
            try {
                $htmlContent = $mailable->render();
                $this->info("✅ HTML template renders successfully (" . strlen($htmlContent) . " characters)");
                
                $preview = strip_tags($htmlContent);
                $preview = substr($preview, 0, 200) . (strlen($preview) > 200 ? '...' : '');
                $this->line("📖 Content Preview: " . trim(preg_replace('/\s+/', ' ', $preview)));
                
            } catch (\Exception $e) {
                $this->error("❌ Template rendering failed: " . $e->getMessage());
                throw $e;
            }
        } else {
            \Illuminate\Support\Facades\Mail::to($email)->send($mailable);
            $this->info("📞 Sent contact auto-reply email");
        }
    }

    private function displaySystemStatus()
    {
        $this->info("📊 Email System Status:");
        $this->info("═══════════════════════════════════════");
        
        // Mail configuration
        $this->info("📧 Mail Driver: " . config('mail.default'));
        $this->info("📬 From Address: " . config('mail.from.address'));
        $this->info("📝 From Name: " . config('mail.from.name'));
        $this->info("🔧 Queue Driver: " . config('queue.default'));
        
        // SendGrid status
        $sendgridConfigured = !empty(config('services.sendgrid.api_key'));
        $this->info("🌐 SendGrid: " . ($sendgridConfigured ? "✅ Configured" : "❌ Not configured"));
        
        // Template status
        $this->line("");
        $this->info("📄 Email Templates:");
        $templates = [
            'booking-confirmation' => 'Booking Confirmation',
            'booking-confirmation-text' => 'Booking Confirmation (Text)',
            'contact-auto-reply' => 'Contact Auto-Reply',
            'contact-auto-reply-text' => 'Contact Auto-Reply (Text)',
            'contact-form-submitted' => 'Contact Form Submitted',
            'contact-form-submitted-text' => 'Contact Form Submitted (Text)'
        ];
        
        foreach ($templates as $template => $name) {
            $exists = view()->exists("emails.{$template}");
            $this->info("  {$name}: " . ($exists ? "✅ Found" : "❌ Missing"));
        }
        
        // Database counts
        $this->line("");
        $this->info("💾 Database Status:");
        $this->info("  Bookings: " . Booking::count());
        $this->info("  Customers: " . Customer::count());
        $this->info("  Contacts: " . \App\Models\Contact::count());
        
        $this->info("═══════════════════════════════════════");
        $this->line("");
    }

    private function displayQueueStatus()
    {
        $this->line("");
        $this->info("⏳ Queue Status:");
        $this->info("═══════════════════════");
        
        if (config('queue.default') === 'database') {
            $pendingJobs = \Illuminate\Support\Facades\DB::table('jobs')->count();
            $failedJobs = \Illuminate\Support\Facades\DB::table('failed_jobs')->count();
            
            $this->info("📋 Pending Jobs: {$pendingJobs}");
            $this->info("❌ Failed Jobs: {$failedJobs}");
            
            if ($pendingJobs > 0) {
                $this->info("💡 Run 'php artisan queue:work' to process pending jobs");
            }
        } else {
            $this->info("🔧 Queue driver: " . config('queue.default'));
            $this->info("💡 Job status depends on your queue configuration");
        }
        
        $this->info("═══════════════════════");
    }

    private function createTestBooking(string $email): Booking
    {
        // Create or find test customer
        $customer = Customer::firstOrCreate(
            ['email' => $email],
            [
                'first_name' => 'Test',
                'last_name' => 'User',
                'phone' => '+1-555-TEST',
                'country' => 'Test Country'
            ]
        );

        // Create test booking
        return Booking::create([
            'booking_number' => 'TEST-' . now()->format('YmdHis'),
            'customer_id' => $customer->id,
            'service_type_id' => 1, // Assuming first service type exists
            'vehicle_type_id' => 1, // Assuming first vehicle type exists
            'service_name' => 'Test Transfer Service',
            'from_location_id' => 1,
            'to_location_id' => 2,
            'pickup_location' => 'Test Pickup Location',
            'dropoff_location' => 'Test Dropoff Location',
            'from_location_type' => 'airport',
            'to_location_type' => 'location',
            'trip_type' => 'arrival',
            'pickup_date_time' => now()->addDays(1),
            'passengers' => 2,
            'child_seats' => 0,
            'wheelchair_accessible' => false,
            'currency' => 'USD',
            'total_price' => 100.00,
            'exchange_rate' => 1.0,
            'special_requests' => 'This is a test booking for email testing.',
            'booking_date' => now(),
            'status' => 'confirmed',
            'payment_status' => 'completed'
        ]);
    }
}
