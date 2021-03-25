<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePipedriveDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pipedrive_data', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
            $table->unsignedTinyInteger('field_name')->default(0)->index();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('pd_person_id');
            $table->unsignedBigInteger('pd_source_string_id')->nullable();
	        $table->foreign('customer_id')->references('id')->on('customers')->onUpdate('CASCADE');
	        $table->unique(['customer_id', 'field_name', 'pd_source_string_id'], 'un_field');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pipedrive_data');
    }
}
