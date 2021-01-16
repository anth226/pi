<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCommissionsBalancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('commissions_balances', function (Blueprint $table) {
	        $table->bigIncrements('id');
	        $table->timestamps();
	        $table->unsignedBigInteger('salespeople_id')->default(0);
	        $table->decimal('unpaid_balance',10,2)->default(0);
	        $table->decimal('paid_amount',10,2)->default(0);
	        $table->foreign('salespeople_id')->references('id')->on('salespeoples')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('commissions_balances');
    }
}
