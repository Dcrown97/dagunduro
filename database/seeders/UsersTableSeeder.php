<?php

namespace Database\Seeders;

use App\Interfaces\UserStatusInterface;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $memberRole = config('roles.models.role')::where('name', '=', 'Member')->first();
        $adminRole = config('roles.models.role')::where('name', '=', 'Admin')->first();
        $permissions = config('roles.models.permission')::all();

        /*
         * Add Users
         *
         */

        if (User::where('email', '=', 'admin@dagunduro.com')->first() === null) {
            $newUser = User::create([
                'name'     => 'Dagunduro Admin',
                'email'    => 'admin@dagunduro.com',
                'country_code' => '+234',
                'phoneno' => '08067799281',
                'home_address' => NULL,
                'occupation' => NULL,
                'username' => 'admin123',
                'is_verified' => "true",
                'is_active' => UserStatusInterface::ACTIVE,
                'can_login' => "true",
                'email_verified_at' => now(),
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'remember_token' => Str::random(10),
            ]);

            $newUser->attachRole($adminRole);
            foreach ($permissions as $permission) {
                $newUser->attachPermission($permission);
            }
        }

        if (User::where('email', '=', 'member@dagunduro.com')->first() === null) {
            $newUser = User::create([
                'name'     => 'Dagunduro Member',
                'email'    => 'member@dagunduro.com',
                'country_code' => '+234',
                'phoneno' => '08129639401',
                'home_address' => '1234 Maple Street, Apt 5B, Springfield, IL 62704',
                'occupation' => 'Software Engineer',
                'username' => 'member123',
                'is_verified' => "true",
                'is_active' => UserStatusInterface::ACTIVE,
                'can_login' => "true",
                'email_verified_at' => now(),
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'remember_token' => Str::random(10),
            ]);

            $newUser->attachRole($memberRole);
        }
    }
}
