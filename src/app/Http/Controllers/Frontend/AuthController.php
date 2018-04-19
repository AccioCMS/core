<?php
/**
 * Created by PhpStorm.
 * User: sopa
 * Date: 26/09/2017
 * Time: 5:27 PM
 */

namespace Accio\App\Controllers\Frontend;

use App\Exceptions\GeneralException;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;


class AuthController extends MainController
{
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

    use AuthenticatesUsers, RegistersUsers;

    /**
     * Create a new controller instance.
     * Only "logout" & register are allowed to be accessed by guest
     *
     */
    public function __construct(){
        $this->middleware('guest', ['except' => ['register','logout']]);
    }

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
        return view('vendor/auth/login');
    }

    /**
     * Show the application's reset password form.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function resetPassword(){
        return view('vendor/auth/reset-password');
    }

    /**
     * Show the application's register form.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function registerForm(){
        return view('vendor/auth/register');
    }

    /**
     * Log the user out of the application.
     *
     * @param \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        $this->guard()->logout();

        return redirect(route('auth.login'));
    }

    /**
     * The user has been authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {

        //TODO krejt ma poshte
        /*
       * Check to see if the users account is confirmed and active
       */
        //log the user out

        //send to auth/unconfirmed

        //ensure the user is active
        if(!$user->isActive){
            $this->guard()->logout();
            return view('vendor/auth/deactivated');
        }

    }
}