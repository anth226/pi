<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvoiceGeneratorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoice_generators', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
	        $table->string('first_name');
	        $table->string('last_name');
	        $table->string('email')->nullable()->index();
	        $table->string('phone_number')->nullable();
	        $table->string('formated_phone_number')->nullable();
	        $table->string('address_1')->nullable();
	        $table->string('address_2')->nullable();
	        $table->string('zip')->nullable();
	        $table->string('city')->nullable();
	        $table->string('state')->nullable();
	        $table->date('access_date')->nullable();
	        $table->unsignedInteger('cc')->default(0);
	        $table->unsignedDecimal('sales_price',8,2)->default(0);
	        $table->unsignedDecimal('grand_total',8,2)->default(0);
	        $table->unsignedDecimal('paid',8,2)->default(0);
	        $table->unsignedDecimal('own',8,2)->default(0);
	        $table->unsignedDecimal('discount_total',8,2)->default(0);
	        $table->json('discounts')->nullable();
	        $table->string('invoice_number',20)->nullable();
	    });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invoice_generators');
    }
}
