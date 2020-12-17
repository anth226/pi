<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Salespeople;
use App\SalespeoplePecentageLog;

class CreateSalespeoplePecentageLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('salespeople_pecentage_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
	        $table->unsignedBigInteger('level_id')->default(0);
	        $table->unsignedBigInteger('salespeople_id')->index();
	        $table->unsignedDecimal('percentage',5,2)->default(0);
	        $table->foreign('salespeople_id')->references('id')->on('salespeoples')->onUpdate('CASCADE')->onDelete('CASCADE');
	        $table->foreign('level_id')->references('id')->on('salespeople_levels')->onUpdate('CASCADE')->onDelete('CASCADE');
	    });

	    $salespeople = Salespeople::get();
	    if($salespeople && $salespeople->count()){
		    foreach ($salespeople  as $s){
			    $data = [
			    	'level_id' => 1,
			    	'salespeople_id' => $s->id,
				    'percentage' => 50
			    ];
			    SalespeoplePecentageLog::create($data);
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
        Schema::dropIfExists('salespeople_pecentage_logs');
    }
}
