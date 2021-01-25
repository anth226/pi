<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPaidAtFieldToSecondarySalesPeopleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('secondary_sales_people', function (Blueprint $table) {
            $table->timestamp('paid_at')->nullable();
            $table->decimal('discrepancy')->default(0);
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
            $table->dropColumn('paid_at');
            $table->dropColumn('discrepancy');
        });
    }
}
