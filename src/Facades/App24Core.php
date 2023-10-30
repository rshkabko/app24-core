<?php

namespace Flamix\App24Core\Facades;

use Illuminate\Support\Facades\Facade;

class App24Core extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'app24';
    }
}
