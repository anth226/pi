<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSalespeopleLevelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('salespeople_levels', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
            $table->string('title')->unique();
	        $table->unsignedDecimal('percentage',5,2)->default(0);
        });

	    DB::table('salespeople_levels')->insert(
		    [
		    	'created_at' => now(),
		    	'updated_at' => now(),
		    	'title' => 'Mandalorian',
			    'percentage' => 50
		    ]
	    );
	    DB::table('salespeople_levels')->insert(
		    [
			    'created_at' => now(),
			    'updated_at' => now(),
			    'title' => 'Stormtrooper',
			    'percentage' => 10
		    ]
	    );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('salespeople_levels');
    }
}
