<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLevelsSalespeoplesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('levels_salespeoples', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
	        $table->unsignedBigInteger('level_id')->default(0);
	        $table->unsignedBigInteger('salespeople_id')->index();
	        $table->foreign('salespeople_id')->references('id')->on('salespeoples')->onUpdate('CASCADE');
	        $table->foreign('level_id')->references('id')->on('salespeople_levels')->onUpdate('CASCADE');
	        $table->index(['level_id', 'salespeople_id']);
        });

	    $log = \App\SalespeoplePecentageLog::groupBy('salespeople_id')->pluck('salespeople_id');
	    if($log && $log->count()){
		    foreach ($log  as $salespeople_id){
		    	$level_id =  \App\SalespeoplePecentageLog::where('salespeople_id', $salespeople_id)->orderBy('created_at', 'desc')->value('level_id');
		    	$id = \App\LevelsSalespeople::where('salespeople_id', $salespeople_id)->where('level_id', $level_id)->value('id');
			    $data = [
				    'salespeople_id' => $salespeople_id,
				    'level_id' => $level_id,
				];
			    if($id){
			    	\App\LevelsSalespeople::find($id)->update($data);
			    }
			    else{
				    \App\LevelsSalespeople::create($data);

			    }

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
        Schema::dropIfExists('levels_salespeoples');
    }
}
