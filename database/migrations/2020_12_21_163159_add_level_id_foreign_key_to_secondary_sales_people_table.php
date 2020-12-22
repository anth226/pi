<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLevelIdForeignKeyToSecondarySalesPeopleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('secondary_sales_people', function (Blueprint $table) {
	        $table->foreign('level_id')->references('id')->on('salespeople_levels')->onUpdate('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('secondary_sales_people', function (Blueprint $table) {
            $table->dropForeign('secondary_sales_people_level_id_foreign');
        });
    }
}
