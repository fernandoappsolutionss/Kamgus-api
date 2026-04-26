<?php

namespace Database\Seeders;

use App\Models\Mark;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MarkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $array = [ 
            "Volvo", "Volkswagen", "Toyota", "Suzuki", "Subaru", "SsangYong", "SEAT", "Renault",
            "Peugeot", "Nissan", "Mitsubishi", "Mercedes-Benz", "Mazda", "Land Rover", "Kia", "JMC", "Jeep",
            "JAC", "Honda", "Ford", "Fiat", "Dacia", "Chevrolet", "BMW",
        ];

        foreach ($array as $value) {
            $mark = new Mark();
            $mark->name = $value;
            $mark->save();
        }

    }
}
