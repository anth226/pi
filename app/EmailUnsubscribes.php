<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use App\Exeptions;
use Exception;

class EmailUnsubscribes extends Model
{
	protected $fillable = [
		'email_id',
		'project_id',
		'unsubscribed_by' // 0  user, 2 - complaints , 6- programmatically, 5 - manualy, 4 - subscribe popup
	];


	protected static function addField($email_id, $project_id,  $unsubscribed_by){
		try {
			$res =  self::firstOrCreate(
				[
					'email_id' => $email_id,
					'project_id' => $project_id
				],
				[
					'unsubscribed_by' => $unsubscribed_by
				]

			);
			return $res;
		}
		catch (Exception $ex){
			$err = $ex->getMessage();
			Exeptions::create( ['error' => $err, 'controller' => 'EmailUnsubscribes model', 'function' => 'addField'] );
			return false;
		}
	}

	public static function getFieldId($email_id, $project_id, $unsubscribed_by = 0){
		$id = 0;
		$res = self::addField($email_id, $project_id,  $unsubscribed_by);
		if($res && $res->id){
			$id = $res->id;
		}
		return $id;
	}

	public static function isAlreadyUnsubscribed($email_id, $project_id){
		return self::where('email_id', $email_id)
		           ->where('project_id', $project_id)->count();
	}
}
