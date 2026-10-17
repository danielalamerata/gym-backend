<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin por defecto (solo desarrollo), el mismo que creaba bootstrap.js en el front
        User::updateOrCreate(
            ['email' => 'admin@gym.local'],
            [
                'name' => 'Admin System',
                'nombre' => 'Admin',
                'apellido' => 'System',
                'dni' => '00000000',
                'password' => 'admin123',
                'rol' => 'admin',
            ]
        );
    }
}
