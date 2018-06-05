<?php
/**
 * Created by PhpStorm.
 * User: sopa
 * Date: 26/09/2017
 * Time: 5:30 PM
 */

namespace Accio\App\Controllers\Frontend;

class AccountController extends MainController
{
    /**
     * Set auth as middleware to stop unauthenticated users to access all defined methods in this class
     *
     */
    public function __construct(){
        $this->middleware('auth');
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