<?php

namespace Flamix\App24Core\Controllers;

use App\Exceptions\App24Exception;
use Flamix\App24Core\Controllers\CacheController;
use Flamix\App24Core\Controllers\App\SecurityController;
use Flamix\App24Core\Controllers\App\VersionController;
use Flamix\App24Core\Models\Bridge;
use Flamix\App24Core\Models\Portals;
use Flamix\App24\Actions\SendLeadToOurBitrix24;
use Flamix\App24Core\Controllers\LicenseController;
use Flamix\Health\Controllers\HealthController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;

class PortalController extends Controller
{
    /**
     * Получаем ВСЕ данные авторизации портала
     *
     * @param int $id
     * @return Portals
     * @throws App24Exception
     */
    public static function get(int $id = 0): Portals
    {
        return Portals::getData($id);
    }

    /**
     * Получаем домен портала
     *
     * @param string $default Чтобы не генерировалось Exception
     * @return string
     * @throws App24Exception
     */
    public static function getDomain(?string $default = null): string
    {
        $data = AuthController::getAuthArray();
        $domain = $data['domain'] ?? $data['DOMAIN'] ?? $default;
        throw_if(empty($domain), App24Exception::class, trans('app24::error.portal_empty_domain'));
        return $domain;
    }

    /**
     * Получаем ID текущего портала
     *
     * @param string $domain
     * @return int
     * @throws App24Exception
     */
    public static function getId(string $domain = ''): int
    {
        $domain = ($domain) ?: self::getDomain();
        $id = Portals::getByDomain($domain)->id;

        if ($id > 0) {
            return $id;
        }

        throw new App24Exception(trans('app24::error.portal_empty_ID') . $domain);
    }

    /**
     * Портал доступен только админу (1 - Админ, 0 - Все)
     *
     * @param int $id
     * @return bool
     * @throws App24Exception
     */
    public static function isAdminOnly(int $id = 0): bool
    {
        return (bool)Portals::getData($id)->admin_only;
    }
}