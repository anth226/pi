<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomersContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customers_contacts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
            $table->unsignedInteger('contact_type')->default(0)->index();
            $table->unsignedInteger('contact_subtype')->default(0)->index();
            $table->unsignedInteger('contact_notes')->default(0)->index();
            $table->unsignedBigInteger('is_main_for_invoice_id')->nullable()->index();
            $table->string('contact_term');
            $table->string('formated_contact_term')->index();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
	        $table->foreign('customer_id')->references('id')->on('customers')->onUpdate('CASCADE');
	        $table->foreign('is_main_for_invoice_id')->references('id')->on('invoices')->onUpdate('CASCADE');
	        $table->foreign('user_id')->references('id')->on('users')->onUpdate('CASCADE');
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
        Schema::dropIfExists('customers_contacts');
    }
}
