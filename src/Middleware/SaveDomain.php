<?php

namespace Flamix\App24Core\Middleware;

use Closure;
use Flamix\App24Core\Controllers\AuthController;

class SaveDomain
{
    public function handle($request, Closure $next)
    {
        AuthController::setDomainToSession();
        return $next($request);
    }
}