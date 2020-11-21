<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveFieldsFromInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
//            $table->dropIndex('invoices_email_template_id_foreign');
            $table->dropForeign(['email_template_id']);
	        $table->dropColumn('email_template_id');
	        $table->dropColumn('password');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
	        $table->unsignedBigInteger('email_template_id')->default(0);
	        $table->string('password')->nullable();
	        $table->foreign('email_template_id')->references('id')->on('email_templates')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }
}
