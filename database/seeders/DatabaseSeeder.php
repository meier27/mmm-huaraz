<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * No se siembran usuarios ni credenciales por defecto: el primer
     * administrador se crea con "php artisan app:create-admin".
     * Tampoco se siembra contenido ficticio como si fuera oficial.
     */
    public function run(): void
    {
        //
    }
}
