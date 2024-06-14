<?php

namespace Flamix\App24Core\Controllers;

use App\Exceptions\App24Exception;
use Flamix\App24Core\Controllers\CacheController;
use Flamix\App24Core\Models\Portals;
use Flamix\B24App\Controllers\PortalController as B24PortalController;
use Illuminate\Routing\Controller;
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
     * Получаем ID текущего портала.
     *
     * @param  string|null  $domain
     * @param  bool  $check_duplicate
     * @return int
     * @throws App24Exception
     */
    public static function getId(?string $domain = null, bool $check_duplicate = true): int
    {
        $domain = $domain ?: self::getDomain();
        return Cache::remember(CacheController::key('portal_domain', $domain), 30, function () use ($domain, $check_duplicate) {
            $app = Portals::select('id')->where('domain', $domain)->where(function ($query) use ($check_duplicate) {
                $query->where('app_code', config('app.name'));

                if ($check_duplicate && method_exists(B24PortalController::class, 'getReverseEnv')) {
                    $query->orWhere('app_code', B24PortalController::getReverseEnv());
                }

                $query->limit(1);
            });

            $portal_id = $app->value('id');
            throw_unless($portal_id, App24Exception::class, __('app24::error.security_cant_find_portal_code', ['domain' => $domain, 'code' => config('app.name')]));

            return $portal_id;
        });
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