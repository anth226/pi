<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePhoneNumbersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('phone_numbers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
            $table->unsignedTinyInteger('isActive')->default(1)->index();
            $table->string('friendlyName')->nullable();
            $table->string('phoneNumber')->nullable()->index();
            $table->string('sid')->nullable();
            $table->unsignedBigInteger('buyer_user_id')->nullable()->index();
            $table->unsignedBigInteger('owner_user_id')->nullable()->index();
	        $table->softDeletes();
	        $table->foreign('buyer_user_id')->references('id')->on('users')->onUpdate('CASCADE');
	        $table->foreign('owner_user_id')->references('id')->on('users')->onUpdate('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('phone_numbers');
    }
}
