<?php

namespace Flamix\App24Core\Models;

use App\Exceptions\App24Exception;
use Flamix\App24Core\App24;
use Flamix\App24Core\Controllers\PortalController;
use Flamix\App24Core\Controllers\CacheController;
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
     * @throws App24Exception
     */
    public static function scopeGetByDomain(Builder $query, string $domain = '', bool $check_duplicate = true): Portals
    {
        $domain = ($domain) ?: PortalController::getDomain();

        // TODO: Make experiment and increase TTL
        return Cache::remember(CacheController::key('portal_domain', $domain), 3, function () use ($query, $domain, $check_duplicate) {
            $app = $query->where('domain', $domain)->where(function ($query) use ($check_duplicate) {
                $query->where('app_code', config('app.name'));

                if ($check_duplicate && method_exists(PortalController::class, 'getReverseEnv')) {
                    $query->orWhere('app_code', PortalController::getReverseEnv());
                }
            });

            if ($app->count() > 1) {
                throw new App24Exception(trans('app24::error.security_finded_two_portal'), 402);
            }

            return $app->firstOr(function () use ($domain) {
                throw new App24Exception(trans('app24::error.security_cant_find_portal_code', ['domain' => $domain, 'code' => config('app.name')]));
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
                throw new App24Exception(trans('app24::error.security_cant_find_portal_id', ['domain' => '(taked by ID)', 'id' => $id]));
            });
        });
    }

    /**
     * Получаем всю информацию по ID портала
     * @param int $id
     * @return mixed
     * @throws App24Exception
     */
    public static function getData(int $id = 0): Portals
    {
        $id = ($id) ?: PortalController::getId();
        return app(Portals::class)->getByID($id);
    }
}