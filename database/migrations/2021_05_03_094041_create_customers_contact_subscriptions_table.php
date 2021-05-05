<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomersContactSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customers_contact_subscriptions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
	        $table->unsignedBigInteger('customers_contact_id')->nullable();
	        $table->unsignedBigInteger('user_id')->nullable();
	        $table->unsignedBigInteger('invoice_id')->nullable();
	        $table->unsignedInteger('subscription_type')->default(0)->index();
	        $table->unsignedTinyInteger('subscription_status')->default(1)->index();
	        $table->foreign('customers_contact_id')->references('id')->on('customers_contacts')->onUpdate('CASCADE');
	        $table->foreign('invoice_id')->references('id')->on('invoices')->onUpdate('CASCADE');
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
        Schema::dropIfExists('customers_contact_subscriptions');
    }
}
