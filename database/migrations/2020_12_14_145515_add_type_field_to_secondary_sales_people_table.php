<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTypeFieldToSecondarySalesPeopleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('secondary_sales_people', function (Blueprint $table) {
            $table->unsignedTinyInteger('sp_type')->default(0)->index();
            $table->unique(['invoice_id', 'salespeople_id'], 'iid_sid_u');
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
            $table->dropColumn('sp_type');
            $table->dropIndex('iid_sid_u');
        });
    }
}
