<?php
/**
 * Voucher Accounting Service
 * Handles deferred revenue accounting for gift vouchers
 * @package GiftVouchers
 */

namespace Modules\GiftVouchers\Services;

use App\Models\Transaction;
use App\Models\TransactionAccount;
use App\Services\TransactionService;
use Modules\GiftVouchers\Models\Voucher;
use Modules\GiftVouchers\Models\VoucherRedemption;
use Illuminate\Support\Facades\Log;

class VoucherAccountingService
{
    /**
     * Account identifiers for gift voucher accounting
     */
    public const ACCOUNT_DEFERRED_REVENUE = 'gift-vouchers-deferred-revenue';
    public const ACCOUNT_REVENUE = 'gift-vouchers-revenue';

    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Record deferred revenue when a voucher is purchased
     * DEBIT: Cash/Bank (handled by order payment)
     * CREDIT: Deferred Revenue (Liability)
     *
     * @param Voucher $voucher
     * @return Transaction|null
     */
    public function recordDeferredRevenue(Voucher $voucher): ?Transaction
    {
        $account = $this->getDeferredRevenueAccount();
        
        if (!$account) {
            Log::warning('Gift voucher deferred revenue account not found', [
                'voucher_id' => $voucher->id,
            ]);
            return null;
        }

        $transaction = new Transaction();
        $transaction->name = sprintf(
            __('Gift Voucher Purchase - %s'),
            $voucher->code
        );
        $transaction->value = $voucher->total_value;
        $transaction->account_id = $account->id;
        $transaction->type = 'deferred-revenue';
        $transaction->active = true;
        $transaction->author = $voucher->author ?? auth()->id();
        $transaction->created_at = now();
        $transaction->save();

        // Link transaction to voucher
        $voucher->deferred_transaction_id = $transaction->id;
        $voucher->save();

        Log::info('Gift voucher deferred revenue recorded', [
            'voucher_id' => $voucher->id,
            'transaction_id' => $transaction->id,
            'amount' => $voucher->total_value,
        ]);

        return $transaction;
    }

    /**
     * Recognize revenue when voucher is redeemed
     * DEBIT: Deferred Revenue (Liability)
     * CREDIT: Revenue
     *
     * @param VoucherRedemption $redemption
     * @return Transaction|null
     */
    public function recognizeRevenue(VoucherRedemption $redemption): ?Transaction
    {
        $revenueAccount = $this->getRevenueAccount();
        
        if (!$revenueAccount) {
            Log::warning('Gift voucher revenue account not found', [
                'redemption_id' => $redemption->id,
            ]);
            return null;
        }

        $voucher = $redemption->voucher;

        // Create revenue recognition transaction
        $transaction = new Transaction();
        $transaction->name = sprintf(
            __('Gift Voucher Redemption - %s'),
            $voucher->code
        );
        $transaction->value = $redemption->total_value;
        $transaction->account_id = $revenueAccount->id;
        $transaction->type = 'revenue-recognition';
        $transaction->active = true;
        $transaction->author = $redemption->author ?? auth()->id();
        $transaction->created_at = now();
        $transaction->save();

        // Link transaction to redemption
        $redemption->revenue_transaction_id = $transaction->id;
        $redemption->save();

        Log::info('Gift voucher revenue recognized', [
            'voucher_id' => $voucher->id,
            'redemption_id' => $redemption->id,
            'transaction_id' => $transaction->id,
            'amount' => $redemption->total_value,
        ]);

        return $transaction;
    }

    /**
     * Reverse deferred revenue when voucher is cancelled/refunded
     *
     * @param Voucher $voucher
     * @param float|null $amount Amount to reverse (null = remaining value)
     * @return Transaction|null
     */
    public function reverseDeferredRevenue(Voucher $voucher, ?float $amount = null): ?Transaction
    {
        $amount = $amount ?? $voucher->remaining_value;
        
        if ($amount <= 0) {
            return null;
        }

        $account = $this->getDeferredRevenueAccount();
        
        if (!$account) {
            Log::warning('Gift voucher deferred revenue account not found for reversal', [
                'voucher_id' => $voucher->id,
            ]);
            return null;
        }

        $transaction = new Transaction();
        $transaction->name = sprintf(
            __('Gift Voucher Cancellation - %s'),
            $voucher->code
        );
        $transaction->value = -$amount; // Negative to reverse
        $transaction->account_id = $account->id;
        $transaction->type = 'deferred-revenue-reversal';
        $transaction->active = true;
        $transaction->author = auth()->id();
        $transaction->created_at = now();
        $transaction->save();

        Log::info('Gift voucher deferred revenue reversed', [
            'voucher_id' => $voucher->id,
            'transaction_id' => $transaction->id,
            'amount' => $amount,
        ]);

        return $transaction;
    }

    /**
     * Get the deferred revenue account
     */
    public function getDeferredRevenueAccount(): ?TransactionAccount
    {
        return TransactionAccount::where('account', self::ACCOUNT_DEFERRED_REVENUE)
            ->first();
    }

    /**
     * Get the revenue account
     */
    public function getRevenueAccount(): ?TransactionAccount
    {
        return TransactionAccount::where('account', self::ACCOUNT_REVENUE)
            ->first();
    }

    /**
     * Create the required accounting accounts if they don't exist
     */
    public function ensureAccountsExist(): void
    {
        // Deferred Revenue (Liability)
        $deferredAccount = TransactionAccount::firstOrNew([
            'account' => self::ACCOUNT_DEFERRED_REVENUE,
        ]);
        
        if (!$deferredAccount->exists) {
            $deferredAccount->name = __('Gift Vouchers - Deferred Revenue');
            $deferredAccount->operation = 'credit';
            $deferredAccount->category_identifier = 'liabilities'; // Liability account
            $deferredAccount->author = auth()->id() ?? 0;
            $deferredAccount->save();
        }

        // Revenue
        $revenueAccount = TransactionAccount::firstOrNew([
            'account' => self::ACCOUNT_REVENUE,
        ]);
        
        if (!$revenueAccount->exists) {
            $revenueAccount->name = __('Gift Vouchers - Revenue');
            $revenueAccount->operation = 'credit';
            $revenueAccount->category_identifier = 'revenues'; // Revenue account
            $revenueAccount->author = auth()->id() ?? 0;
            $revenueAccount->save();
        }
    }

    /**
     * Get total deferred revenue (outstanding liability)
     */
    public function getTotalDeferredRevenue(): float
    {
        return Voucher::redeemable()->sum('remaining_value');
    }

    /**
     * Get revenue recognized in a date range
     */
    public function getRevenueInPeriod(\DateTime $start, \DateTime $end): float
    {
        return VoucherRedemption::whereBetween('created_at', [$start, $end])
            ->sum('total_value');
    }
}
