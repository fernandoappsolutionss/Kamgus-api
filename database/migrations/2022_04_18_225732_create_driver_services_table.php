<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDriverServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('driver_services', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('service_id')->nullable();
            $table->foreign('service_id')->references('id')->on('services');

            $table->unsignedBigInteger('driver_id')->nullable();
            $table->foreign('driver_id')->references('id')->on('drivers');

            $table->dateTime('startTime');

            $table->dateTime('endTime');

            $table->enum('status', ['Pendiente', 'Terminado', 'En curso', 'Agendado', 'Rechazado']);

            $table->enum('confirmed', ['SI', 'NO']);

            $table->dateTime('reservation_date');

            $table->string('observation');

            $table->float('precio_sugerido')->nullable();

            $table->enum('ispaid', ['Pagado', 'Pendiente', 'Omitido', 'Pagado Kamgus']);

            $table->string('commission');

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
        Schema::dropIfExists('driver_services');
    }
}
