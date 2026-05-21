<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Admin;
use App\Models\Aplicacion;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin inicial
        Admin::updateOrCreate(
            ['username' => 'admin'],
            [
                'username' => 'admin',
                'email'    => 'admin@iespacifico.es',
                'password' => Hash::make('admin1234'),
            ]
        );

        // Aplicaciones iniciales
        $apps = [
            [
                'nombre'      => 'Gestión FFE',
                'codigo'      => 'ffe',
                'descripcion' => 'Gestión de Empresas con Convenio — Formación en Empresa',
                'url'         => 'http://172.16.90.55',
                'color'       => '#10B981',
                'orden'       => 1,
                'activo'      => true,
            ],
            [
                'nombre'      => 'Gestión Guardias',
                'codigo'      => 'guardias',
                'descripcion' => 'Gestión de Guardias de Profesores',
                'url'         => 'http://172.16.90.55:8081',
                'color'       => '#F59E0B',
                'orden'       => 2,
                'activo'      => true,
            ],
            [
                'nombre'      => 'Gestión Inventarios',
                'codigo'      => 'inventarios',
                'descripcion' => 'Gestión de Inventarios y Préstamos',
                'url'         => 'http://172.16.90.55:8082',
                'color'       => '#6366F1',
                'orden'       => 3,
                'activo'      => true,
            ],
        ];

        foreach ($apps as $app) {
            Aplicacion::updateOrCreate(['codigo' => $app['codigo']], $app);
        }
    }
}
