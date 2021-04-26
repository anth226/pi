<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddScheduledAtFieldToSupportTodosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('support_todos', function (Blueprint $table) {
            $table->timestamp('scheduled_at')->nullable();
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
            $table->dropColumn('scheduled_at');
        });
    }
}
