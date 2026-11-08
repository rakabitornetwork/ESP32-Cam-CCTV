<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Plan::ensureDefaults();

        $email = config('cctv.admin_email');
        $password = config('cctv.admin_password');

        if (! is_string($email) || $email === '' || ! is_string($password) || $password === '') {
            return;
        }

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => config('cctv.admin_name', 'Admin'),
                'password' => $password,
                'is_admin' => true,
                'email_verified_at' => now(),
            ],
        );

        if ($user->organizations()->doesntExist()) {
            Organization::provisionFor($user);
        }
    }
}
