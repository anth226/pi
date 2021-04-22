<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSupportRepUserIdToSupportTodoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('support_todos', function (Blueprint $table) {
	        $table->unsignedBigInteger('support_rep_user_id')->nullable();
	        $table->foreign('support_rep_user_id')->references('id')->on('users')->onUpdate('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('support_todos', function (Blueprint $table) {
            //
        });
    }
}
