<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
            $table->unsignedBigInteger('email_template_id')->default(0);
            $table->unsignedBigInteger('customer_id')->default(0);
            $table->unsignedBigInteger('salespeople_id')->default(0);
            $table->unsignedBigInteger('product_id')->default(0);
	        $table->unsignedDecimal('sales_price',8,2)->default(0);
	        $table->unsignedInteger('qty')->default(1);
	        $table->date('access_date')->nullable();
	        $table->string('password')->nullable();
	        $table->string('invoice_number',20)->nullable();
	        $table->unsignedInteger('cc')->default(0);
	        $table->foreign('email_template_id')->references('id')->on('email_templates')->onUpdate('CASCADE')->onDelete('CASCADE');
	        $table->foreign('customer_id')->references('id')->on('customers')->onUpdate('CASCADE')->onDelete('CASCADE');
	        $table->foreign('salespeople_id')->references('id')->on('salespeoples')->onUpdate('CASCADE')->onDelete('CASCADE');
	        $table->foreign('product_id')->references('id')->on('products')->onUpdate('CASCADE')->onDelete('CASCADE');
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
        Schema::dropIfExists('invoices');
    }
}
