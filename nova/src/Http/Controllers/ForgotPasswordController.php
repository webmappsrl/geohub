<?php

namespace Laravel\Nova\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
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
    use ValidatesRequests;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('nova.guest:' . config('nova.guard'));

        ResetPassword::toMailUsing(function ($notifiable, $token) {
            return (new MailMessage)
                ->subject(__('Reset Password Notification'))
                ->line(__('You are receiving this email because we received a password reset request for your account.'))
                ->action(__('Reset Password'), route('nova.password.reset', $token))
                ->line(__('If you did not request a password reset, no further action is required.'));
        });
    }

    /**
     * Display the form to request a password reset link.
     *
     * @return \Illuminate\Http\Response
     */
    public function showLinkRequestForm()
    {
        return view('nova::auth.passwords.email');
    }

    /**
     * Get the broker to be used during password reset.
     *
     * @return \Illuminate\Contracts\Auth\PasswordBroker
     */
    public function broker()
    {
        return Password::broker(config('nova.passwords'));
    }

    /**
     * Send a reset link to the given user.
     *
     * @override
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function sendResetLinkEmail(\Illuminate\Http\Request $request)
    {
        $this->validate($request, ['email' => 'required|email']);

        $inputEmail = strtolower($request->input('email'));
        $user = User::whereRaw('LOWER(email) = ?', [$inputEmail])->first();
        if (!$user) {
            return $this->sendResetLinkFailedResponse($request, Password::INVALID_USER);
        }
        $request->merge(['email' => $user->email]);
        $response = $this->broker()->sendResetLink(['email' => $user->email]);
        return $response == Password::RESET_LINK_SENT
            ? $this->sendResetLinkResponse($request, $response)
            : $this->sendResetLinkFailedResponse($request, $response);
    }
}
