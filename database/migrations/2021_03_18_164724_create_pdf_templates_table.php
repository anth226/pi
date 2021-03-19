<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePdfTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pdf_templates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
            $table->string('slug')->unique();
            $table->string('title')->nullable();
	        $table->softDeletes();
        });

	    $data1 = [
		    'slug' => 'pdfviewmain',
		    'title' => "Main Pdf Template",
	    ];
	    $data2 = [
		    'slug' => 'pdfviewmainNoLifetime',
		    'title' => "No 'Lifetime' words",
	    ];
	    $data3 = [
		    'slug' => 'pdfviewmainNoLifetimeNoSixMonth',
		    'title' => "No 'Lifetime' and No '6 months' words",
	    ];
	    \App\PdfTemplates::create($data1);
	    \App\PdfTemplates::create($data2);
	    \App\PdfTemplates::create($data3);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pdf_templates');
    }
}
