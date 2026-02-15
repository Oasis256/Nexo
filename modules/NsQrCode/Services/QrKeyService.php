<?php
/**
 * QR Key Service
 * Handles secure key generation and validation using HMAC signatures
 * @package NsQrCode
 */

namespace Modules\NsQrCode\Services;

use Illuminate\Support\Facades\Log;

class QrKeyService
{
    /**
     * The secret key used for HMAC signing
     */
    protected string $secretKey;

    /**
     * Signature length (characters to extract from HMAC)
     */
    protected int $signatureLength = 16;

    public function __construct()
    {
        // Use app key as base, with optional module-specific salt
        $this->secretKey = config('app.key') . '::nsqrcode';
    }

    /**
     * Generate a signed key with format: PREFIX:ID:SIGNATURE
     *
     * @param string $prefix Module prefix (e.g., 'GV' for Gift Vouchers)
     * @param int|string $identifier Unique identifier (e.g., voucher ID)
     * @param array $additionalData Optional additional data to include in signature
     * @return string The complete signed key
     */
    public function generateSignedKey(string $prefix, int|string $identifier, array $additionalData = []): string
    {
        $signature = $this->generateSignature($prefix, $identifier, $additionalData);
        
        return sprintf('%s:%s:%s', $prefix, $identifier, $signature);
    }

    /**
     * Validate a signed key and extract its components
     *
     * @param string $key The key to validate
     * @param array $additionalData Optional additional data that was used in signing
     * @return array|null Returns [prefix, identifier] if valid, null if invalid
     */
    public function validateSignedKey(string $key, array $additionalData = []): ?array
    {
        $parts = $this->parseKey($key);
        
        if ($parts === null) {
            return null;
        }

        [$prefix, $identifier, $providedSignature] = $parts;
        
        $expectedSignature = $this->generateSignature($prefix, $identifier, $additionalData);
        
        if (!hash_equals($expectedSignature, $providedSignature)) {
            Log::warning('QR key signature mismatch', [
                'prefix' => $prefix,
                'identifier' => $identifier,
            ]);
            return null;
        }

        return [
            'prefix' => $prefix,
            'identifier' => $identifier,
        ];
    }

    /**
     * Parse a key into its components without validating signature
     *
     * @param string $key The key to parse
     * @return array|null Returns [prefix, identifier, signature] or null if format invalid
     */
    public function parseKey(string $key): ?array
    {
        $parts = explode(':', $key);
        
        if (count($parts) !== 3) {
            return null;
        }

        [$prefix, $identifier, $signature] = $parts;

        // Basic validation
        if (empty($prefix) || empty($identifier) || empty($signature)) {
            return null;
        }

        if (strlen($signature) !== $this->signatureLength) {
            return null;
        }

        return [$prefix, $identifier, $signature];
    }

    /**
     * Extract just the identifier from a key (without validation)
     *
     * @param string $key The key to parse
     * @return int|string|null The identifier or null if parse fails
     */
    public function extractIdentifier(string $key): int|string|null
    {
        $parts = $this->parseKey($key);
        
        if ($parts === null) {
            return null;
        }

        $identifier = $parts[1];
        
        // Return as int if numeric
        return is_numeric($identifier) ? (int) $identifier : $identifier;
    }

    /**
     * Check if a key belongs to a specific prefix
     *
     * @param string $key The key to check
     * @param string $expectedPrefix The expected prefix
     * @return bool
     */
    public function hasPrefix(string $key, string $expectedPrefix): bool
    {
        $parts = $this->parseKey($key);
        
        return $parts !== null && $parts[0] === $expectedPrefix;
    }

    /**
     * Generate the HMAC signature
     */
    protected function generateSignature(string $prefix, int|string $identifier, array $additionalData = []): string
    {
        $payload = json_encode([
            'prefix' => $prefix,
            'id' => $identifier,
            'data' => $additionalData,
        ]);

        $hash = hash_hmac('sha256', $payload, $this->secretKey);
        
        // Return first N characters of the hash
        return substr($hash, 0, $this->signatureLength);
    }

    /**
     * Set custom signature length
     */
    public function setSignatureLength(int $length): self
    {
        $this->signatureLength = max(8, min(64, $length)); // Between 8 and 64
        return $this;
    }

    /**
     * Get current signature length
     */
    public function getSignatureLength(): int
    {
        return $this->signatureLength;
    }
}
