<?php
/**
 * QR Code Error Correction Level Enum
 * @package NsQrCode
 */

namespace Modules\NsQrCode\Enums;

use chillerlan\QRCode\Common\EccLevel;

enum QrErrorCorrection: string
{
    case LOW = 'L';        // ~7% recovery
    case MEDIUM = 'M';     // ~15% recovery
    case QUARTILE = 'Q';   // ~25% recovery
    case HIGH = 'H';       // ~30% recovery

    /**
     * Convert to chillerlan EccLevel
     */
    public function toEccLevel(): int
    {
        return match ($this) {
            self::LOW => EccLevel::L,
            self::MEDIUM => EccLevel::M,
            self::QUARTILE => EccLevel::Q,
            self::HIGH => EccLevel::H,
        };
    }

    /**
     * Get default error correction level
     */
    public static function default(): self
    {
        return self::MEDIUM;
    }
}
