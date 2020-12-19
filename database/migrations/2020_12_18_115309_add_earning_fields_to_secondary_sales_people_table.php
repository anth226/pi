<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEarningFieldsToSecondarySalesPeopleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('secondary_sales_people', function (Blueprint $table) {
            $table->unsignedDecimal('earnings', 10,2)->default(0);
            $table->unsignedDecimal('percentage', 5,2)->default(0);
            $table->unsignedBigInteger('level_id')->default(0)->index();
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
            $table->dropColumn('earnings');
            $table->dropColumn('percentage');
            $table->dropColumn('level_id');
        });
    }
}
