<?php
/**
 * Created by PhpStorm.
 * User: KM
 * Date: 3/5/2019
 * Time: 10:32 AM
 */

namespace App\KmClasses\Sms;


class FormatUsPhoneNumber {

	protected static function cleanString($string)
	{
		// allow only letters
		$res = preg_replace("/[^0-9]/", "", $string);

		// trim what's left to 8 chars
		$res = substr($res, 0, 11);

		// make lowercase
		$res = strtolower($res);

		// return
		return $res;
	}

	public static function formatPhoneNumber($phoneNumber) {
		$first_char = substr( trim( $phoneNumber ), 0, 1 );
		$add_plus   = '';
		if ( $first_char === '+' ) {
			$add_plus = '+';
		}
		$cleaned_phone = static::cleanString( $phoneNumber );

		$first_cleaned_char = substr( $cleaned_phone, 0, 1 );
		$formated_phone     = '';

		if ( $add_plus ) {
			if ( $first_cleaned_char === '1' ) {
				if ( strlen( $cleaned_phone ) === 11 ) {
					$formated_phone = '+' . $cleaned_phone;
				}
			}
		} else {
			if ( $first_cleaned_char === '1' && strlen( $cleaned_phone ) === 11) {
				$formated_phone = '+' . $cleaned_phone;
			}
			else {
				if ( strlen( $cleaned_phone ) === 10 ) {
					$formated_phone = '+1' . $cleaned_phone;
				}
			}
		}
		return $formated_phone;
	}

	public static function generateRandomString($length = 10) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}
}