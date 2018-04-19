<?php
/**
 * Created by PhpStorm.
 * User: sopa
 * Date: 26/09/2017
 * Time: 5:31 PM
 */

namespace Themes\DummyTheme\Controllers;

use App\Http\Controllers\Frontend\MainController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class AccountController extends MainController
{

    public function __construct(Request $request)
    {
        parent::__construct($request);
        // As route middlewares are not called when routes are cached
        // we need to call them manually
        if(App::routesAreCached()) {
            $this->middleware('auth');
        }
    }

    /**
     * Show logged user's dashboard
     *
     * @return \Illuminate\Http\Response
     */
    public function dashboard(){
        return view('vendor/account/dashboard');
    }

    /**
     * Show logged user's edit profile form
     *
     * @return \Illuminate\Http\Response
     */
    public function profile(){
        return view('vendor/account/profile');
    }
}