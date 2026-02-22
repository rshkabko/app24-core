<?php

namespace Flamix\App24Core\Controllers;

use Flamix\App24Core\Models\Portals;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ViewsController extends Controller
{
    /**
     * Install portal.
     *
     * @param int $portal_id
     * @return View|RedirectResponse
     */
    public function install(int $portal_id): View|RedirectResponse
    {
        return view('app24-core::install', compact('portal_id'));
    }

    /**
     * Uninstall portal.
     *
     * @param int $portal_id
     * @param bool $need_clear_data
     * @return bool
     * @throws \App\Exceptions\App24Exception
     */
    public function uninstall(int $portal_id, bool $need_clear_data): bool
    {
        $portal = Portals::find($portal_id);

        if (!$need_clear_data) return true;

        Portals::where('id', $portal_id)->delete();
        CacheController::clearPortalCache($portal_id);
        SettingController::deletePortalSettings($portal_id);

        return true;
    }
}