<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Booking $booking
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Booking Confirmed - ' . $this->booking->booking_number,
            from: [
                'address' => config('services.sendgrid.from_email'),
                'name' => config('services.sendgrid.from_name'),
            ],
            replyTo: [
                'address' => config('services.sendgrid.from_email'),
                'name' => config('services.sendgrid.from_name'),
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            html: 'emails.booking-confirmation',
            text: 'emails.booking-confirmation-text',
            with: [
                'booking' => $this->booking,
                'customer' => $this->booking->customer,
                'service' => $this->booking->serviceType,
                'vehicle' => $this->booking->vehicleType,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
