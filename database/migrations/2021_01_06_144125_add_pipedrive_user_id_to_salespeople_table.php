<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPipedriveUserIdToSalespeopleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('salespeoples', function (Blueprint $table) {
            $table->string('pipedrive_user_id')->nullable();
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
            $table->dropColumn('pipedrive_user_id');
        });
    }
}
