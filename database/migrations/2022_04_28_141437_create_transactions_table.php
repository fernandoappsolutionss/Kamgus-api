<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');

            $table->unsignedBigInteger('transferable_id')->nullable();
            //$table->foreign('service_id')->references('id')->on('services');

            $table->enum('type', ['mastercard', 'amex', 'visa', 'discover']);
            
            $table->enum('gateway', ['stripe', 'yappy', 'pago_facil'])->nullable();
            
            $table->float('amount');
            
            $table->float('tax')->default(0);
            
            $table->float('total')->nullable();

            $table->float('accumulated_value')->nullable();

            $table->string('currency');

            $table->string('transaction_id');

            $table->enum('status', [
                'succeeded',
                'pending',
                'failed',
                'requires_payment_method',
                'requires_confirmation',
                'requires_action',
                'processing',
                'requires_capture',
                'canceled',
                'preordered',
                'open',
                'complete'
            ]);

            $table->string('receipt_url', 1500);

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
        Schema::dropIfExists('transactions');
    }
}
