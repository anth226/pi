<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePiPersonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pi_persons', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
            $table->unsignedBigInteger('ownerId')->index();
            $table->unsignedBigInteger('personId')->index();
            $table->string('name')->nullable();
            $table->string('timezone')->nullable();
            $table->timestamp('addTime', 0)->nullable();
            $table->string('label')->nullable();
            $table->string('extra_field')->nullable();
            $table->json('source_field')->nullable();
            $table->json('phone')->nullable();
            $table->json('email')->nullable();
            $table->json('persons_data')->nullable();
            $table->unique(['ownerId', 'personId']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pi_persons');
    }
}
