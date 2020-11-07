<?php
/**
 * Created by PhpStorm.
 * User: KM
 * Date: 3/5/2019
 * Time: 10:32 AM
 */

namespace App\KmClasses\Sms;

use App\PhoneSource;
use App\ProjectsEmails;
use App\SupportNumbers;
use App\Tags;
//use qoraiche\mailEclipse\mailEclipse;
use App\KmClasses\MailEclipse\mailEclipse;

class Elements {

	public static function sourcesSelectWithSourceId($id, $class = '', $no_total = false){
		$res = '';
		$phone_sources = PhoneSource::orderBy('source')->get();
		if($phone_sources && $phone_sources->count()){
			foreach($phone_sources as $ss){
				$option_title = $ss->source;
				if(!$no_total){
					$option_title .= ' ('.$ss->total_leads.')';
				}
				$res .= '<option value="'.$ss->id.'">'.$option_title.'</option>';
			}
			if($res) {
				$res = '<select
						 name="' . $id . '[]"
                         id="' . $id . '"
                         multiple="multiple"
                         class="form-control ' . $class . '"
 						>' . $res . '</select>';
			}
		}
		return $res;

	}


	public static function tagsSelect($id, $class = ''){
		$res = '';
		$tags = Tags::orderBy('tag')->get();
		if($tags && $tags->count()){
			foreach($tags as $ss){
				$option_title = $ss->tag;
				$res .= '<option value="'.$ss->id.'">'.$option_title.'</option>';
			}
			if($res) {
				$res = '<select
						 name="' . $id . '[]"
                         id="' . $id . '"
                         multiple="multiple"
                         class="form-control ' . $class . '"
 						>' . $res . '</select>';
			}
		}
		return $res;

	}

	public static function templatesSelect($id, $class = ''){
		$res = '';
		$templates = mailEclipse::getTemplates()->orderBy('id','desc')->get();
		if($templates && $templates->count()){
			foreach($templates as $ss){
				$option_title = $ss->template_name;
				$res .= '<option value="'.$ss->template_slug.'">'.$option_title.'</option>';
			}
			if($res) {
				$res = '<select
						 name="' . $id . '"
                         id="' . $id . '"
                         class="form-control ' . $class . '"
 						>' . $res . '</select>';
			}
		}
		return $res;
	}

	public static function fromAddressSelect($id, $class = ''){
		$res = '';
		$addresses = ProjectsEmails::where('order', '>', 0)->orderBy('order','desc')->orderBy('id','asc')->get();
		if($addresses && $addresses->count()){
			foreach($addresses as $ss){
				$option_title = $ss->email_name.' &lt;'.$ss->email_address.'&gt;';
				$res .= '<option value="'.$ss->id.'">'.$option_title.'</option>';
			}
			if($res) {
				$res = '<select
						 name="' . $id . '"
                         id="' . $id . '"
                         class="form-control ' . $class . '"
 						>' . $res . '</select>';
			}
		}
		return $res;
	}


	public static function getWeekdays($id, $class = ''){
		/*
		$weekMap = [
			0 => 'SU',
			1 => 'MO',
			2 => 'TU',
			3 => 'WE',
			4 => 'TH',
			5 => 'FR',
			6 => 'SA',
		];*/
		return '<select
				 name="' . $id . '[]"
                 id="' . $id . '"
                 multiple="multiple"
                 class="form-control ' . $class . '"
                >
	                <option value="1">Monday</option>
	                <option value="2">Tuesday</option>
	                <option value="3">Wednesday</option>
	                <option value="4">Thursday</option>
	                <option value="5">Friday</option>
	                <option value="6">Saturday</option>
	                <option value="0">Sunday</option>
                </select>';
	}

	public static function selectSupportNumber($id, $class = ''){
		$res = '';
		$numbers = SupportNumbers::get();
		if($numbers && $numbers->count()){
			foreach($numbers as $ss){
				$option_title = $ss->support_number.' &lt;'.$ss->description.'&gt;';
				$res .= '<option value="'.$ss->id.'">'.$option_title.'</option>';
			}
			if($res) {
				$res = '<select
						 name="' . $id . '"
                         id="' . $id . '"
                         class="form-control ' . $class . '"
 						>' . $res . '</select>';
			}
		}
		return $res;
	}
}