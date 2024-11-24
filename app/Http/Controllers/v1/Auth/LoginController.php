<?php

namespace App\Http\Controllers\v1\Auth;

use App\Helpers\ProcessAuditLog;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Responser\JsonResponser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{

    public function login(Request $request)
    {
        $validateRequest = $this->validateLoginRequest($request);

        if($validateRequest->fails()){
            return JsonResponser::send(true, $validateRequest->errors()->first(), $validateRequest->errors()->all(), 400);
        }

        try {
            $credentials = request(['email', 'password']);

            $loginCheck = auth()->attempt($credentials);

            if (!$loginCheck) {
                return JsonResponser::send(true, "Incorrect email or password", [], 400);
            }

            $currentUserInstance = auth()->user();

            $token = JWTAuth::fromUser($currentUserInstance);
            
            // Check if email have been verified
            if (!$currentUserInstance->is_verified) {
                return JsonResponser::send(true, "Account not verified. Kindly verify your email", [], 400);
            }

            $dataToLog = [
                'causer_id' => $currentUserInstance->id,
                'action_id' => $currentUserInstance->id,
                'action_type' => "Models\User",
                'log_name' => "User logged in successfully",
                'action' => 'Update',
                'description' => "{$currentUserInstance->name} logged in successfully",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);

            $data = [
                'user' => $currentUserInstance,
                'accessToken' => $token,
                'tokenType' => 'Bearer'
            ];

            return JsonResponser::send(false, "You're logged in Successfully!", $data, 200);
        } catch (\Throwable $error) {
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {

        $currentUserInstance = auth()->user();

        $dataToLog = [
            'causer_id' => auth()->user()->id,
            'action_id' => auth()->user()->id,
            'action_type' => "Models\User",
            'log_name' => "User logged out successfully",
            'description' => "{$currentUserInstance->name} Logged out successfully",
        ];

        ProcessAuditLog::storeAuditLog($dataToLog);

        auth()->logout();

        return JsonResponser::send(false, 'Successfully logged out', null);
    }

    private function validateLoginRequest($request)
    {
        $rules = [
            'email' => 'required|exists:users,email',
            'password' => 'required',
        ];

        $validate = Validator::make($request->all(), $rules);
        return $validate;
    }
    
}
