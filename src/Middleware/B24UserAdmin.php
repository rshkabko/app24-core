<?php

namespace Flamix\App24\Middleware;

use Closure;
use Flamix\App24Core\Controllers\Bitrix24\UserController;
use App\Exceptions\FxException;

class B24UserAdmin
{
    public function handle($request, Closure $next)
    {
        throw_unless(UserController::isAdmin(), FxException::class, trans('flamix::msg.only_admin'));

        return $next($request);
    }
}
