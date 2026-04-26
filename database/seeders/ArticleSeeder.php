<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
       DB::insert('
       INSERT INTO `articles` (`id`, `name`, `url_imagen`, `m3`, `altura`, `ancho`, `largo`, `price`, `created_at`, `updated_at`, `sub_category_id`) VALUES
       (80, "Sofá 2 puesto", "https://www.kamgus.com/img/app-usuario/iconos/20200203090216_NV2_ICONO.png", 2.3, 90.00, 120.00, 90.00, 10.00, NULL, NULL, 1),
       (81, "Sofa 3 puestos", "https://www.kamgus.com/img/app-usuario/iconos/20200203090205_NV2_ICONO.png", 3.1, 90.00, 180.00, 90.00, 15.00, NULL, NULL, 1),
       (82, "Taburete", "https://www.kamgus.com/img/app-usuario/iconos/20200211090240_NV2_ICONO.png", 0.5, 100.00, 50.00, 50.00, 0.50, NULL, NULL, 29),
       (83, "Sofa 1 puesto", "https://www.kamgus.com/img/app-usuario/iconos/20200203090259_NV2_ICONO.png", 0.7, 90.00, 30.00, 90.00, 0.50, NULL, NULL, 1),
       (84, "Lampara de decoracion", "https://www.kamgus.com/img/app-usuario/iconos/20200210090212_NV2_ICONO.png", 0.1, 0.50, 0.50, 0.50, 0.10, NULL, NULL, 2),
       (85, "Nevera 2 puerta Sencilla", "https://www.kamgus.com/img/app-usuario/iconos/20200124220143_NV3_ICONO.png", 4, 177.00, 91.00, 72.00, 20.00, NULL, NULL, 27),
       (86, "Sofa en L", "https://www.kamgus.com/img/app-usuario/iconos/20200131210133_NV2_ICONO.png", 3.2, 90.00, 240.00, 90.00, 15.00, NULL, NULL, 1),
       (87, "Sofa XL", "https://www.kamgus.com/img/app-usuario/iconos/20200131210157_NV2_ICONO.png", 4, 90.00, 300.00, 90.00, 20.00, NULL, NULL, 1),
       (88, "Nevera 2 puertas", "https://www.kamgus.com/img/app-usuario/iconos/20200124220110_NV3_ICONO.png", 4.5, 180.00, 98.00, 72.00, 25.00, NULL, NULL, 27),
       (89, "Vinera Grande", "https://www.kamgus.com/img/app-usuario/iconos/20200124220131_NV2_ICONO.png", 2, 83.00, 50.00, 45.00, 8.00, NULL, NULL, 27),
       (90, "Vinera pequeña", "https://www.kamgus.com/img/app-usuario/iconos/20200124220105_NV3_ICONO.png", 1, 60.00, 45.00, 52.00, 5.00, NULL, NULL, 27),
       (91, "Estufa 4 hornilla", "https://www.kamgus.com/img/app-usuario/iconos/20200124220157_NV3_ICONO.png", 2, 90.00, 60.00, 60.00, 5.00, NULL, NULL, 11),
       (92, "Cocina grande 6 hornilla", "https://www.kamgus.com/img/app-usuario/iconos/20200124220154_NV3_ICONO.png", 4, 90.00, 80.00, 72.00, 8.00, NULL, NULL, 11),
       (93, "Silla pequeña de Escritorio (armada)", "https://www.kamgus.com/img/app-usuario/iconos/20200211090210_NV2_ICONO.png", 0.6, 100.00, 60.00, 60.00, 1.00, NULL, NULL, 29),
       (94, "Frezzer Pequeño", "https://www.kamgus.com/img/app-usuario/iconos/20200131210146_NV3_ICONO.png", 0.4, 86.00, 90.00, 56.00, 5.00, NULL, NULL, 27),
       (95, "Nevera de Pequeña (Bar)", "https://www.kamgus.com/img/app-usuario/iconos/20200131210106_NV2_ICONO.png", 0.17, 90.00, 65.00, 65.00, 5.00, NULL, NULL, 27),
       (96, "Nevera 1 Puerta", "https://www.kamgus.com/img/app-usuario/iconos/20200131210118_NV3_ICONO.png", 3.1, 165.00, 65.00, 65.00, 15.00, NULL, NULL, 27),
       (97, "0.75 L", "https://www.kamgus.com/img/app-usuario/iconos/20200131210131_NV3_ICONO.png", 0.1, 0.00, 0.00, 0.00, 1.00, NULL, NULL, 23),
       (98, "2.5 L", "https://www.kamgus.com/img/app-usuario/iconos/20200131210133_NV3_ICONO.png", 0.2, 0.00, 0.00, 0.00, 1.00, NULL, NULL, 23),
       (99, "4.5 L", "https://www.kamgus.com/img/app-usuario/iconos/20200131210148_NV3_ICONO.png", 0.2, 0.00, 0.00, 0.00, 1.00, NULL, NULL, 23),
       (100, "Mesa de Centro Pequeña", "https://www.kamgus.com/img/app-usuario/iconos/20200211180250_NV2_ICONO.png", 0.3, 50.00, 70.00, 70.00, 3.00, NULL, NULL, 3),
       (101, "Mesa de Noche", "https://www.kamgus.com/img/app-usuario/iconos/20200210090237_NV2_ICONO.png", 0.2, 60.00, 60.00, 60.00, 4.00, NULL, NULL, 6),
       (102, "Mueble TV Pequeño", "https://www.kamgus.com/img/app-usuario/iconos/20200211090206_NV2_ICONO.png", 2.3, 60.00, 150.00, 60.00, 20.00, NULL, NULL, 31),
       (103, "Mueble TV mediano (armado)", "https://www.kamgus.com/img/app-usuario/iconos/20200211090218_NV2_ICONO.png", 3.4, 120.00, 160.00, 60.00, 30.00, NULL, NULL, 31),
       (104, "Mueble Tv Grande (armado)", "https://www.kamgus.com/img/app-usuario/iconos/20200211090256_NV2_ICONO.png", 3.3, 200.00, 190.00, 60.00, 30.00, NULL, NULL, 31),
       (105, "Tv de 55", "https://www.kamgus.com/img/app-usuario/iconos/20200211180228_NV2_ICONO.png", 0.4, 76.00, 130.00, 9.00, 7.00, NULL, NULL, 32),
       (106, "Tv de 42", "https://www.kamgus.com/img/app-usuario/iconos/20200211180229_NV2_ICONO.png", 0.3, 66.00, 108.00, 10.00, 5.00, NULL, NULL, 32),
       (107, "Tv de 32", "https://www.kamgus.com/img/app-usuario/iconos/20200211180253_NV2_ICONO.png", 0.2, 51.00, 88.00, 10.00, 3.00, NULL, NULL, 32),
       (108, "Colchon Twin", "https://www.kamgus.com/img/app-usuario/iconos/20200210090204_NV2_ICONO.png", 0.5, 40.00, 100.00, 190.00, 10.00, NULL, NULL, 7),
       (109, "Colchon Full", "https://www.kamgus.com/img/app-usuario/iconos/20200210090233_NV2_ICONO.png", 3, 140.00, 190.00, 40.00, 15.00, NULL, NULL, 7),
       (110, "Colchon Queen", "https://www.kamgus.com/img/app-usuario/iconos/20200210090232_NV2_ICONO.png", 3.6, 40.00, 160.00, 200.00, 20.00, NULL, NULL, 7),
       (111, "Colchon King Presidencial", "https://www.kamgus.com/img/app-usuario/iconos/20200210090252_NV2_ICONO.png", 4, 40.00, 200.00, 200.00, 25.00, NULL, NULL, 7),
       (112, "Comedor 4 puestos", "https://www.kamgus.com/img/app-usuario/iconos/20200211090244_NV3_ICONO.png", 1.5, 80.00, 150.00, 90.00, 15.00, NULL, NULL, 4),
       (113, "Comedor 6 puestos", "https://www.kamgus.com/img/app-usuario/iconos/20200211090228_NV3_ICONO.png", 2.5, 80.00, 180.00, 90.00, 20.00, NULL, NULL, 4),
       (114, "Mesa de Centro Mediana", "https://www.kamgus.com/img/app-usuario/iconos/20200211180235_NV3_ICONO.png", 0.4, 50.00, 110.00, 60.00, 4.00, NULL, NULL, 3),
       (115, "Consola", "https://www.kamgus.com/img/app-usuario/iconos/20200211180220_NV3_ICONO.png", 0.8, 100.00, 100.00, 80.00, 8.00, NULL, NULL, 3),
       (116, "Silla de Comedor", "https://www.kamgus.com/img/app-usuario/iconos/20200211180214_NV3_ICONO.png", 0.6, 100.00, 50.00, 70.00, 5.00, NULL, NULL, 29),
       (117, "Sillon", "https://www.kamgus.com/img/app-usuario/iconos/20200211180258_NV3_ICONO.png", 0.6, 120.00, 100.00, 100.00, 8.00, NULL, NULL, 1),
       (118, "Peinadora", "https://www.kamgus.com/img/app-usuario/iconos/20200211180217_NV2_ICONO.png", 2, 150.00, 100.00, 50.00, 10.00, NULL, NULL, 8),
       (119, "Closet Pequeño Armado", "https://www.kamgus.com/img/app-usuario/iconos/20200211180200_NV3_ICONO.png", 2.2, 180.00, 120.00, 50.00, 20.00, NULL, NULL, 8),
       (120, "Silla Plegable", "https://www.kamgus.com/img/app-usuario/iconos/20200211180244_NV3_ICONO.png", 0.01, 0.00, 0.00, 0.00, 1.00, NULL, NULL, 29),
       (121, "Silla Apilable", "https://www.kamgus.com/img/app-usuario/iconos/20200211180243_NV3_ICONO.png", 0.1, 0.00, 0.00, 0.00, 0.10, NULL, NULL, 29),
       (122, "Mesa Plegable", "https://www.kamgus.com/img/app-usuario/iconos/20200211180240_NV3_ICONO.png", 0.2, 80.00, 100.00, 70.00, 1.00, NULL, NULL, 3),
       (123, "Silla de Escritorio Mediana (armada)", "https://www.kamgus.com/img/app-usuario/iconos/20200211180246_NV3_ICONO.png", 0.6, 120.00, 63.00, 60.00, 3.00, NULL, NULL, 29),
       (124, "Literas", "https://www.kamgus.com/img/app-usuario/iconos/20200211180254_NV3_ICONO.png", 2.5, 180.00, 120.00, 190.00, 35.00, NULL, NULL, 6),
       (125, "Escritorio Pequeño", "https://www.kamgus.com/img/app-usuario/iconos/20200213230221_NV2_ICONO.png", 0.8, 80.00, 100.00, 50.00, 15.00, NULL, NULL, 9),
       (126, "Cama Twin", "https://www.kamgus.com/img/app-usuario/iconos/20200211180204_NV3_ICONO.png", 0.8, 40.00, 100.00, 190.00, 10.00, NULL, NULL, 6),
       (127, "Cama Full", "https://www.kamgus.com/img/app-usuario/iconos/20200211180255_NV3_ICONO.png", 0.9, 40.00, 140.00, 190.00, 15.00, NULL, NULL, 6),
       (128, "Cama Queen", "https://www.kamgus.com/img/app-usuario/iconos/20200211180250_NV3_ICONO.png", 1.3, 40.00, 160.00, 200.00, 20.00, NULL, NULL, 6),
       (129, "Cama King", "https://www.kamgus.com/img/app-usuario/iconos/20200211180219_NV2_ICONO.png", 2, 40.00, 200.00, 200.00, 25.00, NULL, NULL, 6),
       (130, "Espejo de Pie", "https://www.kamgus.com/img/app-usuario/iconos/20200211180247_NV3_ICONO.png", 0.4, 120.00, 60.00, 5.00, 5.00, NULL, NULL, 8),
       (131, "Lámpara de Techo", "https://www.kamgus.com/img/app-usuario/iconos/20201104111146_NV3_ICONO.png", 0.5, 0.20, 0.20, 2.00, 0.10, NULL, NULL, 2),
       (132, "Escritorio Mediano", "https://www.kamgus.com/img/app-usuario/iconos/20200211180248_NV3_ICONO.png", 0.6, 80.00, 140.00, 100.00, 12.00, NULL, NULL, 9),
       (133, "Saco hasta 10Kg", "https://www.kamgus.com/img/app-usuario/iconos/20200211180236_NV3_ICONO.png", 0.3, 0.00, 0.00, 0.00, 3.00, NULL, NULL, 21),
       (134, "Saco hasta 30Kg", "https://www.kamgus.com/img/app-usuario/iconos/20200211180259_NV3_ICONO.png", 0.6, 0.00, 0.00, 0.00, 5.00, NULL, NULL, 21),
       (135, "Saco hasta 50Kg", "https://www.kamgus.com/img/app-usuario/iconos/20200211180243_NV3_ICONO.png", 0.6, 0.00, 0.00, 0.00, 6.00, NULL, NULL, 21),
       (136, "Caja Chica (40 cms aproximado)", "https://www.kamgus.com/img/app-usuario/iconos/20200211210255_NV3_ICONO.png", 0.18, 30.00, 35.00, 40.00, 1.00, NULL, NULL, 33),
       (137, "Caja Chica (40 cms aproximado)", "https://www.kamgus.com/img/app-usuario/iconos/20200211210232_NV3_ICONO.png", 0.18, 30.00, 35.00, 40.00, 1.00, NULL, NULL, 34),
       (138, "Caja Chica (40 cms aproximado)", "https://www.kamgus.com/img/app-usuario/iconos/20200211210259_NV3_ICONO.png", 0.18, 30.00, 35.00, 40.00, 1.00, NULL, NULL, 35),
       (139, "Caja Chica (40 cms aproximado)", "https://www.kamgus.com/img/app-usuario/iconos/20200211210231_NV3_ICONO.png", 0.18, 30.00, 35.00, 40.00, 1.00, NULL, NULL, 37),
       (140, "Caja Mediana (60 cms aproximado)", "https://www.kamgus.com/img/app-usuario/iconos/20200211210248_NV3_ICONO.png", 0.54, 30.00, 50.00, 60.00, 3.00, NULL, NULL, 33),
       (141, "Caja Mediana (60 cms aproximado)", "https://www.kamgus.com/img/app-usuario/iconos/20200211210226_NV3_ICONO.png", 0.54, 30.00, 50.00, 60.00, 3.00, NULL, NULL, 34),
       (142, "Caja Mediana (60 cms aproximado)", "https://www.kamgus.com/img/app-usuario/iconos/20200211210201_NV3_ICONO.png", 0.54, 30.00, 50.00, 60.00, 3.00, NULL, NULL, 37),
       (143, "Caja Grande (80 cms aproximado)", "https://www.kamgus.com/img/app-usuario/iconos/20200211210253_NV3_ICONO.png", 0.11, 30.00, 44.00, 80.00, 5.00, NULL, NULL, 33),
       (144, "Caja Grande (80 cms aproximado)", "https://www.kamgus.com/img/app-usuario/iconos/20200211210246_NV3_ICONO.png", 0.11, 30.00, 44.00, 80.00, 5.00, NULL, NULL, 34),
       (145, "Caja Mediana (60 cms aproximado)", "https://www.kamgus.com/img/app-usuario/iconos/20200211210211_NV3_ICONO.png", 0.11, 30.00, 50.00, 60.00, 5.00, NULL, NULL, 35),
       (146, "Caja Grande (80 cms aproximado)", "https://www.kamgus.com/img/app-usuario/iconos/20200211210238_NV3_ICONO.png", 0.11, 30.00, 44.00, 80.00, 5.00, NULL, NULL, 35),
       (147, "Caja Grandes (80 cms aproximado)", "https://www.kamgus.com/img/app-usuario/iconos/20200211210241_NV3_ICONO.png", 0.11, 30.00, 44.00, 80.00, 5.00, NULL, NULL, 37),
       (148, "Aire Acondicionado (Ventanas)", "https://www.kamgus.com/img/app-usuario/iconos/20200213230220_NV3_ICONO.png", 0.6, 70.00, 90.00, 90.00, 10.00, NULL, NULL, 38),
       (149, "Aire Acondicionado Split 12BTU", "https://www.kamgus.com/img/app-usuario/iconos/20200213230234_NV3_ICONO.png", 0.5, 80.00, 80.00, 30.00, 10.00, NULL, NULL, 38),
       (150, "Aire Acondicionado Split 18BTU", "https://www.kamgus.com/img/app-usuario/iconos/20200213230205_NV3_ICONO.png", 1, 100.00, 100.00, 40.00, 15.00, NULL, NULL, 38),
       (151, "Aire Acondicionado Split 24BTU", "https://www.kamgus.com/img/app-usuario/iconos/20200213230242_NV3_ICONO.png", 1.5, 110.00, 110.00, 40.00, 20.00, NULL, NULL, 38),
       (152, "Aire Acondicionado (Ventanas)", "https://www.kamgus.com/img/app-usuario/iconos/20200213230228_NV3_ICONO.png", 0.6, 0.00, 0.00, 0.00, 10.00, NULL, NULL, 39),
       (153, "Aire Acondicionado Split 12BTU", "https://www.kamgus.com/img/app-usuario/iconos/20200213230201_NV3_ICONO.png", 0.5, 80.00, 80.00, 40.00, 10.00, NULL, NULL, 39),
       (154, "Aire Acondicionado Split 18BTU", "https://www.kamgus.com/img/app-usuario/iconos/20200213230223_NV3_ICONO.png", 1, 100.00, 100.00, 40.00, 15.00, NULL, NULL, 39),
       (155, "Aire Acondicionado Split 24BTU", "https://www.kamgus.com/img/app-usuario/iconos/20200213230248_NV3_ICONO.png", 1.5, 110.00, 110.00, 40.00, 20.00, NULL, NULL, 39),
       (156, "Lavadora o Secadora", "https://www.kamgus.com/img/app-usuario/iconos/20200815140847_NV2_ICONO.png", 1, 110.00, 70.00, 70.00, 20.00, NULL, NULL, 14),
       (157, "Centro de lavado", "https://www.kamgus.com/img/app-usuario/iconos/20200815140833_NV2_ICONO.png", 1, 200.00, 80.00, 70.00, 30.00, NULL, NULL, 14),
       (158, "Alfombra Grande", "https://www.kamgus.com/img/app-usuario/iconos/20201104111154_NV3_ICONO.png", 1, 0.60, 0.60, 0.60, 0.10, NULL, NULL, 3);');

    }
}
