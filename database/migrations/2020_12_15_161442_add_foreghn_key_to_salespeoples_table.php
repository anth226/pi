<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeghnKeyToSalespeoplesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	    Schema::table('salespeoples', function (Blueprint $table) {
		    $table->foreign('level_id')->references('id')->on('salespeople_levels')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('salespeoples', function (Blueprint $table) {
	         $table->dropForeign('salespeoples_level_id_foreign');
        });
    }
}
