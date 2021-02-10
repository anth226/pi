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
	        $table->date('access_date')->nullable();
	        $table->json('invoice_data')->nullable();
	        $table->unsignedDecimal('own',8,2)->default(0);
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
