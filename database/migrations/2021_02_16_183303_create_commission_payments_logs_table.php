<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCommissionPaymentsLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('commission_payments_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
	        $table->unsignedBigInteger('invoice_id')->default(0);
	        $table->unsignedBigInteger('user_id')->default(0);
	        $table->unsignedBigInteger('salespeople_id')->default(0);
	        $table->decimal('paid_amount',10,2)->default(0);
	        $table->unsignedTinyInteger('payment_type')->default(0);
	        $table->foreign('salespeople_id')->references('id')->on('salespeoples')->onUpdate('CASCADE');
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
        Schema::dropIfExists('commission_payments_logs');
    }
}
