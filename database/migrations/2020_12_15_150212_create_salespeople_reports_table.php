<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSalespeopleReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('salespeople_reports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
	        $table->date('report_date')->nullable()->index();
	        $table->unsignedBigInteger('salespeople_id');
	        $table->unsignedDecimal('percentage',5,2)->default(0);
	        $table->unsignedInteger('total_sales')->default(0);
	        $table->unsignedDecimal('sales',10,2)->default(0);
	        $table->unsignedDecimal('earnings',10,2)->default(0);
	        $table->foreign('salespeople_id')->references('id')->on('salespeoples')->onUpdate('CASCADE')->onDelete('CASCADE');
	        $table->unique(['salespeople_id', 'report_date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('salespeople_reports');
    }
}
