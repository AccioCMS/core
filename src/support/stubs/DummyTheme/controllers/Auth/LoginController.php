<?php

namespace Themes\DummyTheme\Controllers\Auth;

use App\Http\Controllers\Frontend\MainController;
use App\Models\Theme;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends MainController{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @return string
     */
    protected function redirectTo(){
        return route('account.dashboard');
    }

    /**
     * Show the application's login form.
     *
     * @return \Illuminate\Http\Response
     */
    public function showLoginForm(){
        return view(Theme::view('auth.login'));
    }


    /**
     * Log the user out of the application.
     *
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        $this->guard()->logout();

        return redirect(route('auth.login'));
    }

}
