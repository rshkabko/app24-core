<?php

namespace Flamix\App24Core\Middleware;

use Closure;
use Flamix\App24Core\B24App as B24AppController;

class B24App
{
    public function handle($request, Closure $next)
    {
        $domain = $request->get('DOMAIN', null);
        if (!empty($domain)) {
            session(['DOMAIN' => $domain]);
        }

        B24AppController::getInstance();
        return $next($request);
    }
}
