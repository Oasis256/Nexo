<?php
/**
 * QrCode Facade
 * @package NsQrCode
 */

namespace Modules\NsQrCode\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\NsQrCode\Services\QrCodeService;

/**
 * @method static string generate(string $data, \Modules\NsQrCode\Enums\QrOutputFormat $format = \Modules\NsQrCode\Enums\QrOutputFormat::PNG, \Modules\NsQrCode\Enums\QrErrorCorrection $eccLevel = \Modules\NsQrCode\Enums\QrErrorCorrection::MEDIUM, array $options = [])
 * @method static string generateAndSave(string $data, ?string $filename = null, ?string $subdirectory = null, \Modules\NsQrCode\Enums\QrOutputFormat $format = \Modules\NsQrCode\Enums\QrOutputFormat::PNG, \Modules\NsQrCode\Enums\QrErrorCorrection $eccLevel = \Modules\NsQrCode\Enums\QrErrorCorrection::MEDIUM, array $options = [])
 * @method static string generateBase64(string $data, \Modules\NsQrCode\Enums\QrErrorCorrection $eccLevel = \Modules\NsQrCode\Enums\QrErrorCorrection::MEDIUM, array $options = [])
 * @method static string generateSvg(string $data, \Modules\NsQrCode\Enums\QrErrorCorrection $eccLevel = \Modules\NsQrCode\Enums\QrErrorCorrection::MEDIUM, array $options = [])
 * @method static bool delete(string $path)
 * @method static bool exists(string $path)
 * @method static string getUrl(string $path)
 * @method static string getFullPath(string $path)
 * @method static \Modules\NsQrCode\Services\QrCodeService setStorageDisk(string $disk)
 * @method static \Modules\NsQrCode\Services\QrCodeService setStoragePath(string $path)
 * @method static \Modules\NsQrCode\Services\QrCodeService setScale(int $scale)
 * @method static \Modules\NsQrCode\Services\QrCodeService setMargin(int $margin)
 *
 * @see \Modules\NsQrCode\Services\QrCodeService
 */
class QrCode extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return QrCodeService::class;
    }
}
