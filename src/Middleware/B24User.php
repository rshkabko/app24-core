<?php

namespace Flamix\App24Core\Middleware;

use Closure;

class B24User
{
    public function handle($request, Closure $next)
    {
        \Flamix\App24Core\B24User::getInstance();
        return $next($request);
    }
}