<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Rol;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UsuariosPruebaSeeder extends Seeder
{
    public function run()
    {
        // Desactivar llaves foráneas para limpiar
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('usuarios')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. Crear el Administrador
        // Usamos DB::table para evitar problemas con los timestamps automáticos de Eloquent si no están configurados
        DB::table('usuarios')->insert([
            'nombre' => 'Admin',
            'apellido_paterno' => 'Solare',
            'correo' => 'admin@solare.com',
            'contrasena' => Hash::make('password123'),
            'rol_id' => 1, // ID del Administrador en tu tabla roles
            'creado_en' => now(),
            'actualizado_en' => now()
        ]);

        // 2. Crear un Cliente
        DB::table('usuarios')->insert([
            'nombre' => 'Cliente',
            'apellido_paterno' => 'Prueba',
            'correo' => 'cliente@solare.com',
            'contrasena' => Hash::make('password123'),
            'rol_id' => 3, // ID del Cliente en tu tabla roles
            'creado_en' => now(),
            'actualizado_en' => now()
        ]);
    }
}
