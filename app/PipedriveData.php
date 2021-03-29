<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PipedriveData extends Model
{
	protected $fillable = [
		'customer_id',
		'field_name',
		'pd_person_id',
		'pd_source_string_id'
	];


	public const FIELD_NAME = [
		0 => 'Source',
		1 => 'EXTRA FIELD'
	];

	public function fieldName()	{
		return $this->hasOne('App\Strings', 'id','pd_source_string_id');
	}
}
