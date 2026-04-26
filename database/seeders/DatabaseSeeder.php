<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            CategorySeeder::class,
            SubCategorySeeder::class,
            ArticleSeeder::class,
            TypesTransportSeeder::class,
            RoleSeeder::class,
            MarkSeeder::class,
            DocumentTypeSeeder::class,
            CountrySeeder::class,
            LicenseSeeder::class,
            AttributeSeeder::class,
            ModuleSeeder::class,
            ColorSeeder::class
        ]);
    }
}
