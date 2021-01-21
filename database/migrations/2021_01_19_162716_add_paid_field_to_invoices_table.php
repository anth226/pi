<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Invoices;

class AddPaidFieldToInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
	        $table->unsignedDecimal('paid',8,2)->default(0);
	        $table->unsignedDecimal('own',8,2)->default(0);
	        $table->timestamp('paid_at')->nullable();
        });

	    $invoices = Invoices::withTrashed()->get();
	    if($invoices && $invoices->count()){
		    foreach ($invoices  as $s){
			    $data = [
				    'paid' => $s->sales_price,
				    'paid_at' => $s->updated_at
			    ];
			    Invoices::withTrashed()->find($s->id)->update($data);
		    }
	    }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('paid');
            $table->dropColumn('own');
            $table->dropColumn('paid_at');
        });
    }
}
