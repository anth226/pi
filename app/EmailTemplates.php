<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;

class EmailTemplates extends Model
{
	use Sortable;

	protected $fillable = [
		'template_name',
		'template_slug',
		'template_description',
		'template_view_name',
		'template_skeleton',
		'template_type'
	];

	public $sortable = [
		'id',
		'template_name',
		'template_slug',
		'template_description',
		'template_view_name',
		'template_skeleton',
		'template_type'
	];
}
