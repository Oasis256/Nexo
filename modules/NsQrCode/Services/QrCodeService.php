<?php
/**
 * QR Code Service
 * Handles QR code generation using chillerlan/php-qrcode
 * @package NsQrCode
 */

namespace Modules\NsQrCode\Services;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\QROutputInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\NsQrCode\Enums\QrOutputFormat;
use Modules\NsQrCode\Enums\QrErrorCorrection;

class QrCodeService
{
    /**
     * Default QR code options
     */
    protected array $defaultOptions = [
        'scale' => 10,
        'margin' => 2,
        'outputType' => QROutputInterface::GDIMAGE_PNG,
    ];

    /**
     * Storage disk for QR images
     */
    protected string $storageDisk = 'public';

    /**
     * Base path for storing QR images
     */
    protected string $storagePath = 'qrcodes';

    /**
     * Generate a QR code image from data
     *
     * @param string $data The data to encode in the QR code
     * @param QrOutputFormat $format Output format
     * @param QrErrorCorrection $eccLevel Error correction level
     * @param array $options Additional options
     * @return string The generated QR code (path, base64, or raw content)
     */
    public function generate(
        string $data,
        QrOutputFormat $format = QrOutputFormat::PNG,
        QrErrorCorrection $eccLevel = QrErrorCorrection::MEDIUM,
        array $options = []
    ): string {
        $qrOptions = $this->buildOptions($format, $eccLevel, $options);
        $qrCode = new QRCode($qrOptions);

        return $qrCode->render($data);
    }

    /**
     * Generate and save a QR code image to storage
     *
     * @param string $data The data to encode
     * @param string|null $filename Custom filename (without extension)
     * @param string|null $subdirectory Optional subdirectory within storage path
     * @param QrOutputFormat $format Output format (PNG or SVG)
     * @param QrErrorCorrection $eccLevel Error correction level
     * @param array $options Additional options
     * @return string The relative path to the saved file
     */
    public function generateAndSave(
        string $data,
        ?string $filename = null,
        ?string $subdirectory = null,
        QrOutputFormat $format = QrOutputFormat::PNG,
        QrErrorCorrection $eccLevel = QrErrorCorrection::MEDIUM,
        array $options = []
    ): string {
        $content = $this->generate($data, $format, $eccLevel, $options);
        
        // For base64, extract the actual image data
        if ($format === QrOutputFormat::BASE64) {
            $content = $this->decodeBase64Image($content);
            $format = QrOutputFormat::PNG; // Base64 decodes to PNG
        }

        // Build file path
        $filename = $filename ?? Str::uuid()->toString();
        $extension = $format->extension();
        
        $path = $this->storagePath;
        if ($subdirectory) {
            $path .= '/' . trim($subdirectory, '/');
        }
        $fullPath = $path . '/' . $filename . '.' . $extension;

        // Save to storage
        Storage::disk($this->storageDisk)->put($fullPath, $content);

        return $fullPath;
    }

    /**
     * Generate a QR code as base64 data URI
     *
     * @param string $data The data to encode
     * @param QrErrorCorrection $eccLevel Error correction level
     * @param array $options Additional options
     * @return string Base64 data URI (data:image/png;base64,...)
     */
    public function generateBase64(
        string $data,
        QrErrorCorrection $eccLevel = QrErrorCorrection::MEDIUM,
        array $options = []
    ): string {
        $options['outputBase64'] = true;
        
        return $this->generate($data, QrOutputFormat::PNG, $eccLevel, $options);
    }

    /**
     * Generate a QR code as SVG string
     *
     * @param string $data The data to encode
     * @param QrErrorCorrection $eccLevel Error correction level
     * @param array $options Additional options
     * @return string SVG markup
     */
    public function generateSvg(
        string $data,
        QrErrorCorrection $eccLevel = QrErrorCorrection::MEDIUM,
        array $options = []
    ): string {
        return $this->generate($data, QrOutputFormat::SVG, $eccLevel, $options);
    }

    /**
     * Delete a stored QR code image
     *
     * @param string $path The path returned from generateAndSave
     * @return bool True if deleted, false otherwise
     */
    public function delete(string $path): bool
    {
        return Storage::disk($this->storageDisk)->delete($path);
    }

    /**
     * Check if a stored QR code exists
     *
     * @param string $path The path to check
     * @return bool
     */
    public function exists(string $path): bool
    {
        return Storage::disk($this->storageDisk)->exists($path);
    }

    /**
     * Get the public URL for a stored QR code
     *
     * @param string $path The path returned from generateAndSave
     * @return string The public URL
     */
    public function getUrl(string $path): string
    {
        return Storage::disk($this->storageDisk)->url($path);
    }

    /**
     * Get the full storage path for a QR code
     *
     * @param string $path The relative path
     * @return string The full filesystem path
     */
    public function getFullPath(string $path): string
    {
        return Storage::disk($this->storageDisk)->path($path);
    }

    /**
     * Build QR code options
     */
    protected function buildOptions(
        QrOutputFormat $format,
        QrErrorCorrection $eccLevel,
        array $customOptions = []
    ): QROptions {
        $outputType = match ($format) {
            QrOutputFormat::PNG, QrOutputFormat::BASE64 => QROutputInterface::GDIMAGE_PNG,
            QrOutputFormat::SVG => QROutputInterface::MARKUP_SVG,
            QrOutputFormat::GIF => QROutputInterface::GDIMAGE_GIF,
        };

        $options = array_merge($this->defaultOptions, [
            'outputType' => $outputType,
            'eccLevel' => $eccLevel->toEccLevel(),
            'outputBase64' => $format === QrOutputFormat::BASE64 || ($customOptions['outputBase64'] ?? false),
        ], $customOptions);

        // Remove custom keys that aren't QROptions properties
        unset($options['outputBase64']);

        return new QROptions($options);
    }

    /**
     * Decode base64 image data from data URI
     */
    protected function decodeBase64Image(string $base64): string
    {
        // Remove data URI prefix if present
        if (str_starts_with($base64, 'data:')) {
            $parts = explode(',', $base64, 2);
            $base64 = $parts[1] ?? $base64;
        }

        return base64_decode($base64);
    }

    /**
     * Set the storage disk
     */
    public function setStorageDisk(string $disk): self
    {
        $this->storageDisk = $disk;
        return $this;
    }

    /**
     * Set the storage path
     */
    public function setStoragePath(string $path): self
    {
        $this->storagePath = trim($path, '/');
        return $this;
    }

    /**
     * Set default scale (module size in pixels)
     */
    public function setScale(int $scale): self
    {
        $this->defaultOptions['scale'] = max(1, $scale);
        return $this;
    }

    /**
     * Set default margin (quiet zone modules)
     */
    public function setMargin(int $margin): self
    {
        $this->defaultOptions['margin'] = max(0, $margin);
        return $this;
    }
}
