<?php

namespace App\Http\Controllers\v1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use App\Responser\JsonResponser;
use App\Notifications\PasswordResetNotification;
use Illuminate\Support\Facades\DB;

class ForgetPasswordController extends Controller
{
    public function requestResetPasswordLink(Request $request)
    {
        $rules = [
            'email' => 'required|email'
        ];

        $validateRequest = Validator::make($request->only("email"), $rules);

        if($validateRequest->fails()){
            return JsonResponser::send(true, $validateRequest->errors()->first(), $validateRequest->errors()->all(), 400);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return JsonResponser::send(true, "Email address not found", [], 400);
        }

        try {
            DB::beginTransaction();
            $email = $request->email;

            $verification_code = Str::random(30); //Generate verification code

            DB::table('password_reset_tokens')->where('email', $email)->delete();

            DB::table('password_reset_tokens')->insert([
                'email' => $email, 
                'token' => $verification_code, 
                'user_id' => $user->id,
                'created_at' => Carbon::now()]);

            $data = [
                'name' => $user->name,
                'email' => $email,
                'verification_code' => $verification_code,
            ];

            Notification::route('mail', $user->email)->notify(new PasswordResetNotification($data));

            DB::commit();
            return JsonResponser::send(false, "A reset email has been sent! Please check your email.", [], 200);

        } catch (\Exception $error) {
            DB::rollBack();
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
       
    }

    public function verifyResetPassword($token){

        try {
            //validate token
            $getToken = DB::table('password_reset_tokens')->where('token', $token)->first();
            if(is_null($getToken)){
                return JsonResponser::send(true, "Token is Invalid", [], 400);
            }

            return JsonResponser::send(false, "Token verified successfully", $token, 200);
            
        } catch (\Throwable $error) {
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    }

    public function adminResetPassword(Request $request)
    {
        try {
            $rules = [
                'password' =>  [
                    'required',
                    'string',
                    'min:8',             // must be at least 8 characters in length
                    'regex:/[a-z]/',      // must contain at least one lowercase letter
                    'regex:/[A-Z]/',      // must contain at least one uppercase letter
                    'regex:/[0-9]/',      // must contain at least one digit
                    'regex:/[@$!%*#?&]/', // must contain a special character
                ],
                "token" => "required",
            ];
            DB::beginTransaction();
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return JsonResponser::send(true, $validator->errors()->first(), [], 400);
            }

            $token = DB::table('password_reset_tokens')->where('token', $request->token)->first();

            if (!$token) {
                return JsonResponser::send(true, "Invalid token", [], 400);
            }

            $password = $request->password;
            $userdata = User::where('id', $token->user_id)->first();

            $hashedPasword = $userdata->password;
            // check if new password is not the same with old password
            if (Hash::check($password, $hashedPasword)) {
                return JsonResponser::send(true, "New password cannot be the same as old password", [], 400);
            }

            $updatePassword = $userdata->update([
                'password' => Hash::make($password),
            ]);
            DB::table('password_reset_tokens')->where('token', $request->token)->delete();
            if (!$updatePassword) {
                return JsonResponser::send(true, "Error occured password was not updated", [], 400);
            } else {
                $data = [
                    'email' => $userdata->email,
                    'name' => $userdata->name,
                    'subject' => "Password Updated Successfully.",
                ];
                DB::commit();
                return JsonResponser::send(false, "Password Updated! Please login with your new password", [], 200);
            }
        } catch (\Throwable $error) {
            DB::rollBack();
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    }
}
