<?php

namespace App\Http\Controllers\v1\Auth;

use App\Helpers\ProcessAuditLog;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Responser\JsonResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{

    public function register(Request $request)
    {
        $validateRequest = $this->validateRegistrationRequest($request);

        if ($validateRequest->fails()) {
            return JsonResponser::send(true, $validateRequest->errors()->first(), $validateRequest->errors()->all(), 400);
        }

        try {
            DB::beginTransaction();

            //check if email already exist
            $recordExist = User::where('email', $request->email)->first();

            if (!is_null($recordExist)) {
                return JsonResponser::send(true, "User email number already exist!", [], 400);
            }

            $adminRole = config('roles.models.role')::where('name', '=', 'Admin')->first();

            $record = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'country_code' => $request->country_code,
                'phoneno' => $request->phoneno,
                'home_address' => $request->home_address,
                'occupation' => $request->occupation,
                'username' => Str::slug($request->name) . rand(10, 99), // Add two random digits
                'password' => Hash::make($request->password),
                'email_verified_at' => now(),
                'is_active' => "Active",
                'is_verified' => "true",
                'can_login' => "true",
            ]);

            if (isset($adminRole)) {
                $record->attachRole($adminRole);
            }

            $dataToLog = [
                'causer_id' => $record->id,
                'action_id' => $record->id,
                'action_type' => "Models\User",
                'log_name' => "Account created Successfully",
                'action' => 'Create',
                'description' => "{$record->name} created account Successfully",
            ];

            ProcessAuditLog::storeAuditLog($dataToLog);

            DB::commit();
            return JsonResponser::send(false, 'Account created Successfully!', $record, 200);
        } catch (\Throwable $error) {
            DB::rollBack();
            logger($error);
            return JsonResponser::send(true, $error->getMessage(), [], 500);
        }
    }

    private function validateRegistrationRequest($request)
    {
        $rules = [
            'name' => 'required',
            'email' => 'required',
            'phoneno' => 'required',
            'password' => 'required'
        ];

        $validate = Validator::make($request->all(), $rules);
        return $validate;
    }
}
