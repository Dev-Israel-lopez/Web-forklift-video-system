<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        // Crea dos usuarios base (sin api_token inicial)
        User::updateOrCreate(
            ['name' => 'operator1'],
            [
                // Si tu tabla users tiene email como NOT NULL, añade un correo dummy único
                'email' => 'operator1@example.com',
                'password' => Hash::make('secret123'),
                'api_token' => null,
            ]
        );

        User::updateOrCreate(
            ['name' => 'operator2'],
            [
                'email' => 'operator2@example.com',
                'password' => Hash::make('secret123'),
                'api_token' => null,
            ]
        );
    }
}
