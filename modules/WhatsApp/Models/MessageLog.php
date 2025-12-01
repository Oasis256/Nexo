<?php

namespace Modules\WhatsApp\Models;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\WhatsApp\Enums\MessageStatus;
use Modules\WhatsApp\Enums\MessageType;
use Modules\WhatsApp\Enums\RecipientType;

/**
 * WhatsApp Message Log Model
 * 
 * @property int $id
 * @property string|null $whatsapp_message_id
 * @property int|null $template_id
 * @property string $recipient_phone
 * @property string|null $recipient_name
 * @property RecipientType $recipient_type
 * @property int|null $recipient_id
 * @property MessageType $message_type
 * @property string $content
 * @property MessageStatus $status
 * @property string|null $error_message
 * @property string|null $error_code
 * @property string|null $related_type
 * @property int|null $related_id
 * @property array|null $meta
 * @property \Carbon\Carbon|null $sent_at
 * @property \Carbon\Carbon|null $delivered_at
 * @property \Carbon\Carbon|null $read_at
 * @property int|null $author
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class MessageLog extends Model
{
    protected $table = 'nexopos_whatsapp_logs';

    protected $fillable = [
        'whatsapp_message_id',
        'template_id',
        'recipient_phone',
        'recipient_name',
        'recipient_type',
        'recipient_id',
        'message_type',
        'content',
        'status',
        'error_message',
        'error_code',
        'related_type',
        'related_id',
        'meta',
        'sent_at',
        'delivered_at',
        'read_at',
        'author',
    ];

    protected $casts = [
        'recipient_type' => RecipientType::class,
        'message_type' => MessageType::class,
        'status' => MessageStatus::class,
        'meta' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    /**
     * Get the template used for this message
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class, 'template_id');
    }

    /**
     * Get the recipient (polymorphic - Customer or User)
     */
    public function recipient(): MorphTo
    {
        return $this->morphTo('recipient', 'recipient_type', 'recipient_id');
    }

    /**
     * Get customer if recipient is customer
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'recipient_id');
    }

    /**
     * Get user if recipient is user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /**
     * Get the related entity (polymorphic)
     */
    public function related(): MorphTo
    {
        return $this->morphTo('related', 'related_type', 'related_id');
    }

    /**
     * Get the author who sent this message
     */
    public function authorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author');
    }

    /**
     * Scope: Filter by status
     */
    public function scopeWithStatus($query, MessageStatus $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Failed messages
     */
    public function scopeFailed($query)
    {
        return $query->where('status', MessageStatus::FAILED);
    }

    /**
     * Scope: Pending messages
     */
    public function scopePending($query)
    {
        return $query->where('status', MessageStatus::PENDING);
    }

    /**
     * Scope: Sent messages (including delivered/read)
     */
    public function scopeSent($query)
    {
        return $query->whereIn('status', [
            MessageStatus::SENT,
            MessageStatus::DELIVERED,
            MessageStatus::READ,
        ]);
    }

    /**
     * Scope: For a specific recipient phone
     */
    public function scopeForPhone($query, string $phone)
    {
        return $query->where('recipient_phone', $phone);
    }

    /**
     * Scope: For a specific related entity
     */
    public function scopeForRelated($query, string $type, int $id)
    {
        return $query->where('related_type', $type)
                     ->where('related_id', $id);
    }

    /**
     * Scope: Messages sent today
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope: Messages in date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Mark message as sent
     */
    public function markAsSent(string $messageId): self
    {
        $this->update([
            'whatsapp_message_id' => $messageId,
            'status' => MessageStatus::SENT,
            'sent_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark message as delivered
     */
    public function markAsDelivered(): self
    {
        $this->update([
            'status' => MessageStatus::DELIVERED,
            'delivered_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark message as read
     */
    public function markAsRead(): self
    {
        $this->update([
            'status' => MessageStatus::READ,
            'read_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark message as failed
     */
    public function markAsFailed(string $errorMessage, ?string $errorCode = null): self
    {
        $this->update([
            'status' => MessageStatus::FAILED,
            'error_message' => $errorMessage,
            'error_code' => $errorCode,
        ]);

        return $this;
    }

    /**
     * Get status badge HTML
     */
    public function getStatusBadgeAttribute(): string
    {
        $colors = [
            'pending' => 'bg-yellow-100 text-yellow-800',
            'sent' => 'bg-blue-100 text-blue-800',
            'delivered' => 'bg-green-100 text-green-800',
            'read' => 'bg-green-200 text-green-900',
            'failed' => 'bg-red-100 text-red-800',
        ];

        $color = $colors[$this->status->value] ?? 'bg-gray-100 text-gray-800';

        return sprintf(
            '<span class="px-2 py-1 text-xs rounded-full %s">%s</span>',
            $color,
            $this->status->label()
        );
    }
}
