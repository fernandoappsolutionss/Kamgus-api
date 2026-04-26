<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoutesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            
            //primer punto
            $table->string('primer_punto');
            $table->float('latitud_primer_punto', 10, 8);
            $table->float('longitud_primer_punto', 10, 8);

            //segundo punto
            $table->string('segundo_punto');
            $table->float('latitud_segundo_punto', 10, 8);
            $table->float('longitud_segundo_punto', 10, 8);

            //tercer punto
            $table->string('tercer_punto')->nullable();
            $table->float('latitud_tercer_punto', 10, 8)->nullable();
            $table->float('longitud_tercer_punto', 10, 8)->nullable();

            $table->timestamps();

            $table->unsignedBigInteger('service_id');
            $table->foreign('service_id')->references('id')->on('services');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('routes');
    }
}
