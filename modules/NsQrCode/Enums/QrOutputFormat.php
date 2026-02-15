<?php
/**
 * QR Code Output Format Enum
 * @package NsQrCode
 */

namespace Modules\NsQrCode\Enums;

enum QrOutputFormat: string
{
    case PNG = 'png';
    case SVG = 'svg';
    case BASE64 = 'base64';
    case GIF = 'gif';

    /**
     * Get the file extension for this format
     */
    public function extension(): string
    {
        return match ($this) {
            self::PNG => 'png',
            self::SVG => 'svg',
            self::GIF => 'gif',
            self::BASE64 => 'png', // Base64 typically encodes PNG
        };
    }

    /**
     * Get the MIME type for this format
     */
    public function mimeType(): string
    {
        return match ($this) {
            self::PNG => 'image/png',
            self::SVG => 'image/svg+xml',
            self::GIF => 'image/gif',
            self::BASE64 => 'text/plain',
        };
    }
}
