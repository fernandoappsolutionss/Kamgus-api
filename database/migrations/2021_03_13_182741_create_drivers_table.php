<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDriversTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('nombres');
            $table->string('apellidos');
            $table->string('telefono')->unique();
            $table->string('direccion')->nullable();
            $table->string('url_foto_perfil')->nullable();
            $table->boolean('borrado')->nullable();

            $table->unsignedBigInteger('document_types_id')->nullable();
            $table->foreign('document_types_id')->references('id')->on('document_types');

            $table->string('document_number')->nullable();

            $table->enum('status', ['F','G','E'])->nullable();

            $table->enum('status_driver', ['A','I','P'])->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('drivers');
    }
}
