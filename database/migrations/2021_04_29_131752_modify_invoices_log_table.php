<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifyInvoicesLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void invoices_logs
     */
	public function up()
	{
		Schema::table('invoices_logs', function (Blueprint $table) {
			$table->dropColumn('user_id');
			$table->dropColumn('invoice_id');
		});
		Schema::table('invoices_logs', function (Blueprint $table) {
			$table->unsignedBigInteger('invoice_id')->nullable();
			$table->unsignedBigInteger('user_id')->nullable();
			$table->unsignedInteger('service_id')->default(0)->index();
			$table->string('result', 1000)->nullable();
			$table->string('error', 1000)->nullable();
			$table->string('message', 1000)->nullable();
			$table->foreign('invoice_id')->references('id')->on('invoices')->onUpdate('CASCADE');
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
		Schema::table('invoices_logs', function (Blueprint $table) {

		});
	}
}
