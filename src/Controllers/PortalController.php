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

    /**
     * Делает реверс доступа (когда админ проверяет)
     *
     * @param int $portal_id
     * @return array
     * @throws App24Exception
     */
    public static function reverseAccess(int $portal_id = 0): array
    {
        $portal_id = $portal_id ?: Portals::getId();

        app(Portals::class)->where('id', $portal_id)->update(['admin_only' => !self::isAdminOnly($portal_id)]);

        CacheController::clearPortalCache($portal_id);
        return ['status' => 'success', 'onlyAdmin' => self::isAdminOnly($portal_id)];
    }

    /**
     * Удаление
     *
     * @param int $portal_id
     * @param bool $force
     * @return bool
     */
    public static function destroy(int $portal_id, bool $force = false): bool
    {
        $portal = Portals::find($portal_id);
        $domain = $portal->domain;

        event('onBeforePortalDestroy', [$domain]);
        //В CRM добавляем что лид удалил приложение
        SendLeadToOurBitrix24::handle('delete');

        if (!$force) return true;

        /**
         * Удаляем портал и его платную версию (если есть)
         * И чистим кэш при этом сразу на 2х
         * Все остальное - не критично
         */
        $env = LicenseController::getPaymentAndFreeVersion($portal_id);
        Portals::where('domain', $domain)->where(function ($query) use ($env) {
            $query->where('app_code', $env['free']);
            $query->orWhere('app_code', $env['pay']);
        })->delete();

        // TODO: Bad cache, fix
        Cache::forget('portal_id_' . $env['free'] . '_' . $domain);
        Cache::forget('portal_id_' . $env['pay'] . '_' . $domain);

        SettingController::deletePortalSettings($portal_id);
        Bridge::deletePortalBridge($portal_id);

        return true;
    }

    /**
     * Destroy duplicate portal
     *
     * @param int $portal_id
     * @param string $api_token
     * @return RedirectResponse
     * @throws App24Exception
     */
    public static function destroyDuplicate(int $portal_id, string $api_token): RedirectResponse
    {
        throw_if($api_token !== SecurityController::getToken($portal_id), new App24Exception('Hello Hacker!'));

        LogController::portal('Force delete portal DUPLICETE!', ['portal_id' => $portal_id, 'api_token' => $api_token], $portal_id);

        // Force delete portal
        self::destroy($portal_id, true);
        return redirect()->back();
    }
}