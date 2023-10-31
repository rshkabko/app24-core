<?php

namespace Flamix\App24Core\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class ViewsController extends Controller
{
    public function install(int $portal_id): View|RedirectResponse
    {
        return view('app24-core::install', compact('portal_id'));
    }
}
