<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Contact extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'status',
        'source',
        'metadata',
        'read_at',
        'replied_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
        'read_at' => 'datetime',
        'replied_at' => 'datetime',
    ];

    /**
     * Mark contact as read
     */
    public function markAsRead(): void
    {
        if (!$this->read_at) {
            $this->update([
                'status' => 'read',
                'read_at' => now()
            ]);
        }
    }

    /**
     * Mark contact as replied
     */
    public function markAsReplied(): void
    {
        $this->update([
            'status' => 'replied',
            'replied_at' => now()
        ]);
    }

    /**
     * Scope for filtering by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for recent contacts
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Get formatted contact info for admin
     */
    public function toAdminResponse(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'subject' => $this->subject,
            'message' => $this->message,
            'status' => $this->status,
            'source' => $this->source,
            'created_at' => $this->created_at->toISOString(),
            'read_at' => $this->read_at?->toISOString(),
            'replied_at' => $this->replied_at?->toISOString(),
            'is_new' => $this->status === 'new',
            'days_old' => $this->created_at->diffInDays(now()),
        ];
    }
}
