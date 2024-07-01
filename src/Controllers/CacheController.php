<?php

namespace Flamix\App24Core\Controllers;

use App\Exceptions\App24Exception;
use Illuminate\Support\Facades\Cache;

class CacheController
{
    /**
     * Get cache key. All keys must be expeted in all controllers.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return string
     */
    public static function key(string $key, mixed $value = ''): string
    {
        return match($key) {
            'portal_domain' => "portal_app_" . config('app.name') . "_id_{$value}",
            'portal_id' => "portal_data_{$value}",
        };
    }

    /**
     * Clear cache for portal. Need to use after update portal data.
     *
     * @param  int|null  $id
     * @param  string|null  $domain
     * @return bool
     * @throws App24Exception
     */
    public static function clearPortalCache(?int $id = null, ?string $domain = null): bool
    {
        $id = $id ?: PortalController::getId();
        Cache::forget(self::key('portal_id', $id));
        if ($domain) {
            Cache::forget(self::key('portal_domain', $domain));
        }
        return true;
    }
}