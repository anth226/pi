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
		'price',
		'stripe_coupon_id',
		'stripe_price_id',
		'dev_stripe_coupon_id',
		'dev_stripe_price_id'
	];

	public static function getIdsAndFullNames(){
		return self::selectRaw("id, CONCAT(title, ' (', description, ')') as full_name")
		           ->orderBy('id', 'asc')
		           ->limit(1000)
		           ->pluck('full_name', 'id')
		           ->toArray()
			;
	}

	public function invoices()	{
		return $this->belongsTo('App\Invoices', 'product_id','id');
	}
}
