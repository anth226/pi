<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmailLogs extends Model
{
	protected $fillable = [
		'invoice_id',
		'email_template_id',
		'from',
		'to',
		'result'
	];

	public function template()
	{
		return $this->hasOne('App\EmailTemplates', 'id','email_template_id');
	}

}
