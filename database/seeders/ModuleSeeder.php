<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Module;

class ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Module::create(['module' => 'history']);
        Module::create(['module' => 'massive']);
        Module::create(['module' => 'payment-center']);
        Module::create(['module' => 'profile']);
        Module::create(['module' => 'referrals']);
        Module::create(['module' => 'services']);
        Module::create(['module' => 'store']);
        Module::create(['module' => 'wallet']);
    }
}
