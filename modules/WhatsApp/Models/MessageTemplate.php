<?php

namespace Modules\WhatsApp\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\WhatsApp\Enums\TemplateTarget;

/**
 * WhatsApp Message Template Model
 * 
 * @property int $id
 * @property string $name
 * @property string $label
 * @property string $event
 * @property string $content
 * @property bool $is_active
 * @property TemplateTarget $target
 * @property array $meta
 * @property int $author
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class MessageTemplate extends Model
{
    protected $table = 'nexopos_whatsapp_templates';

    protected $fillable = [
        'name',
        'label',
        'event',
        'content',
        'is_active',
        'target',
        'meta',
        'author',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'target' => TemplateTarget::class,
        'meta' => 'array',
    ];

    /**
     * Available template events
     */
    public static function getAvailableEvents(): array
    {
        return [
            'order.created' => __m('Order Created', 'WhatsApp'),
            'order.payment' => __m('Payment Received', 'WhatsApp'),
            'order.refund' => __m('Order Refunded', 'WhatsApp'),
            'order.voided' => __m('Order Voided', 'WhatsApp'),
            'order.delivery_update' => __m('Delivery Status Update', 'WhatsApp'),
            'customer.created' => __m('Customer Registered', 'WhatsApp'),
            'customer.reward' => __m('Customer Reward', 'WhatsApp'),
            'low_stock.alert' => __m('Low Stock Alert', 'WhatsApp'),
            'due_orders.reminder' => __m('Payment Due Reminder', 'WhatsApp'),
            'custom' => __m('Custom/Manual', 'WhatsApp'),
        ];
    }

    /**
     * Available placeholders for templates
     */
    public static function getAvailablePlaceholders(): array
    {
        return [
            // Customer placeholders
            '{customer_name}' => __m('Customer full name', 'WhatsApp'),
            '{customer_first_name}' => __m('Customer first name', 'WhatsApp'),
            '{customer_phone}' => __m('Customer phone', 'WhatsApp'),
            '{customer_email}' => __m('Customer email', 'WhatsApp'),
            
            // Order placeholders
            '{order_id}' => __m('Order ID', 'WhatsApp'),
            '{order_code}' => __m('Order code', 'WhatsApp'),
            '{order_total}' => __m('Order total amount', 'WhatsApp'),
            '{order_subtotal}' => __m('Order subtotal', 'WhatsApp'),
            '{order_tax}' => __m('Order tax amount', 'WhatsApp'),
            '{order_discount}' => __m('Order discount', 'WhatsApp'),
            '{order_change}' => __m('Order change', 'WhatsApp'),
            '{order_tendered}' => __m('Order tendered amount', 'WhatsApp'),
            '{order_products}' => __m('Order products list', 'WhatsApp'),
            '{order_date}' => __m('Order date', 'WhatsApp'),
            '{order_status}' => __m('Order status', 'WhatsApp'),
            '{delivery_status}' => __m('Delivery status', 'WhatsApp'),
            
            // Payment placeholders
            '{payment_amount}' => __m('Payment amount', 'WhatsApp'),
            '{payment_method}' => __m('Payment method', 'WhatsApp'),
            '{payment_date}' => __m('Payment date', 'WhatsApp'),
            
            // Refund placeholders
            '{refund_amount}' => __m('Refund amount', 'WhatsApp'),
            '{refund_reason}' => __m('Refund reason', 'WhatsApp'),
            
            // Product/Stock placeholders
            '{product_name}' => __m('Product name', 'WhatsApp'),
            '{product_sku}' => __m('Product SKU', 'WhatsApp'),
            '{product_quantity}' => __m('Product quantity', 'WhatsApp'),
            '{low_stock_products}' => __m('Low stock products list', 'WhatsApp'),
            
            // Store placeholders
            '{store_name}' => __m('Store name', 'WhatsApp'),
            '{store_phone}' => __m('Store phone', 'WhatsApp'),
            '{store_address}' => __m('Store address', 'WhatsApp'),
            
            // Date/Time
            '{current_date}' => __m('Current date', 'WhatsApp'),
            '{current_time}' => __m('Current time', 'WhatsApp'),
        ];
    }

    /**
     * Get the author of this template
     */
    public function authorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author');
    }

    /**
     * Scope: Active templates only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: By event type
     */
    public function scopeForEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    /**
     * Scope: By target audience
     */
    public function scopeForTarget($query, TemplateTarget $target)
    {
        return $query->where(function ($q) use ($target) {
            $q->where('target', $target)
              ->orWhere('target', TemplateTarget::BOTH);
        });
    }

    /**
     * Replace placeholders in template content
     */
    public function render(array $data): string
    {
        $content = $this->content;

        foreach ($data as $key => $value) {
            $placeholder = '{' . $key . '}';
            $content = str_replace($placeholder, (string) $value, $content);
        }

        // Remove any unreplaced placeholders
        $content = preg_replace('/\{[a-z_]+\}/', '', $content);

        return trim($content);
    }
}
