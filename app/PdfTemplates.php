<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;
use Illuminate\Database\Eloquent\SoftDeletes;

class PdfTemplates extends Model
{
	use Sortable;
	use SoftDeletes;

	protected $fillable = [
		'title',
		'slug',
		'invoice_type' // 0 - regular, 1 - generated
	];
}
