<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLevelFieldToSalespeopleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('salespeoples', function (Blueprint $table) {
            $table->unsignedBigInteger('level_id')->default(1);
	        $table->unique(['email'], 'u_email');
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
            $table->dropColumn('level_id');
            $table->dropIndex('u_email');
        });
    }
}
