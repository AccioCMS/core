<?php

namespace Themes\DummyTheme\Controllers\Auth;

use App\Http\Controllers\Frontend\MainController;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResetPasswordController extends MainController
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Create a new controller instance.
     *
     * @param \Illuminate\Http\Request $request
     */
    public function __construct(Request $request)
    {
        $this->middleware('guest');
        parent::__construct($request);
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
     * Display the password reset view for the given token.
     *
     * If no token is present, display the link request form.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string|null  $token
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showResetForm(Request $request, $token = null)
    {
        //get user by token
        $tokenFound = DB::table("password_resets")->where('token', $token)->first();

        //if token has expired or not found
        if(!$tokenFound || !$token){
            return view(Theme::view('auth.reset'))->with(['expired' => true]);
        }

        return view(Theme::view('auth.reset'))->with(
            ['token' => $token, 'email' => $tokenFound->email, 'expired' => false]
        );
    }

}
