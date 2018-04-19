<?php

namespace Themes\DummyTheme\Controllers\Auth;

use App\Http\Controllers\Frontend\MainController;
use App\Models\Theme;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends MainController
{
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
     * @param \Illuminate\Http\Request $request
     */
    public function __construct(Request $request)
    {
        $this->middleware('guest');
        parent::__construct($request);
    }

    /**
     * Display the form to request a password reset link.
     *
     * @return \Illuminate\Http\Response
     */
    public function showLinkRequestForm()
    {
        return view(Theme::view('auth.forgot'));
    }

    /**
     * Send a reset link to the given user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendResetLinkEmail(Request $request)
    {
        $this->validate($request, ['email' => 'required|email']);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        try {
            $response = $this->broker()->sendResetLink(
                $request->only('email')
            );
        }
        catch(\Exception $e){
            return back()->with('error', trans("auth.email_not_sent"));
        }

        return $response == Password::RESET_LINK_SENT
            ? $this->sendResetLinkResponse($response)
            : $this->sendResetLinkFailedResponse($request, $response);
    }

}
