<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTypesTransportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('types_transports', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->double('m3', 5, 2);
            $table->double('peso', 5, 2);
            $table->double('precio_minuto', 5, 2);
            $table->double('precio_ayudante', 5, 2);
            $table->string('descripcion');
            $table->string('foto');
            $table->string('url_foto');
            $table->integer('tiempo');
            $table->boolean('estado');
            $table->string('app_icon');
            $table->string('app_icon_selected');
            $table->integer('orden');
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
        Schema::dropIfExists('types_transports');
    }
}
