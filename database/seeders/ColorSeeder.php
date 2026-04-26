<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Color;

class ColorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Color::create(['name' => 'Rojo']);
        Color::create(['name' => 'Negro']);
        Color::create(['name' => 'Azul']);
        Color::create(['name' => 'Amarillo']);
        Color::create(['name' => 'Blanco']);
        Color::create(['name' => 'Gris']);
        Color::create(['name' => 'Verde']);
        Color::create(['name' => 'Naranja']);
    }
}
