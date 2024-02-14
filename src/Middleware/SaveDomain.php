<?php

namespace Flamix\App24Core\Middleware;

use Closure;
use Flamix\App24Core\Controllers\AuthController;
use Flamix\App24Core\Controllers\PortalController;

class SaveDomain
{
    public function handle($request, Closure $next)
    {
        $domain = $request->get('DOMAIN', null);
        if ($domain) {
            session(['DOMAIN' => $domain]);
        }
        return $next($request);
    }
}