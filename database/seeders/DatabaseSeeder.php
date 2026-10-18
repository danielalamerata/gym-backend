<?php

namespace Database\Seeders;

use App\Models\Plan;
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

        // Planes de ejemplo, los mismos que creaba bootstrap.js, con precios de ejemplo.
        $planes = [
            ['nombre' => 'Básico', 'frecuencia_semanal' => 2, 'precio' => 15000],
            ['nombre' => 'Plus', 'frecuencia_semanal' => 3, 'precio' => 20000],
            ['nombre' => 'Premium', 'frecuencia_semanal' => 4, 'precio' => 25000],
        ];

        foreach ($planes as $plan) {
            Plan::updateOrCreate(
                ['nombre' => $plan['nombre']],
                ['frecuencia_semanal' => $plan['frecuencia_semanal'], 'precio' => $plan['precio']]
            );
        }
    }
}
