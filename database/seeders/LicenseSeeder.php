<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\License;

class LicenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        License::create(['name' => 'FREE', 'price' => '0']);
        License::create(['name' => 'STARTUP', 'price' => '25']);

    }
}
