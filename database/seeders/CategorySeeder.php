<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::insert('INSERT INTO `categories` (`id`, `name`, `url_imagen`, `created_at`, `updated_at`) VALUES
        (1, "Sala y Comedor", "https://apiv2.kamgus.com/public/uploads/categorias/salaycomedor.png", NULL, NULL),
        (2, "Dormitorio", "https://apiv2.kamgus.com/public/uploads/categorias/dormitorio.png", NULL, NULL),
        (3, "Cocina", "https://apiv2.kamgus.com/public/uploads/categorias/cocina.png", NULL, NULL),
        (4, "Exteriores", "https://apiv2.kamgus.com/public/uploads/categorias/exteriores.png", NULL, NULL),
        (5, "Construcción", "https://apiv2.kamgus.com/public/uploads/categorias/construccion.png", NULL, NULL);
        ');
    }
}
