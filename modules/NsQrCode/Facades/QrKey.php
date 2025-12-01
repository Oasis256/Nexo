<?php
/**
 * QrKey Facade
 * @package NsQrCode
 */

namespace Modules\NsQrCode\Facades;

use Illuminate\Support\Facades\Facade;
use Modules\NsQrCode\Services\QrKeyService;

/**
 * @method static string generateSignedKey(string $prefix, int|string $identifier, array $additionalData = [])
 * @method static array|null validateSignedKey(string $key, array $additionalData = [])
 * @method static array|null parseKey(string $key)
 * @method static int|string|null extractIdentifier(string $key)
 * @method static bool hasPrefix(string $key, string $expectedPrefix)
 * @method static \Modules\NsQrCode\Services\QrKeyService setSignatureLength(int $length)
 * @method static int getSignatureLength()
 *
 * @see \Modules\NsQrCode\Services\QrKeyService
 */
class QrKey extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return QrKeyService::class;
    }
}
