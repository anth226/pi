<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('address_1')->nullable();
            $table->string('address_2')->nullable();
            $table->string('zip')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
	        $table->string('email')->nullable();
	        $table->string('password')->nullable();
	        $table->string('phone_number')->nullable();
	        $table->string('formated_phone_number')->nullable();
	        $table->unsignedInteger('cc')->default(0);
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
        Schema::dropIfExists('customers');
    }
}
