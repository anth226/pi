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

	public static function getIdsAndFullNames(){
		return self::selectRaw("id, template_name as full_name")
		           ->orderBy('id', 'desc')
		           ->limit(10000)
		           ->pluck('full_name', 'id')
		           ->toArray()
			;
	}

	public function invoices()	{
		return $this->belongsTo('App\Invoices', 'email_template_id','id');
	}
}
