<?php
/**
 * Voucher Template Model
 * @package GiftVouchers
 */

namespace Modules\GiftVouchers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Modules\GiftVouchers\Enums\TemplateStatus;

class VoucherTemplate extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'nexopos_gift_voucher_templates';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uuid',
        'name',
        'description',
        'validity_days',
        'is_transferable',
        'status',
        'total_value',
        'author',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_transferable' => 'boolean',
        'total_value' => 'decimal:5',
        'validity_days' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($template) {
            if (empty($template->uuid)) {
                $template->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

    /**
     * Get the template items (products/services included).
     */
    public function items(): HasMany
    {
        return $this->hasMany(VoucherTemplateItem::class, 'template_id');
    }

    /**
     * Get the vouchers generated from this template.
     */
    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class, 'template_id');
    }

    /**
     * Get the author user.
     */
    public function authorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author');
    }

    /**
     * Check if template is active.
     */
    public function isActive(): bool
    {
        return $this->status === TemplateStatus::ACTIVE->value;
    }

    /**
     * Recalculate total value from items.
     */
    public function recalculateTotalValue(): self
    {
        $this->total_value = $this->items()->sum('total_price');
        $this->save();
        
        return $this;
    }

    /**
     * Scope to filter active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('status', TemplateStatus::ACTIVE->value);
    }
}
