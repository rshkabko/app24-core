<?php

namespace Flamix\App24Core\Models;

use App\Exceptions\FxException;
use Flamix\App24Core\B24App;
use Flamix\App24Core\Controllers\PortalController;
use Flamix\App24Core\Controllers\App\CacheController;
use Flamix\App24\Controllers\LicenseController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Portals extends Model
{
    protected $guarded = [];

    public static function getId(string $domain = ''): int
    {
        //TODO: Remove. Was insert on 01.10.2023
//        sdd('Deprecated Portals::getId()');
        return PortalController::getId($domain);
    }

    public static function getDomain(): string
    {
        //TODO: Remove. Was insert on 01.10.2023
//        sdd('Deprecated Portals::getDomain()');
        return PortalController::getDomain();
    }

    /**
     * Инициализация портала по домену
     *
     * @param Builder $query
     * @param string $domain
     * @param bool $check_duplicate Проверять дубли порталов? Ставим НЕТ в крайнем случае.
     * @return Portals
     * @throws FxException
     */
    public static function scopeGetByDomain(Builder $query, string $domain = '', bool $check_duplicate = true): Portals
    {
        $domain = ($domain) ?: PortalController::getDomain();

        // TODO: Make experiment and increase TTL
        return Cache::remember(CacheController::key('portal_domain', $domain), 3, function () use ($query, $domain, $check_duplicate) {
            $app = $query->where('domain', $domain)->where(function ($query) use ($check_duplicate) {
                $query->where('app_code', config('app.name'));

                if ($check_duplicate && method_exists(LicenseController::class, 'getReverseEnv')) {
                    $query->orWhere('app_code', LicenseController::getReverseEnv());
                }
            });

            if ($app->count() > 1) {
                throw new FxException(trans('flamix::error.security_finded_two_portal'), 402);
            }

            return $app->firstOr(function () use ($domain) {
                throw new FxException(trans('flamix::error.security_cant_find_portal_code', ['domain' => $domain, 'code' => config('app.name')]));
            });
        });
    }

    /**
     * Быстрая инициализация портала по ID
     *
     * @param Builder $query
     * @param int $id
     * @return Portals
     */
    public static function scopeGetByID(Builder $query, int $id): Portals
    {
        return Cache::remember(CacheController::key('portal_id', $id), 3, function () use ($query, $id) {
            $data = $query->where('id', $id);

            return $data->firstOr(function () use ($id) {
                throw new FxException(trans('flamix::error.security_cant_find_portal_id', ['domain' => '(taked by ID)', 'id' => $id]));
            });
        });
    }

    /**
     * Получаем всю информацию по ID портала
     * @param int $id
     * @return mixed
     * @throws FxException
     */
    public static function getData(int $id = 0): Portals
    {
        $id = ($id) ?: PortalController::getId();
        return app(Portals::class)->getByID($id);
    }
}
