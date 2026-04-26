<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class SubCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::insert('
        INSERT INTO `sub_categories` (`id`, `name`, `url_imagen`, `created_at`, `updated_at`, `category_id`) VALUES
        (1, "Sofa", "https://www.kamgus.com/img/app-usuario/iconos/20200123100107_NV2_ICONO.png", NULL, NULL, 1),
        (2, "Lámpara", "https://www.kamgus.com/img/app-usuario/iconos/20200124220153_NV2_ICONO.png", NULL, NULL, 1),
        (3, "Mesa", "https://www.kamgus.com/img/app-usuario/iconos/ICONOS-23.png", NULL, NULL, 1),
        (4, "Comedor", "https://www.kamgus.com/img/app-usuario/iconos/ICONOS-24.png", NULL, NULL, 1),
        (6, "Cama", "https://www.kamgus.com/img/app-usuario/iconos/ICONOS-11.png", NULL, NULL, 2),
        (7, "Colchon", "https://www.kamgus.com/img/app-usuario/iconos/ICONOS-12.png", NULL, NULL, 2),
        (8, "Espejo", "https://www.kamgus.com/img/app-usuario/iconos/ICONOS-13.png", NULL, NULL, 2),
        (9, "Escritorio", "https://www.kamgus.com/img/app-usuario/iconos/ICONOS-14.png", NULL, NULL, 2),
        (11, "Estufa", "https://www.kamgus.com/img/app-usuario/iconos/20200124220122_NV2_ICONO.png", NULL, NULL, 3),
        (14, "Lavanderia", "https://www.kamgus.com/img/app-usuario/iconos/20200124220129_NV2_ICONO.png", NULL, NULL, 3),
        (21, "Sacos de Construcción", "https://www.kamgus.com/img/app-usuario/iconos/20200211180256_NV2_ICONO.png", NULL, NULL, 5),
        (23, "Pinturas", "https://www.kamgus.com/img/app-usuario/iconos/20200131090107_NV2_ICONO.png", NULL, NULL, 5),
        (27, "Nevera", "https://www.kamgus.com/img/app-usuario/iconos/20200124220147_NV2_ICONO.png", NULL, NULL, 3),
        (29, "Sillas", "https://www.kamgus.com/img/app-usuario/iconos/20200124220101_NV2_ICONO.png", NULL, NULL, 1),
        (31, "Mueble TV", "https://www.kamgus.com/img/app-usuario/iconos/20200210090211_NV2_ICONO.png", NULL, NULL, 1),
        (32, "TV", "https://www.kamgus.com/img/app-usuario/iconos/20200210090239_NV2_ICONO.png", NULL, NULL, 1),
        (33, "Cajas", "https://www.kamgus.com/img/app-usuario/iconos/20200211180238_NV2_ICONO.png", NULL, NULL, 1),
        (34, "Cajas", "https://www.kamgus.com/img/app-usuario/iconos/20200211180201_NV2_ICONO.png", NULL, NULL, 2),
        (35, "Cajas", "https://www.kamgus.com/img/app-usuario/iconos/20200211180225_NV2_ICONO.png", NULL, NULL, 3),
        (37, "Cajas", "https://www.kamgus.com/img/app-usuario/iconos/20200211180258_NV2_ICONO.png", NULL, NULL, 5),
        (38, "Aires Acondicionados", "https://www.kamgus.com/img/app-usuario/iconos/20200213230236_NV2_ICONO.png", NULL, NULL, 1),
        (39, "Aires Acondicionados", "https://www.kamgus.com/img/app-usuario/iconos/20200213230249_NV2_ICONO.png", NULL, NULL, 2);');
    }
}
