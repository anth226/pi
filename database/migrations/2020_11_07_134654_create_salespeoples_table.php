<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSalespeoplesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('salespeoples', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('name_for_invoice');
            $table->string('email')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('formated_phone_number')->nullable();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('salespeoples');
    }
}
