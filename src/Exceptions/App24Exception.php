<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\View\View;

class App24Exception extends Exception
{
    public function report()
    {
    }

    /**
     * Throw exception.
     * If we have ExceptionController - show it.
     * TODO! If we have ExceptionController - show it.
     *
     * @param Request $request
     * @return View
     * @throws Exception
     */
    public function render(Request $request): View
    {
        // TODO: Check if ExceptionController exist - show
        throw new Exception($this->getMessage(), $this->getCode());
    }
}
