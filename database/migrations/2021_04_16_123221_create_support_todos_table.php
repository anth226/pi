<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSupportTodosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('support_todos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
	        $table->unsignedBigInteger('invoice_id')->nullable();
	        $table->unsignedBigInteger('done_by_user_id')->nullable();
	        $table->unsignedBigInteger('added_by_user_id')->nullable();
	        $table->unsignedInteger('task_type')->default(0);
	        $table->unsignedTinyInteger('task_status')->default(1);
	        $table->timestamp('done_at')->nullable();
	        $table->foreign('invoice_id')->references('id')->on('invoices')->onUpdate('CASCADE');
	        $table->foreign('done_by_user_id')->references('id')->on('users')->onUpdate('CASCADE');
	        $table->foreign('added_by_user_id')->references('id')->on('users')->onUpdate('CASCADE');
	        $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('support_todos');
    }
}
