<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomersContacts extends Model
{
	use SoftDeletes;

	protected $fillable = [
		'contact_type',
		'contact_subtype',
		'contact_notes',
		'contact_term',
		'formated_contact_term',
		'customer_id',
		'user_id',
		'is_main_for_invoice_id'
	];

	public const CONTACT_TYPES = [
		0 => 'Email',
		1 => 'Phone Number'
	];

	public const CONTACT_SUBTYPES = [
		0 => 'US Phone Number',
		1 => 'International Phone Number'
	];

	public const CONTACT_NOTES_EMAILS = [
		0 => 'Other',
		1 => 'Home',
		3 => 'Work'
	];

	public const CONTACT_NOTES_PHONES = [
		0 => 'Other',
		1 => 'Home',
		3 => 'Work',
		4 => 'Mobile',
		5 => 'Landline'
	];

	public function subscriptions()
	{
		return $this->hasMany('App\CustomersContactSubscriptions', 'customers_contact_id', 'id')->where('subscription_status', '>', 0);
	}

	public function user()
	{
		return $this->hasOne('App\User', 'id','user_id')->withTrashed();
	}

	public function customer()
	{
		return $this->hasOne('App\Customers', 'id','customer_id')->withTrashed();
	}
}
