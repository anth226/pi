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
	        $table->foreign('salespeople_id')->references('id')->on('salespeoples')->onUpdate('CASCADE');
	        $table->foreign('level_id')->references('id')->on('salespeople_levels')->onUpdate('CASCADE');
	    });

	    $salespeople = Salespeople::get();
	    if($salespeople && $salespeople->count()){
		    foreach ($salespeople  as $s){
			    $data = [
			    	'level_id' => 2,
			    	'salespeople_id' => $s->id,
				    'percentage' => 10
			    ];
			    if(
			    	$s->id == 4 || //Dan Halimi
			    	$s->id == 3 || //Sam simon
			    	$s->id == 2 || //Deric ned
			    	$s->id == 1    //Kyle sanna
			    ){
				    $data = [
					    'level_id' => 1,
					    'salespeople_id' => $s->id,
					    'percentage' => 50
				    ];
			    }
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
