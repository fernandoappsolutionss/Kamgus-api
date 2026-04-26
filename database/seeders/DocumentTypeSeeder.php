<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DocumentType;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        
        $array = ['Cédula de Ciudadanía'];

        foreach ($array as $value) {
            $document_type = new DocumentType();
            $document_type->name = $value;
        }

    }
}
