<?php

namespace Accio\App\Http\Controllers\Backend\Auth;

use Accio\App\Http\Controllers\Backend\MainController;
use Illuminate\Routing\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Support\Facades\Auth;

class BaseForgotPasswordController extends MainController {
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(){
        $this->middleware('guest');
        parent::__construct();
    }


    /**
     * Define admin guard
     *
     * @return mixed
     */
    protected function guard(){
        return Auth::guard('admin');
    }

    /**
     * Display the form to request a password reset link.
     *
     * @return \Illuminate\Http\Response
     */
    public function showLinkRequestForm()
    {
        return view('accio::auth.passwords.email');
    }

}
