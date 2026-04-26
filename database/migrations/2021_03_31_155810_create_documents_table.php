<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['CEDULA', 'PASAPORTE', 'LICENCIA']);
            $table->string('numero')->nullable();
            $table->string('url_foto');
            $table->date('fecha_vencimiento')->nullable();
            $table->boolean('status')->nullable();
            $table->boolean('borrado')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('driver_id');
            $table->foreign('driver_id')->references('id')->on('drivers');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('documents');
    }
}
