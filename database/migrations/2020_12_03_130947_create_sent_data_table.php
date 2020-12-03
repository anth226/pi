<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSentDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sent_data', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
	        $table->unsignedBigInteger('customer_id')->default(0)->index();
	        $table->string('service_name', 20)->nullable();
	        $table->string('field', 20)->nullable();
	        $table->string('value', 20)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sent_data');
    }
}
