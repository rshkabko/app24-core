<?php

namespace Flamix\App24Core\Middleware;

use Closure;
use Flamix\App24Core\App24 as App24Controller;

class App24
{
    public function handle($request, Closure $next)
    {
        $domain = $request->get('DOMAIN', null);
        if (!empty($domain)) {
            session(['DOMAIN' => $domain]);
        }

        App24Controller::getInstance();
        return $next($request);
    }
}