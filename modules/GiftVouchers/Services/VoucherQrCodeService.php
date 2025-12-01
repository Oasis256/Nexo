<?php
/**
 * Voucher QR Code Service
 * Integrates with NsQrCode module for QR generation and key signing
 * @package GiftVouchers
 */

namespace Modules\GiftVouchers\Services;

use Modules\GiftVouchers\Models\Voucher;
use Modules\NsQrCode\Services\QrCodeService;
use Modules\NsQrCode\Services\QrKeyService;
use Modules\NsQrCode\Enums\QrOutputFormat;
use Modules\NsQrCode\Enums\QrErrorCorrection;

class VoucherQrCodeService
{
    /**
     * Prefix for gift voucher QR keys
     */
    public const KEY_PREFIX = 'GV';

    /**
     * Storage subdirectory for voucher QR codes
     */
    protected const STORAGE_SUBDIR = 'gift-vouchers';

    protected QrCodeService $qrCodeService;
    protected QrKeyService $qrKeyService;

    public function __construct(
        QrCodeService $qrCodeService,
        QrKeyService $qrKeyService
    ) {
        $this->qrCodeService = $qrCodeService;
        $this->qrKeyService = $qrKeyService;
    }

    /**
     * Generate a secure redemption key for a voucher
     *
     * @param Voucher $voucher
     * @return string The signed key (format: GV:{id}:{signature})
     */
    public function generateRedemptionKey(Voucher $voucher): string
    {
        // Include voucher code in signature for extra security
        $additionalData = [
            'code' => $voucher->code,
            'created' => $voucher->created_at?->timestamp ?? time(),
        ];

        return $this->qrKeyService->generateSignedKey(
            self::KEY_PREFIX,
            $voucher->id,
            $additionalData
        );
    }

    /**
     * Validate a redemption key and return the voucher if valid
     *
     * @param string $key The key to validate
     * @return Voucher|null The voucher if valid and redeemable, null otherwise
     */
    public function validateRedemptionKey(string $key): ?Voucher
    {
        // Check prefix first (quick rejection)
        if (!$this->qrKeyService->hasPrefix($key, self::KEY_PREFIX)) {
            return null;
        }

        // Extract voucher ID
        $voucherId = $this->qrKeyService->extractIdentifier($key);
        
        if ($voucherId === null) {
            return null;
        }

        // Find voucher
        $voucher = Voucher::find($voucherId);
        
        if ($voucher === null) {
            return null;
        }

        // Validate signature with voucher's additional data
        $additionalData = [
            'code' => $voucher->code,
            'created' => $voucher->created_at?->timestamp ?? 0,
        ];

        $validation = $this->qrKeyService->validateSignedKey($key, $additionalData);
        
        if ($validation === null) {
            return null;
        }

        // Check if QR key matches stored key
        if ($voucher->qr_redemption_key !== $key) {
            return null;
        }

        // Check QR key expiry
        if ($voucher->qr_key_expires_at && $voucher->qr_key_expires_at->isPast()) {
            return null;
        }

        return $voucher;
    }

    /**
     * Generate QR code image and save it for a voucher
     *
     * @param Voucher $voucher
     * @param bool $regenerateKey Whether to regenerate the redemption key
     * @return string Path to the saved QR image
     */
    public function generateQrImage(Voucher $voucher, bool $regenerateKey = false): string
    {
        // Generate or use existing key
        if ($regenerateKey || empty($voucher->qr_redemption_key)) {
            $redemptionKey = $this->generateRedemptionKey($voucher);
            
            $voucher->qr_redemption_key = $redemptionKey;
            $voucher->qr_key_expires_at = $voucher->expires_at;
            $voucher->save();
        } else {
            $redemptionKey = $voucher->qr_redemption_key;
        }

        // Delete old QR image if exists
        if ($voucher->qr_image_path) {
            $this->qrCodeService->delete($voucher->qr_image_path);
        }

        // Generate and save QR code
        $filename = 'voucher-' . $voucher->id . '-' . time();
        
        $path = $this->qrCodeService->generateAndSave(
            data: $redemptionKey,
            filename: $filename,
            subdirectory: self::STORAGE_SUBDIR,
            format: QrOutputFormat::PNG,
            eccLevel: QrErrorCorrection::HIGH, // High correction for printability
            options: [
                'scale' => 10,
                'margin' => 2,
            ]
        );

        // Update voucher with new path
        $voucher->qr_image_path = $path;
        $voucher->save();

        return $path;
    }

    /**
     * Get QR code as base64 data URI (for inline display)
     *
     * @param Voucher $voucher
     * @return string Base64 data URI
     */
    public function getQrImageBase64(Voucher $voucher): string
    {
        // Ensure key exists
        if (empty($voucher->qr_redemption_key)) {
            $redemptionKey = $this->generateRedemptionKey($voucher);
            $voucher->qr_redemption_key = $redemptionKey;
            $voucher->qr_key_expires_at = $voucher->expires_at;
            $voucher->save();
        }

        return $this->qrCodeService->generateBase64(
            data: $voucher->qr_redemption_key,
            eccLevel: QrErrorCorrection::HIGH,
            options: [
                'scale' => 10,
                'margin' => 2,
            ]
        );
    }

    /**
     * Get public URL for stored QR image
     *
     * @param Voucher $voucher
     * @return string|null URL or null if no image stored
     */
    public function getQrImageUrl(Voucher $voucher): ?string
    {
        if (empty($voucher->qr_image_path)) {
            return null;
        }

        if (!$this->qrCodeService->exists($voucher->qr_image_path)) {
            return null;
        }

        return $this->qrCodeService->getUrl($voucher->qr_image_path);
    }

    /**
     * Delete stored QR image for a voucher
     *
     * @param Voucher $voucher
     * @return bool
     */
    public function deleteQrImage(Voucher $voucher): bool
    {
        if (empty($voucher->qr_image_path)) {
            return true;
        }

        $deleted = $this->qrCodeService->delete($voucher->qr_image_path);

        if ($deleted) {
            $voucher->qr_image_path = null;
            $voucher->save();
        }

        return $deleted;
    }

    /**
     * Regenerate QR key and image for a voucher
     * (Useful after suspected key compromise)
     *
     * @param Voucher $voucher
     * @return string New QR image path
     */
    public function regenerateQr(Voucher $voucher): string
    {
        // Force regeneration of key
        $voucher->qr_redemption_key = null;
        
        return $this->generateQrImage($voucher, regenerateKey: true);
    }

    /**
     * Parse a QR key without full validation
     * (For quick lookup before full validation)
     *
     * @param string $key
     * @return int|null Voucher ID or null if parse fails
     */
    public function parseVoucherId(string $key): ?int
    {
        if (!$this->qrKeyService->hasPrefix($key, self::KEY_PREFIX)) {
            return null;
        }

        $id = $this->qrKeyService->extractIdentifier($key);
        
        return is_int($id) ? $id : null;
    }
}
