<?php
/**
 * Voucher Model
 * @package GiftVouchers
 */

namespace Modules\GiftVouchers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;
use App\Models\Customer;
use App\Models\Order;
use Modules\GiftVouchers\Enums\VoucherStatus;

class Voucher extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'nexopos_gift_vouchers';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uuid',
        'code',
        'template_id',
        'purchaser_id',
        'purchase_order_id',
        'total_value',
        'remaining_value',
        'points_awarded',
        'status',
        'expires_at',
        'qr_redemption_key',
        'qr_key_expires_at',
        'qr_image_path',
        'deferred_transaction_id',
        'author',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'total_value' => 'decimal:5',
        'remaining_value' => 'decimal:5',
        'points_awarded' => 'decimal:5',
        'expires_at' => 'datetime',
        'qr_key_expires_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($voucher) {
            if (empty($voucher->uuid)) {
                $voucher->uuid = \Illuminate\Support\Str::uuid()->toString();
            }

            if (empty($voucher->code)) {
                $voucher->code = self::generateUniqueCode();
            }

            if (empty($voucher->remaining_value)) {
                $voucher->remaining_value = $voucher->total_value;
            }
        });
    }

    /**
     * Generate a unique voucher code.
     */
    public static function generateUniqueCode(): string
    {
        do {
            // Format: GV-XXXXXXXX (8 alphanumeric characters)
            $code = 'GV-' . strtoupper(\Illuminate\Support\Str::random(8));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    /**
     * Get the template this voucher was created from.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(VoucherTemplate::class, 'template_id');
    }

    /**
     * Get the purchaser (customer who bought the voucher).
     */
    public function purchaser(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'purchaser_id');
    }

    /**
     * Get the purchase order.
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'purchase_order_id');
    }

    /**
     * Get the voucher items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(VoucherItem::class, 'voucher_id');
    }

    /**
     * Get the redemptions.
     */
    public function redemptions(): HasMany
    {
        return $this->hasMany(VoucherRedemption::class, 'voucher_id');
    }

    /**
     * Get the commissions.
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(VoucherCommission::class, 'voucher_id');
    }

    /**
     * Get the author user.
     */
    public function authorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author');
    }

    /**
     * Check if voucher is redeemable.
     */
    public function isRedeemable(): bool
    {
        $status = VoucherStatus::tryFrom($this->status);
        
        if (!$status?->isRedeemable()) {
            return false;
        }

        // Check expiry
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        // Check remaining value
        if ($this->remaining_value <= 0) {
            return false;
        }

        return true;
    }

    /**
     * Check if voucher is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if voucher is fully redeemed.
     */
    public function isFullyRedeemed(): bool
    {
        return $this->remaining_value <= 0;
    }

    /**
     * Get the redeemed value.
     */
    public function getRedeemedValueAttribute(): float
    {
        return $this->total_value - $this->remaining_value;
    }

    /**
     * Get redemption percentage.
     */
    public function getRedemptionPercentageAttribute(): float
    {
        if ($this->total_value <= 0) {
            return 0;
        }

        return (($this->total_value - $this->remaining_value) / $this->total_value) * 100;
    }

    /**
     * Scope to filter redeemable vouchers.
     */
    public function scopeRedeemable($query)
    {
        return $query->whereIn('status', [
            VoucherStatus::ACTIVE->value,
            VoucherStatus::PARTIALLY_REDEEMED->value,
        ])
        ->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        })
        ->where('remaining_value', '>', 0);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeWithStatus($query, VoucherStatus $status)
    {
        return $query->where('status', $status->value);
    }

    /**
     * Scope to filter expired vouchers.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now())
            ->where('status', '!=', VoucherStatus::EXPIRED->value);
    }

    /**
     * Scope to filter by purchaser.
     */
    public function scopeForPurchaser($query, int $customerId)
    {
        return $query->where('purchaser_id', $customerId);
    }
}
