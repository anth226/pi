<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActionsLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('actions_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
            $table->unsignedBigInteger('user_id');
            $table->unsignedTinyInteger('model')->default(0)->index();
            $table->unsignedTinyInteger('action')->default(0);
            $table->unsignedBigInteger('related_id')->default(0)->index();
            $table->string('field_name')->nullable();
            $table->string('old_value', 2000)->nullable();
            $table->string('new_value', 2000)->nullable();
	        $table->foreign('user_id')->references('id')->on('users')->onUpdate('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('actions_logs');
    }
}
