<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUserIdFieldToEmailLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('email_logs', function (Blueprint $table) {
	        $table->unsignedBigInteger('user_id')->nullable();
	        $table->foreign('user_id')->references('id')->on('users')->onUpdate('CASCADE');
        });
	    Schema::table('email_logs_generated_invoices', function (Blueprint $table) {
		    $table->unsignedBigInteger('user_id')->nullable();
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
        Schema::table('email_logs', function (Blueprint $table) {
            //
        });
    }
}
