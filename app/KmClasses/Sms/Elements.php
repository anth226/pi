<?php
/**
 * Created by PhpStorm.
 * User: KM
 * Date: 3/5/2019
 * Time: 10:32 AM
 */

namespace App\KmClasses\Sms;

use App\LevelsSalespeople;
use App\PdfTemplates;
use App\SupportTodo;
use App\User;
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

	public static function createDate($datetimestring){
		$timezone = config('app.timezone');
		$carbon = Carbon::instance(date_create_from_format('m-d-Y', $datetimestring));
		$carbon->timezone($timezone);
		return $carbon->toDateString();
	}

	public static function pdfTemplatesSelect($name, $params = [], $value = 0, $invoice_type = 0){
		$res = '';
		$templates = PdfTemplates::where('invoice_type', $invoice_type)->get();
		if($templates && $templates->count()) {
			foreach ( $templates as $ss ) {
					$option_title = $ss->title;
					$selected     = '';
					if ( $value  && $value == $ss->id) {
						$selected = ' selected ';
					}
					$res .= '<option value="' . $ss->id . '" ' . $selected . ' >' . $option_title . '</option>';
			}
		}
		if($res) {
			$added_params = '';
			if ( count( $params ) ) {
				foreach ( $params as $n => $val ) {
					$added_params .= ' ' . $n . '="' . $val . '" ';
				}
			}
			$res = '<select
						 name="' . $name . '"
                         ' . $added_params . '
                        >' . $res . '</select>';
		}
		return $res;
	}

	public static function salespeopleSelect($name, $params = [], $values = []){
		$res = '<option value="">Please Select</option>';
		$salespeople = LevelsSalespeople::with('salespeople')->with('level')->get();
		if($salespeople && $salespeople->count()){
			foreach($salespeople as $ss){
				if(!$ss->salespeople->deleted_at) {
					$option_title = $ss->salespeople->name_for_invoice . ' (' . $ss->level->title . ' | ' . $ss->level->percentage . '%)';
					$selected     = '';
					if ( count( $values ) ) {
						foreach ( $values as $v ) {
							if ( $v == $ss->id ) {
								$selected = ' selected ';
							}
						}
					}
					$res .= '<option value="' . $ss->id . '" ' . $selected . ' data-salesperson_id="' . $ss->salespeople_id . '" data-level_id="' . $ss->level_id . '" >' . $option_title . '</option>';
				}
			}
			if($res) {
				$added_params = '';
				if(count($params)){
					foreach($params as $n=>$val){
						if($n == 'multiple'){
							$added_params .= ' ' . $n . ' ';
						}
						else {
							$added_params .= ' ' . $n . '="' . $val . '" ';
						}
					}
				}
				$res = '<select
						 name="' . $name . '"
                         ' .$added_params. '
                        >' . $res . '</select>';
			}
		}
		return $res;
	}

	public static function supportRepsSelect($name, $params = [], $values = []){
		$res = '<option value="">Please Select</option>';
		$supportReps = User::whereHas("roles", function($q){ $q->where("name", "Support Rep"); })->orderBy('id','DESC')->get();
		if($supportReps && $supportReps->count()){
			foreach($supportReps as $ss){
				if(!$ss->deleted_at) {
					$option_title = $ss->name;
					$selected     = '';
					if ( count( $values ) ) {
						foreach ( $values as $v ) {
							if ( $v == $ss->id ) {
								$selected = ' selected ';
							}
						}
					}
					$res .= '<option value="' . $ss->id . '" ' . $selected . ' >' . $option_title . '</option>';
				}
			}
			if($res) {
				$added_params = '';
				if(count($params)){
					foreach($params as $n=>$val){
						if($n == 'multiple'){
							$added_params .= ' ' . $n . ' ';
						}
						else {
							$added_params .= ' ' . $n . '="' . $val . '" ';
						}
					}
				}
				$res = '<select
						 name="' . $name . '"
                         ' .$added_params. '
                        >' . $res . '</select>';
			}
		}
		return $res;
	}

	public static function taskSelect($name, $params = [], $values = []){
		$res = '<option value="">Please Select</option>';
		$tasks = SupportTodo::TASK_TYPE;
		if($tasks && count($tasks)){
			foreach($tasks as $id => $title){
					$selected     = '';
					if ( count( $values ) ) {
						foreach ( $values as $v ) {
							if ( $v == $id ) {
								$selected = ' selected ';
							}
						}
					}
					$res .= '<option value="' . $id . '" ' . $selected . ' >' . $title . '</option>';

			}
			if($res) {
				$added_params = '';
				if(count($params)){
					foreach($params as $n=>$val){
						if($n == 'multiple'){
							$added_params .= ' ' . $n . ' ';
						}
						else {
							$added_params .= ' ' . $n . '="' . $val . '" ';
						}
					}
				}
				$res = '<select
						 name="' . $name . '"
                         ' .$added_params. '
                        >' . $res . '</select>';
			}
		}
		return $res;
	}
}