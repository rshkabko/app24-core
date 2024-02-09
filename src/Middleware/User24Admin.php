<?php

namespace Flamix\App24Core\Middleware;

use Closure;
use Flamix\App24Core\Controllers\UserController;
use App\Exceptions\App24Exception;

class User24Admin
{
    public function handle($request, Closure $next)
    {
        throw_unless(UserController::isAdmin(), App24Exception::class, trans('app24::error.only_admin'));

        return $next($request);
    }
}