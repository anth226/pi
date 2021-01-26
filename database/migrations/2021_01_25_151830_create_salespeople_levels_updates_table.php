<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSalespeopleLevelsUpdatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('salespeople_levels_updates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
	        $table->unsignedBigInteger('salespeople_id')->default(0);
	        $table->foreign('salespeople_id')->references('id')->on('salespeoples')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('salespeople_levels_updates');
    }
}
