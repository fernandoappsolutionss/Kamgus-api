<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TypesTransportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::insert('
        INSERT INTO `types_transports` (`id`, `nombre`, `m3`, `peso`, `precio_minuto`, `precio_ayudante`, `descripcion`, `foto`, `url_foto`, `tiempo`, `estado`, `app_icon`, `app_icon_selected`, `orden`, `created_at`, `updated_at`) VALUES
        (1, "Panel", 5.30, 0.80, 0.47, 15.00, "Vehículo ideal para traslado de mercancía en caja, encargos pequeños, repuestos pequeños. Incluye 1 ayudante. No incluye subir pisos esto se calculará como servicio extra.", "http://www.kamgus.com/images/20191213081213_photo.png", "https://ve.all.biz/img/ve/catalog/12294.jpeg", 1, 1, "https://www.kamgus.com/img/app-usuario/iconos/ICONOS-03.png", "https://www.kamgus.com/img/app-usuario/iconos/ICONOS-07.png", 3, NULL, NULL),
        (2, "Pick up", 4.50, 1.00, 0.45, 15.00, "Vehículo ideal para el traslado de mudanzas pequeñas (Nevera, Tv, Muebles pequeños, Cama, Cocinas, Caliche en pequeñas proporciones. incluye 1 ayudante carga y descarga. No incluye subir pisos esto se calculará como servicio extra.", "http://www.kamgus.com/images/20191213081238_photo.png", "http://www.kamgus.com/images/20200507100510_photo.png", 1, 1, "https://www.kamgus.com/img/app-usuario/iconos/ICONOS-04.png", "https://www.kamgus.com/img/app-usuario/iconos/ICONOS-08.png", 4, NULL, NULL),
        (3, "Camión Pequeño", 45.00, 4.00, 0.75, 30.00, "Vehículo ideal para mudanzas pequeñas, transportar gran cantidad de mercancía, artículos de volumen mediano", "http://www.kamgus.com/images/20191213081208_photo.png", "http://www.kamgus.com/images/20200507100530_photo.png", 2, 1, "https://www.kamgus.com/img/app-usuario/iconos/ICONOS-05.png", "https://www.kamgus.com/img/app-usuario/iconos/ICONOS-09.png", 5, NULL, NULL),
        (4, "Camión Grande", 54.00, 7.00, 0.85, 40.00, "Vehículo ideal para mudanzas Grandes, transporta gran cantidad de mercancía, artículos de volumen Alto", "http://www.kamgus.com/images/20191213081242_photo.png", "https://www.kamgus.com/images/camion%20grande.jpg", 2, 1, "https://www.kamgus.com/img/app-usuario/iconos/ICONOS-06.png", "https://www.kamgus.com/img/app-usuario/iconos/ICONOS-10.png", 6, NULL, NULL),
        (5, "Moto", 0.10, 0.04, 0.05, 3.50, "Vehículo ideal para traslado de mensajería, pedidos pequeños y paquetería de bajo volumen", "http://www.kamgus.com/images/20200821170818_photo.png", "http://www.kamgus.com/images/20200821160817_photo.png", 1, 0, 0, 0, 1, NULL, NULL),
        (6, "Sedan", 0.50, 0.30, 0.08, 5.00, "Vehículo ideal para traslado de mercancía en caja, encargos pequeños, repuestos pequeños. No incluye subir pisos esto se calculará como servicio extra.", "http://www.kamgus.com/images/20200821170841_photo.png", "http://www.kamgus.com/images/20200821160802_photo.png", 1, 1, 0, 0, 2, NULL, NULL);
        ');
    }
}
