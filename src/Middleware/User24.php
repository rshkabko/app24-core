<?php

namespace Flamix\App24Core\Middleware;

use Closure;

class User24
{
    public function handle($request, Closure $next)
    {
        \Flamix\App24Core\User24::getInstance();
        return $next($request);
    }
}