<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmailLogsGeneratedInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('email_logs_generated_invoices', function (Blueprint $table) {
	        $table->bigIncrements('id');
	        $table->timestamps();
	        $table->unsignedBigInteger('invoice_id')->default(0);
	        $table->unsignedBigInteger('email_template_id')->default(0);
	        $table->string('from');
	        $table->string('to');
	        $table->foreign('invoice_id')->references('id')->on('invoice_generators')->onUpdate('CASCADE')->onDelete('CASCADE');
	        $table->foreign('email_template_id')->references('id')->on('email_templates')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('email_logs_generated_invoices');
    }
}
