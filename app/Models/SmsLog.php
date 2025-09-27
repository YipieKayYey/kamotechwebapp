<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsLog extends Model
{
    protected $fillable = [
        'message_id',
        'semaphore_user_id',
        'semaphore_user',
        'account_id',
        'account',
        'recipient',
        'message',
        'sender_name',
        'network',
        'status',
        'type',
        'source',
        'booking_id',
        'message_type',
        'credits_used',
        'raw_response',
        'sent_at',
    ];

    protected $casts = [
        'raw_response' => 'array',
        'sent_at' => 'datetime',
        'credits_used' => 'integer',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function isSuccessful(): bool
    {
        return in_array(strtolower($this->status), ['queued', 'pending', 'sent']);
    }

    public function isFailed(): bool
    {
        return in_array(strtolower($this->status), ['failed', 'refunded']);
    }
}
