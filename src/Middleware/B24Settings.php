<?php

namespace Flamix\App24\Middleware;

use Closure;
use Flamix\App24Core\Controllers\SettingController;
use Flamix\App24Core\Models\Portals;

class B24Settings
{
    public function handle($request, Closure $next)
    {
        (new SettingController)->setPortal(Portals::getId());
        return $next($request);
    }
}
