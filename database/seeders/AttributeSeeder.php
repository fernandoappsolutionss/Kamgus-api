<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attribute;

class AttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Attribute::create(['item' => 'Servicios multipunto']);
        Attribute::create(['item' => 'Cotizar por articulos y vehiculos']);
        Attribute::create(['item' => 'Billetera virtual']);
        Attribute::create(['item' => 'Historial de servicios']);
        Attribute::create(['item' => 'Kamgus store']);
        Attribute::create(['item' => 'Sistema de carga consolidada']);
        Attribute::create(['item' => 'Transacciones en Kamgus store 10%']);
        Attribute::create(['item' => 'Api integracion web (200 USD)']);
        Attribute::create(['item' => 'Asesor personal']);
        Attribute::create(['item' => 'Herramientas básicas']);

    }
}
