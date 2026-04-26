<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('tiempo')->nullable();
            $table->string('kilometraje')->nullable();
            $table->dateTime('fecha_reserva')->nullable();
            $table->enum('tipo_transporte', ['MOTO','SEDAN','PANEL','PICK UP','CAMIÓN PEQUEÑO','CAMIÓN GRANDE']);
            $table->enum('tipo_servicio', ['SIMPLE','MUDANZA']);
            $table->enum('estado', ['ACTIVO','AGENDADO','INACTIVO','PENDIENTE','CANCELADO','ANULADO','TERMINADO','RESERVA','PROGRAMAR','REPETIR'])->default('PENDIENTE');
            $table->float('precio_real')->nullable();
            $table->float('precio_sugerido')->nullable();
            $table->enum('tipo_pago', ['Card', 'Efectivo']);
            $table->enum('pago', ['PENDIENTE', 'PAGADO', 'ANULADO', 'TRANSFERIDO'])->default('PENDIENTE');
            $table->string('descripcion')->nullable();
            $table->tinyInteger('assistant')->default('0');
            $table->boolean('borrado')->nullable();
            $table->timestamps();

            $table->unsignedBigInteger('customer_id');
            $table->foreign('customer_id')->references('id')->on('customers');
            
            $table->unsignedBigInteger('driver_id')->nullable();
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
        Schema::dropIfExists('services');
    }
}
