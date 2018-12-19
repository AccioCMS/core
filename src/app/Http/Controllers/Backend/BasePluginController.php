<?php

namespace Accio\App\Http\Controllers\Backend;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;


class BasePluginController extends MainController
{
    /**
     * Return view for plugin.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function pluginView()
    {
        return view('content');
    }


}
