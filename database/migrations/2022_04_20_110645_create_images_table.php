<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->enum('is', [
                'profile', 
                'photo_url_vehicle', 
                'back_url_vehicle', 
                'right_url_vehicle', 
                'left_url_vehicle', 
                'property_url_vehicle', 
                'revised_url_vehicle', 
                'policy_url_vehicle', 
                'service_detail'
            ]);
            $table->integer('imageable_id');
            $table->string('imageable_type');
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
        Schema::dropIfExists('images');
    }
}
