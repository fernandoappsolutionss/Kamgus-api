<?php

namespace App\Http\Controllers;

use App\Classes\K_HelpersV1;
use App\Constants\Constant;
use App\Mail\NewPasswordReset;
use App\Models\PasswordReset;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class NewPasswordController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        //
    }

    /**
     * Forgot password method.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    //recuperar contraseña
    function forgot(Request $request) {

        $request->validate([
            'email' => 'required|email|exists:users',
        ]);

        $token = Str::random(64);

        $password_reset = PasswordReset::where('email', $request->email)->first();

        if ($password_reset !== null) {

            $password_reset->update([
                'email' => $request->email, 
                'token' => $token,
                'created_at' => Carbon::now()
            ]);

        } else {
            PasswordReset::create(
                [
                    'email' => $request->email, 
                    'token' => $token,
                    'created_at' => Carbon::now()
                ]
            );
        }

        //$new_url = 'https://myapp2.kamgus.com/#/new-password/'.(string) $token;
        $new_url = 'https://app.kamgus.com/#/new-password/'.(string) $token;

        Mail::to($request->email)->send(new NewPasswordReset('Motivo', 'Mensaje', $new_url));

        return response()->json(['msg' => Constant::FORGOT_PASSWORD]);
 
        
    }

    /**
     * Reset password method.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    //cambiar contraseña
    function reset(Request $request){

    
        $request->validate([
            'password' => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required'
        ]);

        $updatePassword = PasswordReset::where([
                'token' => $request->token
        ])->first();

        if(!$updatePassword){
            return response()->json(['warning' => 'Token invalido!']);
        }

        User::where('email', $updatePassword->email)->update(['password' => Hash::make($request->password)]);
        K_HelpersV1::getInstance()->resetDriverPassword($updatePassword->email, $request->password);
        PasswordReset::where(['email'=> $updatePassword->email])->delete();

        return response()->json(['msg' => Constant::CHANGE_PASSWORD]);

    }


}
