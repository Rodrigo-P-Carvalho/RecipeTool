<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@recipetool.com')],
            [
                'name' => env('ADMIN_NAME', 'Administrador'),
                'password' => env('ADMIN_PASSWORD', 'password'),
                'email_verified_at' => now(),
            ],
        );
    }
}
