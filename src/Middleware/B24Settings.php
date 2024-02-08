<?php

namespace Flamix\App24Core\Middleware;

use Closure;
use Flamix\App24Core\Controllers\SettingController;
use Flamix\App24Core\Controllers\PortalController;

class B24Settings
{
    public function handle($request, Closure $next)
    {
        (new SettingController)->setPortal(PortalController::getId());
        return $next($request);
    }
}