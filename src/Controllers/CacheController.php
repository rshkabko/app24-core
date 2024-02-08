<?php

namespace Flamix\App24Core\Controllers;

use App\Exceptions\App24Exception;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

class CacheController extends Controller
{
    public static function key(string $key, mixed $value = ''): string
    {
        return match($key) {
            'portal_domain' => "portal_app_" . config('app.name') . "_id_{$value}", //TODO: to ID
            'portal_id' => "portal_data_{$value}",
        };
    }

    /**
     * Очищаем кэш портала (где получаем все опции)
     * Нужно проводить после каждого Инсерта/Апдейта
     *
     * @param int $id
     * @return bool
     * @throws App24Exception
     */
    public static function clearPortalCache(int $id = 0): bool
    {
        $id = $id ?: PortalController::getId();
        Cache::forget(self::key('portal_id', $id));
        return true;
    }
}
