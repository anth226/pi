<?php
/**
 * Created by PhpStorm.
 * User: KM
 * Date: 3/5/2019
 * Time: 10:32 AM
 */

namespace App\KmClasses\Sms;

use Carbon\Carbon;

class Elements {

	public static function moneyToDecimal($number, $dec_point=null) {
		if (empty($dec_point)) {
			$locale = localeconv();
			$dec_point = $locale['decimal_point'];
		}
		return floatval(str_replace($dec_point, '.', preg_replace('/[^\d'.preg_quote($dec_point).']/', '', $number)));
	}

	public static function createDateTime($datetimestring){
		$timezone = config('app.timezone');
		$carbon = Carbon::instance(date_create_from_format('m-d-Y', $datetimestring));
		$carbon->timezone($timezone);
		return $carbon->toDateTimeString();
	}
}