<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUpdateIdToSalespeoplePecentageLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('salespeople_pecentage_logs', function (Blueprint $table) {
	        $table->unsignedBigInteger('update_id')->default(0);
	    });

	    $log = \App\SalespeoplePecentageLog::get();
	    if($log && $log->count()){
		    foreach ($log  as $l){
			    $data = [
				    'salespeople_id' => $l->salespeople_id,
				    'created_at' => $l->created_at,
				    'updated_at' => $l->updated_at
				];
			    $res = \App\SalespeopleLevelsUpdates::create($data);
			    $data = [
				    'update_id' => $res->id,
				];
			    \App\SalespeoplePecentageLog::find($l->id)->update($data);
		    }
	    }

	    Schema::table('salespeople_pecentage_logs', function (Blueprint $table) {
		    $table->foreign('update_id')->references('id')->on('salespeople_levels_updates')->onUpdate('CASCADE')->onDelete('CASCADE');
	    });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('salespeople_pecentage_logs', function (Blueprint $table) {
            $table->dropColumn('update_id');
        });
    }
}
