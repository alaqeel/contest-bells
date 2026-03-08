<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Create the default super admin account.
     *
     * Run with: php artisan db:seed --class=SuperAdminSeeder
     *
     * Change credentials in .env or update here before first deploy.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@contest-bells.local')],
            [
                'name'     => env('ADMIN_NAME', 'Super Admin'),
                'email'    => env('ADMIN_EMAIL', 'admin@contest-bells.local'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
                'role'     => 'super_admin',
            ]
        );

        $this->command->info('Super admin account ready.');
    }
}
