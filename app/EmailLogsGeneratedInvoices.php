<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmailLogsGeneratedInvoices extends Model
{
	protected $fillable = [
		'invoice_id',
		'email_template_id',
		'user_id',
		'from',
		'to'
	];

	public function template()
	{
		return $this->hasOne('App\EmailTemplates', 'id','email_template_id');
	}
}
