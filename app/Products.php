<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Products extends Model
{
	use Sortable;
	use SoftDeletes;

	protected $fillable = [
		'title',
		'description',
		'sku',
		'price'
	];

	public static function getIdsAndFullNames(){
		return self::selectRaw("id, CONCAT(title, ' ($', price, ', sku: ', sku, ')') as full_name")
		           ->orderBy('id', 'desc')
		           ->limit(1000)
		           ->pluck('full_name', 'id')
		           ->toArray()
			;
	}
}
